<?php

require_once( __DIR__ . '/base-service.php' );

class Untappd_Sync extends Base_Beer_Service {

	const IMAGE_CRON      = 'image_untappd';
	const ID              = 'id';
	const RATING_SCORE    = 'rating-score';
	const RATING_COUNT    = 'rating-count';
	const ABV             = 'abv';
	const HIT_RATE_LIMIT  = 'untappd-hit-limit';
	const IMAGE_SYNCED    = 'has-untappd-image';

	protected $service_name = 'untappd';

	/**
	 * Untappd HTTP request helper, handles API keys and rate limit automatically
	 *
	 * @param string $path
	 * @param array $query_params
	 * @return boolean|WP_Error|array
	 */
	function _make_http_request( $path, $query_params = array() ) {

		if (
			( false === defined( 'UNTAPPD_CLIENT_ID' ) ) ||
			( false === defined( 'UNTAPPD_CLIENT_SECRET' ) ) ||
			$this->have_hit_api_rate_limit()
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
				$this->set_hit_api_rate_limit();
			}

		} else if ( 200 === $response_code ) {

			return $body_data;

		}

		return false;

	}

	function attach_hooks() {

		parent::attach_hooks();

		add_action( self::IMAGE_CRON, array( $this, 'sync_featured_image_with_untappd' ), 10, 2 );

	}

	/**
	 * Get beer info for a given Untappd BID
	 *
	 * @param int $beer_id
	 * @return booean|object false on failure, beer object on success
	 */
	function get_beer_info( $beer_id ) {

		$response = $this->_make_http_request( "beer/info/{$beer_id}" );

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
	function have_hit_api_rate_limit() {

		return get_transient( self::HIT_RATE_LIMIT );

	}

	/**
	 * For a given DABC beer post ID, search Untappd and associate ID if found
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function map_post_to_beer( $post_id, $beer ) {

		$this->titan->setOption( self::ID, $beer->beer->bid, $post_id );

	}

	function register_post_meta() {

		$untappd_box = $this->titan->createMetaBox( array(
			'name'      => 'Untappd Info',
			'id'        => 'untappd-info',
			'post_type' => $this->post_type
		) );

		$untappd_box->createOption( array(
			'name' => 'ID',
			'id'   => self::ID
		) );

		$untappd_box->createOption( array(
			'name' => 'Rating Score',
			'id'   => self::RATING_SCORE
		) );

		$untappd_box->createOption( array(
			'name' => 'Ratings Count',
			'id'   => self::RATING_COUNT
		) );

		$untappd_box->createOption( array(
			'name' => 'ABV',
			'id'   => self::ABV
		) );

	}

	/**
	 * Schedule a job to download a beer image from Untappd
	 *
	 * @param sting $image_url
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_image_sync_for_beer( $image_url, $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::IMAGE_CRON, array( $image_url, $post_id ) );

	}

	/**
	 * Search for beers on Untappd
	 *
	 * @param string $query
	 * @return bool|WP_Error|array
	 */
	function search( $query ) {

		$args = array(
			'q'    => urlencode( $query ),
			'sort' => 'count'
		);

		$response = $this->_make_http_request( 'search/beer', $args );

		if ( $response && ! is_wp_error( $response ) && isset( $response->response->beers->items ) ) {

			return $response->response->beers->items;

		}

		return false;

	}

	/**
	 * Set a flag that we've hit the Untappd API rate limit
	 */
	function set_hit_api_rate_limit() {

		set_transient( self::HIT_RATE_LIMIT, true, HOUR_IN_SECONDS );

	}

	/**
	 * Download a beer's image from Untappd and set as it's featured image
	 *
	 * @param int $image_url
	 * @param int $post_id
	 */
	function sync_featured_image( $image_url, $post_id ) {

		if ( ! function_exists( 'media_sideload_image' ) ) {

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/media.php' );

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/file.php' );

		}

		$result = media_sideload_image( $image_url, $post_id );

		if ( is_wp_error( $result ) ) {

			$this->schedule_image_sync_for_beer( $image_url, $post_id, 10 );

		} else {

			$images = get_attached_media( 'image', $post_id );

			$thumbnail = array_shift( $images );

			if ( ! is_null( $thumbnail ) ) {

				set_post_thumbnail( $post_id, $thumbnail->ID );

			}

		}

	}

	/**
	 * For a given DABC beer post ID, sync date with Untappd
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function sync_post_beer_info( $post_id ) {

		$untappd_id = $this->titan->getOption( self::ID, $post_id );

		$beer_info  = $this->get_beer_info( $untappd_id );

		if ( is_object( $beer_info ) ) {

			if ( isset( $beer_info->rating_count ) ) {

				$this->titan->setOption( self::RATING_COUNT, $beer_info->rating_count, $post_id );

			}

			if ( isset( $beer_info->rating_score ) ) {

				$this->titan->setOption( self::RATING_SCORE, $beer_info->rating_score, $post_id );

			}

			if ( isset( $beer_info->beer_abv ) ) {

				$this->titan->setOption( self::ABV, $beer_info->beer_abv, $post_id );

			}

			if ( isset( $beer_info->beer_label ) ) {

				$this->sync_featured_image( $beer_info->beer_label, $post_id );

			}

			do_action( 'untappd_sync_post_beer_info', $post_id, $beer_info );

			return true;

		}

		return false;

	}

}