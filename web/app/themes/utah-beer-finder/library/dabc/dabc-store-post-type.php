<?php
/**
 * Implement the Store post type
 */

class DABC_Store_Post_Type {

	const POST_TYPE           = 'dabc-store';
	const TITAN_NAMESPACE     = 'dabc-store';
	const CITY_TAXONOMY       = 'dabc-store-city';
	const STORE_NUMBER        = 'dabc-store-number';
	const GOOGLE_ZOOM         = 'dabc-store-google-zoom';
	const ADDRESS_1           = 'dabc-store-address-1';
	const ADDRESS_2           = 'dabc-store-address-2';
	const PHONE_NUMBER        = 'dabc-store-phone';
	const LATITUDE            = 'dabc-store-latitude';
	const LONGITUDE           = 'dabc-store-longitude';
	const DABC_IMAGE_CRON     = 'image_dabc_store';
	const DABC_STORE_CRON     = 'sync_dabc_stores';
	const DABC_IMG_SEARCHED   = 'has-dabc-image';
	const STORES_JS_URL       = 'http://abc.utah.gov/common/script/abcMap.js';
	const STORE_IMGURL_FORMAT = 'http://abc.utah.gov/stores/images/store%s.jpg';

	var $titan;

	function __construct() {

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

	}

	function init() {

		$this->register_post_type();

		$this->register_post_meta();

		$this->register_taxonomies();

		$this->attach_hooks();

	}

	function register_post_type() {

		register_post_type(
			self::POST_TYPE,
			array(
				'public'      => true,
				'labels'      => array(
					'name'               => 'Stores',
					'singular_name'      => 'Store',
					'add_new'            => 'Add New',
					'add_new_item'       => 'Add New Store',
					'edit_item'          => 'Edit Store',
					'new_item'           => 'New Store',
					'view_item'          => 'View Store',
					'search_items'       => 'Search Stores',
					'not_found'          => 'No stores found.',
					'not_found_in_trash' => 'No stores found in trash',
					'parent_item_colon'  => null,
					'all_items'          => 'All Stores'
				),
				'supports'    => array(
					'title',
					'editor',
					'thumbnail',
					'comments',
					'revisions'
				),
				'rewrite'     => array(
					'slug' => 'store'
				),
				'has_archive' => 'stores'
			)
		);

	}

	function register_post_meta() {

		$box = $this->titan->createMetaBox( array(
			'name'      => 'Store Info',
			'id'        => 'dabc-store-info',
			'post_type' => self::POST_TYPE
		) );

		$box->createOption( array(
			'name' => 'Store Number',
			'id'   => self::STORE_NUMBER
		) );

		$box->createOption( array(
			'name' => 'Address 1',
			'id'   => self::ADDRESS_1
		) );

		$box->createOption( array(
			'name' => 'Address 2',
			'id'   => self::ADDRESS_2
		) );

		$box->createOption( array(
			'name' => 'Phone Number',
			'id'   => self::PHONE_NUMBER
		) );

		$box->createOption( array(
			'name' => 'Latitude',
			'id'   => self::LATITUDE
		) );

		$box->createOption( array(
			'name' => 'Longitude',
			'id'   => self::LONGITUDE
		) );

		$box->createOption( array(
			'name' => 'Google Zoom',
			'id'   => self::GOOGLE_ZOOM
		) );

	}

	function register_taxonomies() {

		register_taxonomy( self::CITY_TAXONOMY, self::POST_TYPE, array(
			'label' => 'City'
		) );

	}

	function attach_hooks() {

		add_action( self::DABC_IMAGE_CRON, array( $this, 'sync_featured_image_with_dabc' ), 10, 2 );

		add_action( self::DABC_STORE_CRON, array( $this, 'sync_stores_with_dabc' ) );

	}

	/**
	 * HTTP request helper
	 *
	 * @param string $url URL to request
	 * @param array $args wp_remote_request() arguments
	 * @return boolean|WP_Error|string - bool on non-200, WP_Error on error, string body on 200
	 */
	function _make_http_request( $url, $args = array() ) {

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {

			return $response;

		} else if ( 200 === wp_remote_retrieve_response_code( $response ) ) {

			return wp_remote_retrieve_body( $response );

		}

		return false;

	}

