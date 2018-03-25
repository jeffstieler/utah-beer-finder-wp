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

add_action( 'rest_api_init', function() {
	/**
	 * Add WooCommerce Maps Store Locator store fields to the REST API
	 */
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

	/**
	 * Add WooCommerce Maps Store Locator store fields to the REST API
	 */
	register_rest_field( 'product', 'stores', array(
		'get_callback' => function( $product ) {
			return wc_get_object_terms( $product['id'], 'dtwm_map', 'term_id' );
		},
		'schema' => array(
			'description' => 'Stores',
			'type'        => 'array',
		),
	) );
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

		$args['post__not_in'] = array( get_the_ID() );
	}

	return $args;
} );

/**
 * Add product ID to the cache key used by the Product Widget.
 */
add_filter( 'woocommerce_cached_widget_id', function( $widget_id ) {
	if ( ( 'woocommerce_products' === $widget_id ) && is_singular( 'product' ) ) {
		$widget_id .= '-' . get_the_ID();
	}

	return $widget_id;
} );

function ubf_single_product_checkins_map() {
	global $post;

	$markers_query = new WP_Query( array(
		'post_type'      => 'checkin',
		'post_parent'    => $post->ID,
		'post_status'    => 'publish',
		'posts_per_page' => 50,
	) );

	if ( ! $markers_query->have_posts() ) {
		return;
	}

	extract( array(
		'maptype'     => dtwm_get_option('map_type','roadmap'),
		'filter'      => dtwm_get_option('show_filter','true'),
		'scrollwheel' => dtwm_get_option('scrollwheel', 'false'),
		'width'       => dtwm_get_option('dtwm_width','auto'),
		'height'      => dtwm_get_option('dtwm_height','450'),
	) );

	$html = '';
	ob_start();
	?>
	<h3>Checkins</h3>
	<div id="dt-woo-map-sc">
		<script>
			// Map Configuration
			var dtwmMapOptions = {
				scrollwheel: <?php echo $scrollwheel; ?>,
				mapType: '<?php echo $maptype; ?>',
				filter: '<?php echo $filter; ?>',
				width:'<?php echo $width; ?>',
				height: '<?php echo (int) $height; ?>',
				catIDs: '',
				zoom: <?php echo count( $checkin_data ) === 1 ? 18 : 0 ?>,
			};
			// Defining markers information
			var DTWMMarkersData = [
				<?php
				while ( $markers_query->have_posts() ) :
				$markers_query->the_post();
				$checkin = json_decode( get_the_content() );
				?>
				{
					lat: <?php echo esc_js( $checkin->venue->location->lat ); ?>,
					lng: <?php echo esc_js( $checkin->venue->location->lng ); ?>,
					name: ( new Date( '<?php echo esc_js( $checkin->created_at ); ?>' ) ).toLocaleDateString( 'en-US' ),
					content: '<?php echo esc_js( $checkin->venue->venue_name ); ?>',
					title_term: '',
					directions: '',
				},
				<?php endwhile; wp_reset_postdata(); ?>
			];
		</script>

		<div id="dt-woo-map-content">
			<div id="dtwm-map-canvas" class="dt-woo-map-box"></div>
		</div>
	</div><!-- /#dt-woo-map -->
	<?php
	$html = ob_get_clean();
	return $html;
}

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

	if ( $DT_Woo_Map->dtwm_get_makersdata_tax() ) {
		echo '<h3>Store Availability</h3>';
		echo do_shortcode( '[dt_woo_single_product_map]' );
		return;
	}

	echo ubf_single_product_checkins_map();
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

register_post_type( 'checkin', array(
	'public'              => true,
	'exclude_from_search' => true,
	'show_in_rest'        => true,
	'rest_base'           => 'checkins',
	'labels'              => array(
		'name'              => 'Checkins',
		'singular_name'     => 'Checkin',
		'search_items'      => 'Search Checkins',
		'all_items'         => 'All Checkins',
		'parent_item'       => 'Parent Product',
		'parent_item_colon' => 'Parent Product:',
		'edit_item'         => 'Edit Checkin',
		'update_item'       => 'Update Checkin',
		'add_new_item'      => 'Add New Checkin',
		'new_item_name'     => 'New Checkin',
		'menu_name'         => 'Checkins',
		'not_found'         => 'No Checkin found.',
	),
	'hierarchical'        => true,
) );

/**
 * Remove "products tagged" labeling from breadcrumbs.
 */
add_filter( 'woocommerce_get_breadcrumb', function( $crumbs ) {
	if ( is_product_tag() ) {
		$current_term = get_queried_object();

		array_pop( $crumbs );

		$crumbs[] = array( $current_term->name, '' );
	}

	return $crumbs;
} );

add_action( 'after_setup_theme', function() {
	remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_single_meta' );
	add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_false' );
} );
