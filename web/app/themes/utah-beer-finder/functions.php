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
		$args = array_merge( $args, array(
			'show_in_rest' => true,
			'rest_base'    => 'stores',
			'rewrite'      => array(
				'slug' => 'stores',
			),
			'labels'       => array(
				'name'              => 'Stores',
				'singular_name'     => 'Store',
				'search_items'      => 'Search Stores',
				'all_items'         => 'All Stores',
				'parent_item'       => 'Parent Store',
				'parent_item_colon' => 'Parent Store:',
				'edit_item'         => 'Edit Store',
				'update_item'       => 'Update Store',
				'add_new_item'      => 'Add New Store',
				'new_item_name'     => 'New Genre Store',
				'menu_name'         => 'Stores',
				'not_found'			=> 'No Store found.',
			),
		) );
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
 * Hide empty Stores in the [dt_woo_map] shortcode.
 */
$dtwm_map_hide_empty_stores = function( $args, $taxonomies ) {
	if ( array( 'dtwm_map' ) === $taxonomies ) {
		$args['hide_empty'] = true;
	}

	return $args;
};

add_filter( 'pre_do_shortcode_tag', function( $return, $tag ) use ( $dtwm_map_hide_empty_stores ) {
	if ( 'dt_woo_map' === $tag ) {
		add_filter( 'get_terms_args', $dtwm_map_hide_empty_stores, 10, 2 );
	}

	return $return;
}, 10, 2 );

add_filter( 'do_shortcode_tag', function( $output, $tag ) use ( $dtwm_map_hide_empty_stores ) {
	if ( 'dt_woo_map' === $tag ) {
		remove_filter( 'get_terms_args', $dtwm_map_hide_empty_stores, 10, 2 );
	}

	return $output;
}, 10, 2 );

/**
 * Show beers of the same style in Product Widgets on single Beer pages.
 */
add_filter( 'woocommerce_products_widget_query_args', function( $args ) {
	if ( is_singular( 'product' ) ) {
		$product_tags = wp_get_post_terms( get_the_ID(), 'product_tag', array( 'fields' => 'ids' ) );

		$args['tax_query'][] = array(
			'taxonomy' => 'product_tag',
			'field'    => 'term_id',
			'terms'    => $product_tags,
		);
	}

	return $args;
} );

/**
 * Show store availability on the single product page
 */
add_action( 'woocommerce_after_single_product_summary', function() {
	global $DT_Woo_Map;

	if ( ! is_a( $DT_Woo_Map, 'DT_Woo_Map' ) ) {
		return;
	}

	if ( ! method_exists( $DT_Woo_Map, 'dtwm_get_makersdata_tax' ) ) {
		return;
	}

	if ( ! $DT_Woo_Map->dtwm_get_makersdata_tax() ) {
		return;
	}

	echo '<h5 class="uppercase mt">Store Availability</h5>';
	echo do_shortcode( '[dt_woo_single_product_map]' );
} );

/**
 * Move $options found in $values to the beginning of the array.
 */
function ubf_move_selected_sf_inputs_to_top( $options, $values ) {
	$idx_to_pull = [];
	$selected    = [];

	foreach ( $options as $idx => $option ) {
		if ( in_array( $option->value, $values ) ) {
            array_unshift( $idx_to_pull, $idx );
		}
	}

	foreach ( $idx_to_pull as $idx ) {
        $pulled = array_splice( $options, $idx, 1 );

        array_unshift( $selected, array_pop( $pulled ) );
	}

	return array_merge( $selected, $options );
}

/**
 * Move all selected filter values to the top of the checklist.
 */
add_filter( 'sf_input_object_pre', function( $input_args, $sfid ) {
	if ( 'checkbox' === $input_args['type'] && ! empty( $input_args['defaults'] ) ) {
		$input_args['options'] = ubf_move_selected_sf_inputs_to_top( $input_args['options'], $input_args['defaults'] );
	}

	return $input_args;
}, 10, 2 );

register_meta( 'post', 'untappd_id', array(
	'single'       => true,
	'show_in_rest' => true,
	'type'         => 'integer',
) );

add_filter( 'register_post_type_args', function( $args, $post_type ) {
	if ( 'product' === $post_type ) {
		$args['show_in_rest'] = true;
		$args['rest_base']    = 'products';
	}

	return $args;
}, 10, 2 );

