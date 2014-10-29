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

}