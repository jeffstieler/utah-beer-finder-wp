<?php

/**
 * Include the DABC beer post type
 */
require_once( 'dabc-beer-post-type.php' );

/**
 * Include the DABC store post type
 */
require_once( 'dabc-store-post-type.php' );

/**
 * Register connections between post types
 */
require_once( 'o2o-connections.php' );


class DABC {

	const BEER_INVENTORY_CRON = 'sync_inventory';

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

	}

	function attach_hooks() {

		add_action( self::BEER_INVENTORY_CRON, array( $this, 'sync_inventory_for_beer' ) );

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

		$inventory = $this->beers->search_dabc_inventory_for_cs_code( $cs_code );

		if ( ! $inventory ) {

			return;

		}

		// TODO: remove beer from stores it's no longer in stock

		foreach ( $inventory as $store_inventory ) {

			$store_post = $this->stores->get_store_by_store_number( $store_inventory['store'] );

			if ( $store_post ) {

				// TODO: only add beer to store if it isn't already associated
				$this->connections->add_beer_to_store( $beer_post_id, $store_post->ID );

			}

		}

		$this->beers->set_beer_inventory( $beer_post_id, $inventory );

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
	 * Schedule one-time cron jobs to sync all beer inventory
	 */
	function sync_inventory_with_dabc() {

		$beers = new WP_Query( array(
			'post_type'      => DABC_Beer_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		array_map( array( $this, 'schedule_inventory_sync_for_beer' ), $beers->posts );

	}

}

add_action( 'init', array( new DABC(), 'init' ) );