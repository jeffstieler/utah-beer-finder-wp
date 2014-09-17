<?php
/**
 * Implement the Beer post type
 *
 * @package Brewtah
 */
use Symfony\Component\DomCrawler\Crawler;

class DABC_Beer_Post_Type {

	const POST_TYPE          = 'dabc-beer';
	const DEPT_TAXONOMY      = 'dabc-dept';
	const CAT_TAXONOMY       = 'dabc-cat';
	const SIZE_TAXONOMY      = 'beer-size';
	const STATUS_TAXONOMY    = 'dabc-status';
	const DABC_BEER_LIST_URL = 'http://www.webapps.abc.utah.gov/Production/OnlinePriceList/DisplayPriceList.aspx?DivCd=T';
	const TITAN_NAMESPACE    = 'dabc-beer';
	const DABC_NAME_OPTION   = 'dabc-name';
	const CS_CODE_OPTION     = 'cs-code';
	const PRICE_OPTION       = 'price';

	var $dabc_column_map;

	function __construct() {

		$this->dabc_column_map = array(
			'description',
			'div',
			'dept',
			'cat',
			'size',
			'cs_code',
			'price',
			'status',
			'spa_on',
		);

	}

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

		$titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

		$box = $titan->createMetaBox( array(
			'name'      => 'Beer Info',
			'id'        => 'beer-info',
			'post_type' => self::POST_TYPE
		) );

		$box->createOption( array(
			'name' => 'DABC Name',
			'id'   => self::DABC_NAME_OPTION,
			'desc' => 'The original description from the DABC'
		) );

		$box->createOption( array(
			'name' => 'CS Code',
			'id'   => self::CS_CODE_OPTION,
			'desc' => 'The DABC\'s SKU for this beer'
		) );

		$box->createOption( array(
			'name' => 'Price',
			'id'   => self::PRICE_OPTION
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

	function parse_dabc_beer_table_row( Crawler $row ) {

		$beer = false;

		$cols = $row->filter( 'td' );

		if ( iterator_count( $cols ) ) {

			$beer = array();

			foreach ( $this->dabc_column_map as $i => $key ) {

				$beer[$key] = $cols->eq( $i )->text();

			}

			// remove "355ml" and similar from beer description
			$beer['description'] = trim( preg_replace( '/\d+ml/', '', $beer['description'] ) );

		}

		return $beer;

	}

	function parse_dabc_beer_list( $html ) {

		$beers = array();

		$crawler = new Crawler();

		$crawler->addHtmlContent( $html );

		$table_rows = $crawler->filter( '#ctl00_ContentPlaceHolderBody_gvPricelist > tr' );

		$beers = $table_rows->each( function( Crawler $row ) {
			return $this->parse_dabc_beer_table_row( $row );
		} );

		$beers = array_filter( $beers );

		return $beers;

	}

	/**
	 * Retrieve a DABC Beer by its CS CODE
	 *
	 * @param string $cs_code
	 */
	function get_beer_by_cs_code( $cs_code ) {

		$beer_query = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_key'       => self::TITAN_NAMESPACE . '_' . self::CS_CODE_OPTION,
			'meta_value'     => $cs_code,
			'no_found_rows'  => true,
			'posts_per_page' => 1
		) );

		if ( $beer_query->have_posts() ) {

			return $beer_query->next_post();

		}

		return false;

	}

}

add_action( 'init', array( new DABC_Beer_Post_Type(), 'init' ) );