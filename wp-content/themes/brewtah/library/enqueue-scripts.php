<?php

if (!function_exists('brewtah_scripts')) :
  function brewtah_scripts() {

    // deregister the jquery version bundled with wordpress
    wp_deregister_script( 'jquery' );

    // register scripts
    wp_register_script( 'modernizr', get_template_directory_uri() . '/js/modernizr/modernizr.min.js', array(), '1.0.0', true );
    wp_register_script( 'jquery', get_template_directory_uri() . '/js/jquery/dist/jquery.min.js', array(), '1.0.0', true );
    wp_register_script( 'foundation', get_template_directory_uri() . '/js/app.js', array('jquery'), '1.0.0', true );

	if ( defined( 'BREWTAH_GOOGLE_MAPS_API' ) && BREWTAH_GOOGLE_MAPS_API ) {

		$script_args = array(
			'key' => BREWTAH_GOOGLE_MAPS_API,
			'v'   => '3.exp'
		);

		$script_url = add_query_arg( $script_args, 'https://maps.googleapis.com/maps/api/js' );

		wp_register_script( 'google-maps', $script_url, array(), false, true );

		if ( is_front_page() ) {

			wp_enqueue_script( 'google-maps' );

		}

	}

    // enqueue scripts
    wp_enqueue_script('modernizr');
    wp_enqueue_script('jquery');
    wp_enqueue_script('foundation');

  }

  add_action( 'wp_enqueue_scripts', 'brewtah_scripts' );
endif;

?>