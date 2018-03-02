<?php
/**
 * Plugin Name: UBF REST API Authentication
 * Plugin URI: https://utahbeerfinder.com/
 * Description: Allows WC REST API keys to work for some key WP endpoints.
 * Version: 0.1
 * Author: jeffstieler
 * Author URI: https://jeffstieler.com
 */

/**
 * Allow WooCommerce API keys to authenticate core WP endpoints we need.
 */
add_filter( 'woocommerce_rest_is_request_to_rest_api', function( $is_request ) {
	$rest_prefix = trailingslashit( rest_get_url_prefix() );

	if ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wp/v2/stores' ) ) {
		return true;
	}

	if ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wp/v2/product' ) ) {
		return true;
	}

	if ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wp/v2/checkins' ) ) {
		return true;
	}

	return $is_request;
} );