	/**
	 * Get store(s) by their DABC number
	 *
	 * @param string|array $store_number DABC store number(s)
	 * @return WP_Query
	 */
	function query_stores_by_number( $store_number ) {

		$store_number = (array) $store_number;

		$store_query = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => self::TITAN_NAMESPACE . '_' . self::STORE_NUMBER,
					'value'   => $store_number,
					'compare' => 'IN'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => count( $store_number ),
			'orderby'        => 'post_title',
			'order'          => 'ASC'
		) );

		return $store_query;

	}

	/**
	 * Create a DABC Store post, including meta and taxonomy terms
	 *
	 * expected $store_info structure:
	 *	array(
	 *		"label"       => "Store #39"
     *		"hours"       => "Monday - Saturday, 11:00 am to 10:00 pm",
     *		"number"      => "39"
     *		"address1"    => "(Wine Store) 161 North 900 East"
     *		"address2"    => "St. George, UT 84770"
     *		"phone"       => "(435) 674-9550"
     *		"latitude"    => "37.1104731609"
     *		"longitude"   => "-113.564036681"
     *		"google_zoom" => "16"
     *		"city"        => "St. George"
	 *  )
	 *
	 * @param array $store_info
	 */
	function create_store( $store_info ) {

		$post_id = wp_insert_post( array(
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
			'post_title' => $store_info['label'],
			'post_content' => $store_info['hours']
		) );

		$this->titan->setOption( self::STORE_NUMBER, $store_info['number'], $post_id );

		$this->titan->setOption( self::GOOGLE_ZOOM, $store_info['google_zoom'], $post_id );

		$this->titan->setOption( self::ADDRESS_1, $store_info['address1'], $post_id );

		$this->titan->setOption( self::ADDRESS_2, $store_info['address2'], $post_id );

		$this->titan->setOption( self::PHONE_NUMBER, $store_info['phone'], $post_id );

		$this->titan->setOption( self::LATITUDE, $store_info['latitude'], $post_id );

		$this->titan->setOption( self::LONGITUDE, $store_info['longitude'], $post_id );

		if ( ! term_exists( $store_info['city'], self::CITY_TAXONOMY ) ) {

			wp_insert_term( $store_info['city'], self::CITY_TAXONOMY );

		}

		wp_set_object_terms( $post_id, $store_info['city'], self::CITY_TAXONOMY );

		$this->sync_featured_image_with_dabc( $store_info['number'], $post_id );

		return $post_id;

	}

	/**
	 * Parse DABC store map JavaScript into PHP array of store info
	 *
	 * @param string $map_js abcMap.js contents from DABC site
	 * @return array $stores array of stores' info
	 */
	function parse_dabc_store_map_js( $map_js ) {

		$stores  = array();

		$matches = array();

		preg_match_all( '/^locations\.push\(({.+})\);/m', $map_js, $matches );

		if ( isset( $matches[1] ) ) {

			foreach ( $matches[1] as $store_json ) {

				$store_json = preg_replace( '/ ([a-zA-Z][\w\d]*):/', ' "$1":', $store_json );

				$store_json = str_replace( "'", '"', $store_json );

				$store = json_decode( $store_json, ARRAY_A );

				// remove store type designation from address field
				if ( isset( $store['address01'] ) ) {

					$store['address01'] = preg_replace( '/^\([[:word:]\s]+\)\s/', '', $store['address01'] );

				}

				if ( ! is_null( $store['storeNumber'] ) ) {

					$stores[] = $store;

				}
			}

		}

		return $stores;

	}

	/**
	 * Import all DABC stores from their map javascript file
	 */
	function sync_stores_with_dabc() {

		$map_js = $this->_make_http_request( self::STORES_JS_URL );

		if ( $map_js && !is_wp_error( $map_js ) ) {

			$stores = $this->parse_dabc_store_map_js( $map_js );

			foreach ( $stores as $store ) {

				$existing_store = $this->query_stores_by_number( $store['storeNumber'] );

				if ( $existing_store->have_posts() ) {

					continue;

				}

				$this->create_store( array(
					'label'       => $store['label'],
					'hours'       => $store['hours'],
					'number'      => $store['storeNumber'],
					'phone'       => $store['phone'],
					'address1'    => $store['address01'],
					'address2'    => $store['address02'],
					'city'        => $store['whatCity'],
					'google_zoom' => $store['googleZoom'],
					'latitude'    => $store['latitude'],
					'longitude'   => $store['longitude']
				) );

			}

		}

	}

	function get_store_number( $post_id ) {

		return $this->titan->getOption( self::STORE_NUMBER, $post_id );

	}

	function get_store_address( $post_id ) {

		$line1 = $this->titan->getOption( self::ADDRESS_1, $post_id );

		$line2 = $this->titan->getOption( self::ADDRESS_2, $post_id );

		return $line1 . '<br>' . $line2;

	}

	function get_store_phone_number( $post_id ) {

		return $this->titan->getOption( self::PHONE_NUMBER, $post_id );

	}

	function get_store_tel_link( $post_id ) {

		$phone_number = $this->get_store_phone_number( $post_id );

		$phone_number = str_replace( array( '(', ')', ' ' ), array( '', '', '-' ), $phone_number );

		return sprintf( 'tel:+1-%s', esc_attr( $phone_number ) );

	}

	function get_store_latitude( $post_id ) {

		return $this->titan->getOption( self::LATITUDE, $post_id );

	}

	function get_store_longitude( $post_id ) {

		return $this->titan->getOption( self::LONGITUDE, $post_id );

	}

	/**
	 * Grab featured images from DABC
	 */
	function sync_featured_images_with_dabc() {

		$no_image_stores = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			)
		) );

		foreach ( $no_image_stores->posts as $post_id ) {

			$store_number = $this->get_store_number( $post_id );

			$image_synced = $this->titan->getOption( self::DABC_IMG_SEARCHED, $post_id );

			if ( $store_number && ! $image_synced ) {

				$this->schedule_dabc_image_sync_for_store( $store_number, $post_id );

			}

		}

	}

	/**
	 * Schedule a job to download a store image from the DABC
	 *
	 * @param int $store_number DABC store number
	 * @param int $post_id store post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_dabc_image_sync_for_store( $store_number, $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::DABC_IMAGE_CRON, array( $store_number, $post_id ) );

	}

	/**
	 * Build a store image URL from it's Store Number
	 *
	 * @param int $number
	 * @return string URL for DABC Store image
	 */
	function get_dabc_store_image_url( $number ) {

		return sprintf( self::STORE_IMGURL_FORMAT, $number );

	}

	/**
	 * Download a store's image from the DABC and set as it's featured image
	 *
	 * @param int $post_id
	 */
	function sync_featured_image_with_dabc( $store_number, $post_id ) {

		if ( ! function_exists( 'media_sideload_image' ) ) {

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/media.php' );

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/file.php' );

		}

		if ( ! function_exists( 'wp_read_image_metadata' ) ) {

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/image.php' );

		}

		$image_url = $this->get_dabc_store_image_url( $store_number );

		$result    = media_sideload_image( $image_url, $post_id );

		if ( is_wp_error( $result ) ) {

			if ( 'http_404' === $result->get_error_code() ) {

				$this->mark_store_as_image_searched( $post_id );

			} else {

				$this->schedule_dabc_image_sync_for_store( $store_number, $post_id, 10 );

			}

		} else {

			$images = get_attached_media( 'image', $post_id );

			$thumbnail = array_shift( $images );

			if ( ! is_null( $thumbnail ) ) {

				set_post_thumbnail( $post_id, $thumbnail->ID );

			}

			$this->mark_store_as_image_searched( $post_id );

		}

	}

	/**
	 * Flag a store as having an image sync attempt with the DABC
	 *
	 * @param int $post_id store post ID
	 * @return bool success
	 */
	function mark_store_as_image_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, self::DABC_IMG_SEARCHED, true );

	}

}