<?php
/**
 * Implement the Beer post type
 *
 * @package Brewtah
 */

class DABC_Beer_Post_Type {

	const POST_TYPE = 'dabc-beer';

	function init() {

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

}

add_action( 'init', array( new DABC_Beer_Post_Type(), 'init' ) );