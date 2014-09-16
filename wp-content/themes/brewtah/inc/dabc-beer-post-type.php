<?php
/**
 * Implement the Beer post type
 *
 * @package Brewtah
 */

class DABC_Beer_Post_Type {

	const POST_TYPE          = 'dabc-beer';
	const DEPT_TAXONOMY      = 'dabc-dept';
	const CAT_TAXONOMY       = 'dabc-cat';
	const SIZE_TAXONOMY      = 'beer-size';
	const STATUS_TAXONOMY    = 'dabc-status';
	const DABC_BEER_LIST_URL = 'http://www.webapps.abc.utah.gov/Production/OnlinePriceList/DisplayPriceList.aspx?DivCd=T';

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
					'name'               => 'Beers',
					'singular_name'      => 'Beer',
					'add_new'            => 'Add New',
					'add_new_item'       => 'Add New Beer',
					'edit_item'          => 'Edit Beer',
					'new_item'           => 'New Beer',
					'view_item'          => 'View Beer',
					'search_items'       => 'Search Beers',
					'not_found'          => 'No beers found.',
					'not_found_in_trash' => 'No beers found in trash',
					'parent_item_colon'  => null,
					'all_items'          => 'All Beers'
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

		$titan = TitanFramework::getInstance( BREWTAH_NAMESPACE );

		$box = $titan->createMetaBox( array(
			'name'      => 'Beer Info',
			'id'        => 'beer-info',
			'post_type' => self::POST_TYPE
		) );

		$box->createOption( array(
			'name' => 'DABC Name',
			'id'   => 'dabc-name',
			'desc' => 'The original description from the DABC'
		) );

		$box->createOption( array(
			'name' => 'CS Code',
			'id'   => 'cs-code',
			'desc' => 'The DABC\'s SKU for this beer'
		) );

		$box->createOption( array(
			'name' => 'Price',
			'id'   => 'price'
		) );

	}

	function register_taxonomies() {

		register_taxonomy( self::DEPT_TAXONOMY, self::POST_TYPE, array(
			'label' => 'Department'
		) );

		register_taxonomy( self::CAT_TAXONOMY, self::POST_TYPE, array(
			'label' => 'Category'
		) );

		register_taxonomy( self::SIZE_TAXONOMY, self::POST_TYPE, array(
			'label' => 'Size'
		) );

		register_taxonomy( self::STATUS_TAXONOMY, self::POST_TYPE, array(
			'label' => 'Status'
		) );

	}

	function get_beer_list_from_dabc() {

		$result = false;

		$response = wp_remote_get( self::DABC_BEER_LIST_URL );

		if ( is_wp_error( $response ) ) {

			$result = $response;

		} else if ( 200 === wp_remote_retrieve_response_code( $response ) ) {

			$result = wp_remote_retrieve_body( $response );

		}

		return $result;

	}

}

add_action( 'init', array( new DABC_Beer_Post_Type(), 'init' ) );