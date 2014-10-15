<?php

class Alphabetic_Listing {

	const TAXONOMY     = 'first-letter';
	const SUPPORT_TYPE = 'alphabetic-listing';

	function init() {

		$this->register_taxonomy();

		$this->attach_hooks();

		$this->register_rewrites();

	}

	function attach_hooks() {

		add_action( 'save_post', array( $this, 'set_taxonomy_term' ), 10, 2 );

	}

	function register_taxonomy() {

		$supported_types = $this->get_supported_post_types();

		register_taxonomy( self::TAXONOMY, $supported_types, array(
			'label' => 'First Letter'
		) );

	}

	function register_rewrites() {

		$supported_types = $this->get_supported_post_types();

		foreach ( $supported_types as $type ) {

			$type = get_post_type_object( $type );

			if ( is_string( $type->has_archive ) ) {

				$archive_slug = $type->has_archive;

				$post_type    = $type->name;

				add_rewrite_rule( "{$archive_slug}/listing/([a-z]){1}/?\$", "index.php?post_type={$post_type}&first-letter=\$matches[1]", 'top' );

				add_rewrite_rule( "{$archive_slug}/listing/([a-z]){1}/page/([0-9]{1,})?\$", "index.php?post_type={$post_type}&first-letter=\$matches[1]&paged=\$matches[2]", 'top' );

			}

		}

	}

	function get_supported_post_types() {

		$post_types      = get_post_types();

		$supported_types = array_filter( $post_types, function( $post_type ) {

			return post_type_supports( $post_type, Alphabetic_Listing::SUPPORT_TYPE );

		} );

		return $supported_types;

	}

	function get_first_letter( $string ) {

		return strtolower( substr( $string, 0, 1 ) );

	}

	function set_first_letter( $post_id, $letter ) {

		return wp_set_post_terms( $post_id, $letter, self::TAXONOMY );

	}

	function set_taxonomy_term( $post_id, $post ) {

		if ( ! post_type_supports( get_post_type( $post ), self::SUPPORT_TYPE ) ) {

			return $post_id;

		}

		$letter = $this->get_first_letter( $post->post_title );

		$this->set_first_letter( $post_id, $letter );

	}

}

add_action( 'init', array( new Alphabetic_Listing(), 'init' ), PHP_INT_MAX );