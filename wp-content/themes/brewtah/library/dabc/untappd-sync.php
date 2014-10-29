<?php

class Untappd_Sync {

	const SUPPORT_TYPE    = 'untappd';
	const TITAN_NAMESPACE = 'untapped';
	const ID_OPTION       = 'id';

	var $titan;

	function __construct() {

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

	}

	function init() {

		$this->attach_hooks();

	}

	function attach_hooks() {

		add_action( 'wp_loaded', array( $this, 'register_post_meta' ) );

	}

	function register_post_meta() {

		$post_types = get_post_types();

		$post_types = array_filter( $post_types, function( $post_type ) {
			return post_type_supports( $post_type, Untappd_Sync::SUPPORT_TYPE );
		} );

		$box = $this->titan->createMetaBox( array(
			'name'      => 'Untappd Info',
			'id'        => 'untappd-info',
			'post_type' => $post_types
		) );

		$box->createOption( array(
			'name' => 'ID',
			'id'   => self::ID_OPTION
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
	 * Search for beers on Untappd
	 *
	 * @param string $query
	 * @return bool|WP_Error|array
	 */
	function search( $query ) {

		if ( ! defined( 'UNTAPPD_CLIENT_ID' ) || ! defined( 'UNTAPPD_CLIENT_SECRET' ) ) {

			return false;

		}

		$url = add_query_arg(
			array(
				'q'             => urlencode( $query ),
				'sort'          => 'count',
				'client_id'     => UNTAPPD_CLIENT_ID,
				'client_secret' => UNTAPPD_CLIENT_SECRET
			),
			'https://api.untappd.com/v4/search/beer'
		);

		$response = $this->_make_http_request( $url );

		if ( ( false === $response ) || is_wp_error( $response ) ) {

			return false;

		}

		$response_data = json_decode( $response );

		if ( isset( $response_data->response->beers->items ) ) {

			return $response_data->response->beers->items;

		}

		return false;

	}

}