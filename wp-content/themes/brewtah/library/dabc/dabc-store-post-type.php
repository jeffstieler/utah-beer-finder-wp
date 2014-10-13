<?php
/**
 * Implement the Store post type
 *
 * @package Brewtah
 */

class DABC_Store_Post_Type {

	const POST_TYPE       = 'dabc-store';
	const TITAN_NAMESPACE = 'dabc-store';
	const CITY_TAXONOMY   = 'dabc-store-city';
	const STORE_NUMBER    = 'dabc-store-number';
	const GOOGLE_ZOOM     = 'dabc-store-google-zoom';
	const ADDRESS_1       = 'dabc-store-address-1';
	const ADDRESS_2       = 'dabc-store-address-2';
	const PHONE_NUMBER    = 'dabc-store-phone';
	const LATITUDE        = 'dabc-store-latitude';
	const LONGITUDE       = 'dabc-store-longitude';
	const STORES_JS_URL   = 'http://abc.utah.gov/common/script/abcMap.js';

	var $titan;

	function __construct() {

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

	}

	function init() {

		$this->register_post_type();

		$this->register_post_meta();

		$this->register_taxonomies();

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
	 * Get a store post by it's DABC number
	 *
	 * @param string $store_number DABC store number
	 * @return boolean|WP_Post false on no store found, store post otherwise
	 */
	function get_store_by_store_number( $store_number ) {

		$store_query = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_key'       => self::TITAN_NAMESPACE . '_' . self::STORE_NUMBER,
			'meta_value'     => $store_number,
			'no_found_rows'  => true,
			'posts_per_page' => 1
		) );

		if ( $store_query->have_posts() ) {

			return $store_query->next_post();

		}

		return false;

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

}