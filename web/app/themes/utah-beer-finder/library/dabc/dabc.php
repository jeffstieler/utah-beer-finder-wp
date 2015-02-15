<?php

/**
 * Include the DABC beer post type
 */
require_once( __DIR__ . '/dabc-beer-post-type.php' );

/**
 * Include the DABC store post type
 */
require_once( __DIR__ . '/dabc-store-post-type.php' );

/**
 * Register connections between post types
 */
require_once( __DIR__ . '/o2o-connections.php' );

/**
 * Include widgets
 */
require_once( __DIR__ . '/widgets.php' );

/**
 * Include Alphabetical Listing plugin
 */
require_once( __DIR__ . '/alphabetic-listing.php' );

/**
 * Include template tags
 */
require_once( __DIR__ . '/template-tags.php' );

class DABC {

	const BEER_INVENTORY_CRON = 'sync_beer_inventory';
	const ALL_INVENTORY_CRON  = 'sync_all_inventory';

	var $beers;
	var $stores;
	var $connections;

	function __construct() {

		$this->beers = new DABC_Beer_Post_Type();

		$this->stores = new DABC_Store_Post_Type();

		$this->connections = new DABC_O2O_Connections();

	}

	function init() {

		$this->beers->init();

		$this->stores->init();

		$this->connections->init();

		$this->attach_hooks();

		$this->schedule_jobs();

	}

	/**
	 * Add non-standard cron schedules for use in beer services
	 *
	 * @param array $schedules list of registered cron schedules
	 * @return array filtered cron schedules
	 */
	function add_cron_schedules( $schedules ) {

		$schedules[self::TWO_MINUTE_CRON_INTERVAL] = array(
			'interval' => 2 * MINUTE_IN_SECONDS,
			'display' => __( 'Every Two Minutes' )
		);

		return $schedules;

	}

	function attach_hooks() {

		add_action( self::BEER_INVENTORY_CRON, array( $this, 'sync_inventory_for_beer' ) );

		add_action( self::ALL_INVENTORY_CRON, array( $this, 'sync_inventory_with_dabc' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'add_store_data_to_maps_script' ) );

		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );

	}

	/**
	 * Search DABC inventory for given beer, associate it with store posts
	 * where it's in stock and record the inventory quantities
	 *
	 * @param int $beer_post_id
	 */
	function sync_inventory_for_beer( $beer_post_id ) {

		$cs_code = $this->beers->get_cs_code( $beer_post_id );

		if ( ! $cs_code ) {

			return;

		}

		$inventory = $this->beers->dabc_sync->search_inventory_for_cs_code( $cs_code );

		if ( ! $inventory ) {

			return;

		}

		// TODO: remove beer from stores it's no longer in stock

		foreach ( $inventory as $store_number => $store_inventory ) {

			$stores = $this->stores->query_stores_by_number( $store_number );

			if ( $stores->have_posts() ) {

				$store_post = $stores->next_post();

				// TODO: only add beer to store if it isn't already associated
				$this->connections->add_beer_to_store( $beer_post_id, $store_post->ID );

			}

		}

		$this->beers->set_beer_inventory( $beer_post_id, $inventory );

	}

	/**
	 * Schedule recurring jobs
	 */
	function schedule_jobs() {

		wp_schedule_event( time(), 'everytwominutes', self::ALL_INVENTORY_CRON );

	}

	/**
	 * Schedule a one-time cron job to update a beer's inventory
	 *
	 * @param int $post_id
	 * @param int $offset_in_minutes
	 */
	function schedule_inventory_sync_for_beer( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::BEER_INVENTORY_CRON, array( $post_id ) );

	}

	/**
	 * Sync next beer's inventory with DABC
	 */
	function sync_inventory_with_dabc() {

		$beers_to_sync = new WP_Query( array(
			'post_type'      => DABC_Beer_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'or',
				array(
					'key'     => DABC_Beer_Post_Type::DABC_LAST_UPDATED,
					'value'   => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
					'compare' => '<='
				),
				array(
					'key'     => DABC_Beer_Post_Type::DABC_LAST_UPDATED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'order'          => 'ASC',
			'orderby'        => 'post_date',
			'no_found_rows'  => true
		) );

		if ( $beers_to_sync->posts ) {

			$beer_post_id = array_shift( $beers_to_sync->posts );

			$this->sync_inventory_for_beer( $beer_post_id );

		}

	}

	/**
	 * Retreive beers connected to a given store
	 *
	 * @param int $store_post_id
	 * @return WP_Query
	 */
	function get_store_beers( $store_post_id ) {

		$ratebeer = new Ratebeer_Sync( DABC_Beer_Post_Type::POST_TYPE );

		$beers = new WP_Query( array(
			'post_type' => DABC_Beer_Post_Type::POST_TYPE,
			'o2o_query' => array(
				'connection' => DABC_O2O_Connections::DABC_STORE_BEERS,
				'direction'  => 'to',
				'id'         => $store_post_id,
			),
			'orderby'   => array(
				'meta_value_num' => 'DESC',
				'post_title'     => 'ASC'
			),
			'meta_key'       => $ratebeer->_get_titan_meta_key( Ratebeer_Sync::OVERALL_SCORE ),
			'posts_per_page' => -1
		) );

		return $beers;

	}

	/**
	 * Add location data for all stores to be used on the homepage map
	 */
	function add_store_data_to_maps_script() {

		if ( ! is_front_page() ) {
			return;
		}

		$stores = new WP_Query( array(
			'post_type'      => DABC_Store_Post_Type::POST_TYPE,
			'posts_per_page' => -1
		) );

		$store_data = array();

		foreach ( $stores->posts as $store_post ) {

			$store_data[] = array(
				'name'        => $store_post->post_title,
				'latitude'    => $this->stores->get_store_latitude( $store_post->ID ),
				'longitude'   => $this->stores->get_store_longitude( $store_post->ID ),
				'image'       => get_the_post_thumbnail( $store_post->ID ),
				'hours'       => $store_post->post_content,
				'telLink'     => $this->stores->get_store_tel_link( $store_post->ID ),
				'phoneNumber' => $this->stores->get_store_phone_number( $store_post->ID ),
				'permalink'   => get_permalink( $store_post->ID )
			);

		}

		wp_localize_script( 'google-maps', 'storeLocations', $store_data );

	}

}

add_action( 'init', array( new DABC(), 'init' ) );