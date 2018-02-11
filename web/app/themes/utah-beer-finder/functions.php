<?php

/**
 * Add short description under product name in listing to aid in spot checking Untappd matches
 */
add_action( 'manage_product_posts_custom_column', function( $column_name, $post_id ) {
	if ( 'name' === $column_name ) {
		$product = wc_get_product( $post_id );
		echo '<p class="description">', $product->get_short_description(), '</p>';
	}
}, 20, 2 );

/**
 * Add [product_tags] shortcode that outputs with Product Tag widget
 */
add_shortcode( 'product_tags', function( $atts ) {
	add_filter( 'woocommerce_product_tag_cloud_widget_args', function( $args ) use ( $atts ) {
		return array_merge( $args, $atts );
	} );
	$widget = new WC_Widget_Product_Tag_Cloud();
	ob_start();
	$widget->widget( $atts, array( 'title' => ' ' ) );
	return ob_get_clean();
} );

/**
 * Expose WooCommerce Maps Store Locator terms via the REST API
 */
add_filter( 'register_taxonomy_args', function( $args, $name ) {
	if ( 'dtwm_map' === $name ) {
		$args['show_in_rest'] = true;
		$args['rest_base']    = 'stores';
	}

	return $args;
}, 10, 2 );

/**
 * Add WooCommerce Maps Store Locator store fields to the REST API
 */
add_action( 'rest_api_init', function() {
	$fields = array(
		'latitude' => array(
			'id'    => 'dtwm_lat',
			'label' => __( 'Latitude' ),
			'type'  => 'number',
		),
		'longitude' => array(
			'id'    => 'dtwm_lng',
			'label' => __( 'Longitude' ),
			'type'  => 'number',
		),
		'info_window' => array(
			'id'    => 'dtwm_infowindow',
			'label' => __( 'Info Window Text' ),
			'type'  => 'string',
		),
	);

	foreach ( $fields as $field_name => $field ) {
		register_rest_field( 'dtwm_map', $field_name, array(
			'get_callback' => function( $store ) use ( $field ) {
				return get_woocommerce_term_meta( $store['id'], $field['id'] );
			},
			'update_callback' => function( $value, $store ) use ( $field ) {
				return update_woocommerce_term_meta( $store->term_id, $field['id'], wc_clean( $value ) );
			},
			'schema' => array(
				'description' => $field['label'],
				'type'        => $field['type'],
			),
		) );
	}
} );

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

	return $is_request;
} );