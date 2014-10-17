<?php

class Alphabetic_Listing {

	const TAXONOMY     = 'first-letter';
	const SUPPORT_TYPE = 'alphabetic-listing';
	const REWRITE_SLUG = 'listing';

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

				add_rewrite_rule( sprintf( '%s/%s/([a-z]){1}/?$', $archive_slug, self::REWRITE_SLUG ), "index.php?post_type={$post_type}&first-letter=\$matches[1]&orderby=post_title&order=ASC", 'top' );

				add_rewrite_rule( sprintf( '%s/%s/([a-z]){1}/page/([0-9]{1,})?$', $archive_slug, self::REWRITE_SLUG ), "index.php?post_type={$post_type}&first-letter=\$matches[1]&paged=\$matches[2]&orderby=post_title&order=ASC", 'top' );

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

	function get_first_letter_terms() {

		$terms = get_terms( self::TAXONOMY, array( 'fields' => 'names' ) );

		return ( is_wp_error( $terms ) ? array() : $terms );

	}

	function get_letter_archive_link_for_post_type( $letter, $post_type, $page = null ) {

		$post_type_object = get_post_type_object( $post_type );

		if ( is_string( $post_type_object->has_archive ) ) {

			$page = is_null( $page ) ? '' : 'page/' . $page;

			$archive_slug = $post_type_object->has_archive;

			$archive_link = sprintf( '%s/listing/%s/%s', $archive_slug, $letter, $page );

		} else {

			$archive_link = sprintf( 'listing/%s/', $letter );

		}

		return site_url( $archive_link );

	}

	function paginate_alphabetic_links( $post_type = '' ) {

		$letter_terms  = $this->get_first_letter_terms();

		$alphabet      = range( 'a', 'z' );

		echo '<ul class="pagination">', "\n";

		foreach ( $alphabet as $letter ) {

			$letter_has_term = in_array( $letter, $letter_terms );

			$classes = array();

			$link    = 'href';

			if ( $letter === get_query_var( self::TAXONOMY ) ) {

				$classes[] = 'current';

			}

			if ( $letter_has_term ) {

				$link .= sprintf( '="%s"', $this->get_letter_archive_link_for_post_type( $letter, $post_type ) );

			} else {

				$classes[] = 'unavailable';

			}

			$class = implode( ' ', $classes );

			printf( '<li class="%s letter-%s"><a %s>%s</a></li>', $class, $letter, $link, strtoupper( $letter ) );

		}

		echo '</ul>', "\n";

	}

}

function paginate_alphabetic_links( $post_type = '' ) {

	$alpha_listing = new Alphabetic_Listing();

	echo $alpha_listing->paginate_alphabetic_links( $post_type );

}

add_action( 'init', array( new Alphabetic_Listing(), 'init' ), PHP_INT_MAX );