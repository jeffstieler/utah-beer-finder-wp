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
				'public'   => true,
				'labels'   => array(
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
				'supports' => array(
					'title',
					'editor',
					'thumbnail',
					'comments',
					'revisions'
				)
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

}

add_action( 'init', array( new DABC_Store_Post_Type(), 'init' ) );