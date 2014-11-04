<?php

use Symfony\Component\DomCrawler\Crawler;

class Untappd_Sync {

	const TITAN_NAMESPACE        = 'untappd';
	const UNTAPPD_SEARCHED       = 'has-untappd-searched';
	const UNTAPPD_MAP_CRON       = 'map_untappd';
	const UNTAPPD_SYNC_CRON      = 'sync_untappd';
	const UNTAPPD_IMAGE_CRON     = 'image_untappd';
	const UNTAPPD_ID             = 'untappd-id';
	const UNTAPPD_RATING_SCORE   = 'untappd-rating-score';
	const UNTAPPD_RATING_COUNT   = 'untappd-rating-count';
	const UNTAPPD_ABV            = 'untappd-abv';
	const UNTAPPD_HIT_LIMIT      = 'untappd-hit-limit';
	const UNTAPPD_SYNCED         = 'has-untappd-sync';
	const UNTAPPD_IMG_SEARCHED   = 'has-untappd-image';

	var $post_type;
	var $titan;

	function __construct( $post_type ) {

		$this->post_type = $post_type;

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

	}

	/**
	 * Untappd HTTP request helper, handles API keys and rate limit automatically
	 *
	 * @param string $path
	 * @param array $query_params
	 * @return boolean|WP_Error|array
	 */
	function _untappd_make_http_request( $path, $query_params = array() ) {

		if (
			( false === defined( 'UNTAPPD_CLIENT_ID' ) ) ||
			( false === defined( 'UNTAPPD_CLIENT_SECRET' ) ) ||
			$this->have_hit_untappd_rate_limit()
		) {

			return false;

		}

		$query_params = array_merge(
			array(
				'client_id'     => UNTAPPD_CLIENT_ID,
				'client_secret' => UNTAPPD_CLIENT_SECRET
			),
			$query_params
		);

		$url      = add_query_arg( $query_params, 'https://api.untappd.com/v4/' . $path );

		$response = wp_remote_request( $url );

		if ( is_wp_error( $response ) ) {

			return $response;

		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$body_data     = json_decode( $response_body );

		// handle rate limit
		if ( 500 === $response_code ) {

			if (
				isset( $body_data->meta->error_type ) &&
				( 'invalid_limit' === $body_data->meta->error_type )
			) {
				$this->set_hit_untappd_rate_limit();
			}

		} else if ( 200 === $response_code ) {

			return $body_data;

		}

		return false;

	}

	function attach_hooks() {

		add_action( self::UNTAPPD_IMAGE_CRON, array( $this, 'sync_featured_image_with_untappd' ), 10, 2 );

		add_action( self::UNTAPPD_MAP_CRON, array( $this, 'cron_map_dabc_beer_to_untappd' ) );

		add_action( self::UNTAPPD_SYNC_CRON, array( $this, 'cron_sync_dabc_beer_with_untappd' ) );

	}

	/**
	 * WP-Cron hook callback for searching a beer on Untappd
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_map_dabc_beer_to_untappd( $post_id ) {

		$success = $this->map_dabc_beer_to_untappd( $post_id );

		if ( $success ) {

			$this->mark_beer_as_untappd_searched( $post_id );

		} else {

			$this->schedule_untappd_search_for_beer( $post_id, 10 );

		}

	}

	/**
	 * WP-Cron hook callback for syncing a beer with Untappd
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_sync_dabc_beer_with_untappd( $post_id ) {

		$success = $this->sync_dabc_beer_with_untappd( $post_id );

		if ( $success ) {

			$this->mark_beer_as_untappd_synced( $post_id );

		} else {

			$this->schedule_untappd_sync_for_beer( $post_id, 10 );

		}

	}

	/**
	 * Get beer info for a given Untappd BID
	 *
	 * @param int $beer_id
	 * @return booean|object false on failure, beer object on success
	 */
	function get_untappd_beer_info( $beer_id ) {

		$response = $this->_untappd_make_http_request( "beer/info/{$beer_id}" );

		if ( $response && ! is_wp_error( $response ) && isset( $response->response->beer ) ) {

			return $response->response->beer;

		}

		return false;

	}

	/**
	 * Have we hit the Untappd API rate limit?
	 *
	 * @return bool
	 */
	function have_hit_untappd_rate_limit() {

		return get_transient( self::UNTAPPD_HIT_LIMIT );

	}

	function init() {

		$this->register_post_meta();

		$this->attach_hooks();

	}

	/**
	 * For a given DABC beer post ID, search Untappd and associate ID if found
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function map_dabc_beer_to_untappd( $post_id ) {

		$post = get_post( $post_id );

		$beer_name = $post->post_title;

		$search_results = $this->search_untappd( $beer_name );

		if ( is_array( $search_results ) ) {

			$beer = array_shift( $search_results );

			if ( $beer ) {

				$titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

				$titan->setOption( self::UNTAPPD_ID, $beer->beer->bid, $post_id );

			}

			return true;

		}

		return false;

	}

	/**
	 * Flag a beer as having attempted to be mapped with Untappd
	 * NOTE: many won't be found and we don't want to keep looking
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_beer_as_untappd_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, self::UNTAPPD_SEARCHED, true );

	}

	/**
	 * Flag a beer as having been synced with Untappd
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_beer_as_untappd_synced( $post_id ) {

		return (bool) update_post_meta( $post_id, self::UNTAPPD_SYNCED, true );

	}

	function register_post_meta() {

		$untappd_box = $this->titan->createMetaBox( array(
			'name'      => 'Untappd Info',
			'id'        => 'untappd-info',
			'post_type' => $this->post_type
		) );

		$untappd_box->createOption( array(
			'name' => 'ID',
			'id'   => self::UNTAPPD_ID
		) );

		$untappd_box->createOption( array(
			'name' => 'Rating Score',
			'id'   => self::UNTAPPD_RATING_SCORE
		) );

		$untappd_box->createOption( array(
			'name' => 'Ratings Count',
			'id'   => self::UNTAPPD_RATING_COUNT
		) );

		$untappd_box->createOption( array(
			'name' => 'ABV',
			'id'   => self::UNTAPPD_ABV
		) );

	}

	/**
	 * Schedule a job to download a beer image from Untappd
	 *
	 * @param sting $image_url
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_untappd_image_sync_for_beer( $image_url, $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::UNTAPPD_IMAGE_CRON, array( $image_url, $post_id ) );

	}

	/**
	 * Schedule a job to search a single beer on Untappd
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_untappd_search_for_beer( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::UNTAPPD_MAP_CRON, array( $post_id ) );

	}

	/**
	 * Schedule a job to sync a single beer with Untappd
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_untappd_sync_for_beer( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::UNTAPPD_SYNC_CRON, array( $post_id ) );

	}

	/**
	 * Find all beers that haven't been searched for on Untappd
	 * successfully and schedule a cron job to map them
	 */
	function search_beers_on_untappd() {

		$unmapped_beers = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => self::UNTAPPD_SEARCHED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		array_map( array( $this, 'schedule_untappd_search_for_beer' ), $unmapped_beers->posts );

	}

	/**
	 * Search for beers on Untappd
	 *
	 * @param string $query
	 * @return bool|WP_Error|array
	 */
	function search_untappd( $query ) {

		$args = array(
			'q'    => urlencode( $query ),
			'sort' => 'count'
		);

		$response = $this->_untappd_make_http_request( 'search/beer', $args );

		if ( $response && ! is_wp_error( $response ) && isset( $body_data->response->beers->items ) ) {

			return $body_data->response->beers->items;

		}

		return false;

	}

	/**
	 * Set a flag that we've hit the Untappd API rate limit
	 */
	function set_hit_untappd_rate_limit() {

		set_transient( self::UNTAPPD_HIT_LIMIT, true, HOUR_IN_SECONDS );

	}



	/**
	 * Retrieve info and ratings from Untappd for beers that have been mapped
	 */
	function sync_beers_with_untappd() {

		$unsynced_beers = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => self::UNTAPPD_SYNCED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		foreach ( $unsynced_beers->posts as $post_id ) {

			if ( $this->titan->getOption( self::UNTAPPD_ID, $post_id ) ) {

				$this->schedule_untappd_sync_for_beer( $post_id );

			}

		}

	}

	/**
	 * For a given DABC beer post ID, sync date with Untappd
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function sync_dabc_beer_with_untappd( $post_id ) {

		$untappd_id = $this->titan->getOption( self::UNTAPPD_ID, $post_id );

		$beer_info  = $this->get_untappd_beer_info( $untappd_id );

		if ( is_object( $beer_info ) ) {

			if ( isset( $beer_info->rating_count ) ) {

				$this->titan->setOption( self::UNTAPPD_RATING_COUNT, $beer_info->rating_count, $post_id );

			}

			if ( isset( $beer_info->rating_score ) ) {

				$this->titan->setOption( self::UNTAPPD_RATING_SCORE, $beer_info->rating_score, $post_id );

			}

			if ( isset( $beer_info->beer_abv ) ) {

				$this->titan->setOption( self::UNTAPPD_ABV, $beer_info->beer_abv, $post_id );

			}

			if ( isset( $beer_info->beer_style ) ) {

				wp_set_object_terms( $post_id, $beer_info->beer_style, self::STYLE_TAXONOMY );

			}

			if ( isset( $beer_info->brewery->brewery_name ) ) {

				wp_set_object_terms( $post_id, $beer_info->brewery->brewery_name, self::BREWERY_TAXONOMY );

			}

			if ( isset( $beer_info->brewery->country_name ) ) {

				wp_set_object_terms( $post_id, $beer_info->brewery->country_name, self::COUNTRY_TAXONOMY );

			}

			if ( isset( $beer_info->brewery->location->brewery_state ) ) {

				wp_set_object_terms( $post_id, $beer_info->brewery->location->brewery_state, self::STATE_TAXONOMY );

			}

			if ( isset( $beer_info->beer_label ) ) {

				$this->sync_featured_image_with_untappd( $beer_info->beer_label, $post_id );

			}

			return true;

		}

		return false;

	}

	/**
	 * Download a beer's image from Untappd and set as it's featured image
	 *
	 * @param int $image_url
	 * @param int $post_id
	 */
	function sync_featured_image_with_untappd( $image_url, $post_id ) {

		if ( ! function_exists( 'media_sideload_image' ) ) {

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/media.php' );

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/file.php' );

		}

		$result = media_sideload_image( $image_url, $post_id );

		if ( is_wp_error( $result ) ) {

			$this->schedule_untappd_image_sync_for_beer( $image_url, $post_id, 10 );

		} else {

			$images = get_attached_media( 'image', $post_id );

			$thumbnail = array_shift( $images );

			if ( ! is_null( $thumbnail ) ) {

				set_post_thumbnail( $post_id, $thumbnail->ID );

			}

		}

	}

}