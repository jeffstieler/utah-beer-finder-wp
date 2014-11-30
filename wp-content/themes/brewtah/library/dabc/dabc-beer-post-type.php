<?php
/**
 * Implement the Beer post type
 *
 * @package Brewtah
 */

/**
 * Include beer service syncing
 */
require_once( __DIR__ . '/services/dabc.php' );
require_once( __DIR__ . '/services/ratebeer.php' );
require_once( __DIR__ . '/services/untappd.php' );

class DABC_Beer_Post_Type {

	const POST_TYPE              = 'dabc-beer';
	const DEPT_TAXONOMY          = 'dabc-dept';
	const CAT_TAXONOMY           = 'dabc-cat';
	const SIZE_TAXONOMY          = 'beer-size';
	const STATUS_TAXONOMY        = 'dabc-status';
	const STYLE_TAXONOMY         = 'beer-style';
	const BREWERY_TAXONOMY       = 'beer-brewery';
	const STATE_TAXONOMY         = 'beer-state';
	const COUNTRY_TAXONOMY       = 'beer-country';
	const TITAN_NAMESPACE        = 'dabc-beer';
	const DABC_NAME_OPTION       = 'dabc-name';
	const CS_CODE_OPTION         = 'cs-code';
	const PRICE_OPTION           = 'price';
	const DABC_INVENTORY         = 'dabc-store-inventory';
	const DABC_SYNC_CRON         = 'dabc_inventory_sync';

	var $titan;
	var $dabc_sync;
	var $ratebeer_sync;
	var $untappd_sync;

	function __construct() {

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

		$this->dabc_sync = new DABC_Sync();

		$this->ratebeer_sync = new Ratebeer_Sync( self::POST_TYPE );

		$this->untappd_sync  = new Untappd_Sync( self::POST_TYPE );

	}

	function attach_hooks() {

		add_action( 'untappd_sync_post_beer_info', array( $this, 'set_taxonomy_data_from_untappd' ), 10, 2 );

		add_action( self::DABC_SYNC_CRON, array( $this, 'sync_beers_with_dabc' ) );

	}

	function init() {

		$this->attach_hooks();

		$this->register_post_type();

		$this->register_post_meta();

		$this->register_taxonomies();

		$this->add_post_columns();

		$this->ratebeer_sync->init();

		$this->untappd_sync->init();

		$this->schedule_jobs();

	}

	function register_post_type() {

		register_post_type(
			self::POST_TYPE,
			array(
				'public'      => true,
				'labels'      => array(
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
				'supports'    => array(
					'title',
					'editor',
					'thumbnail',
					'comments',
					'revisions',
					'alphabetic-listing'
				),
				'rewrite'     => array(
					'slug' => 'beer'
				),
				'has_archive' => 'beers'
			)
		);

	}

	function register_post_meta() {

		$dabc_box = $this->titan->createMetaBox( array(
			'name'      => 'DABC Beer Info',
			'id'        => 'dabc-beer-info',
			'post_type' => self::POST_TYPE
		) );

		$dabc_box->createOption( array(
			'name' => 'Description',
			'id'   => self::DABC_NAME_OPTION,
			'desc' => 'The original description from the DABC'
		) );

		$dabc_box->createOption( array(
			'name' => 'CS Code',
			'id'   => self::CS_CODE_OPTION,
			'desc' => 'The DABC\'s SKU for this beer'
		) );

		$dabc_box->createOption( array(
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

		register_taxonomy( self::STYLE_TAXONOMY, self::POST_TYPE, array(
			'labels'	 => array(
				'name'                       => 'Styles',
				'singular_name'              => 'Styles',
				'search_items'               => 'Search Styles',
				'popular_items'              => 'Popular Styles',
				'all_items'                  => 'Styles',
				'edit_item'                  => 'Edit Style',
				'view_item'                  => 'View Style',
				'update_item'                => 'Update Style',
				'add_new_item'               => 'Add New Style',
				'new_item_name'              => 'New Style Name',
				'separate_items_with_commas' => 'Separate styles with commas',
				'add_or_remove_items'        => 'Add or remove styles',
				'choose_from_most_used'      => 'Choose from the most used styles',
				'not_found'                  => 'No styles found.',
				'menu_name'                  => 'Styles',
				'name_admin_bar'             => 'Styles'
			),
			'rewrite'	 => array(
				'slug' => 'style'
			)
		) );

		register_taxonomy( self::BREWERY_TAXONOMY, self::POST_TYPE, array(
			'labels'	 => array(
				'name'                       => 'Breweries',
				'singular_name'              => 'Breweries',
				'search_items'               => 'Search Breweries',
				'popular_items'              => 'Popular Breweries',
				'all_items'                  => 'Breweries',
				'edit_item'                  => 'Edit Brewery',
				'view_item'                  => 'View Brewery',
				'update_item'                => 'Update Brewery',
				'add_new_item'               => 'Add New Brewery',
				'new_item_name'              => 'New Brewery Name',
				'separate_items_with_commas' => 'Separate breweries with commas',
				'add_or_remove_items'        => 'Add or remove breweries',
				'choose_from_most_used'      => 'Choose from the most used breweries',
				'not_found'                  => 'No breweries found.',
				'menu_name'                  => 'Breweries',
				'name_admin_bar'             => 'Breweries'
			),
			'rewrite'	 => array(
				'slug' => 'brewery'
			)
		) );

		register_taxonomy( self::STATE_TAXONOMY, self::POST_TYPE, array(
			'label' => 'State'
		) );

		register_taxonomy( self::COUNTRY_TAXONOMY, self::POST_TYPE, array(
			'label' => 'Country'
		) );

	}

	function add_post_columns() {

		Jigsaw::add_column( self::POST_TYPE, 'DABC ID', array( $this, 'display_dabc_id_column' ) );

		Jigsaw::add_column( self::POST_TYPE, 'Price', array( $this, 'display_price_column' ) );

	}

	function display_dabc_id_column( $post_id ) {

		echo $this->titan->getOption( self::CS_CODE_OPTION, $post_id );

	}

	function display_price_column( $post_id ) {

		echo $this->titan->getOption( self::PRICE_OPTION, $post_id );

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

	/**
	 * Create a DABC Beer post, including meta and taxonomy terms
	 *
	 * expected $beer_info structure:
	 *	array(
	 *		"name" => "Rogue Dead Guy Ale"
     *		"description" => "ROGUE DEAD GUY ALE 355ml",
     *		"div" => "T"
     *		"dept" => "TN"
     *		"cat" => "TNC"
     *		"size" => "355"
     *		"cs_code" => "904164"
     *		"price" => "2.54"
     *		"status" => "1"
     *		"spa_on" => " "
	 *  )
	 *
	 * @param array $beer_info
	 */
	function create_beer( $beer_info ) {

		$post_data = array(
			'post_type'   => self::POST_TYPE,
			'post_title'  => $beer_info['name'],
			'post_status' => 'publish'
		);

		$post_id = wp_insert_post( $post_data );

		$titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

		$titan->setOption( self::DABC_NAME_OPTION, $beer_info['description'], $post_id );

		$titan->setOption( self::CS_CODE_OPTION, $beer_info['cs_code'], $post_id );

		$titan->setOption( self::PRICE_OPTION, $beer_info['price'], $post_id );

		$taxonomy_map = array(
			'dept'   => self::DEPT_TAXONOMY,
			'cat'    => self::CAT_TAXONOMY,
			'size'   => self::SIZE_TAXONOMY,
			'status' => self::STATUS_TAXONOMY
		);

		foreach ( $taxonomy_map as $info_key => $taxonomy ) {

			wp_set_object_terms( $post_id, $beer_info[$info_key], $taxonomy );

		}

		return $post_id;

	}

	/**
	 * Sync post type with DABC site data
	 */
	function sync_beers_with_dabc() {

		$beers = $this->dabc_sync->get_beer_list_from_dabc();

		if ( is_wp_error( $beers ) ) {

			// reschedule job if connection timeout ?
			return;

		}

		foreach ( $beers as $beer_info ) {

			$existing_beer = $this->get_beer_by_cs_code( $beer_info['cs_code'] );

			if ( ! $existing_beer ) {

				$this->create_beer( $beer_info );

			}

		}

	}

	/**
	 * Get DABC CS Code for given Beer post ID
	 *
	 * @param int $post_id beer post ID
	 * @return string $cs_code DABC CS Code
	 */
	function get_cs_code( $post_id ) {

		return $this->titan->getOption( self::CS_CODE_OPTION, $post_id );

	}

	/**
	 * Schedule beer sync with DABC
	 */
	function schedule_jobs() {

		wp_schedule_event( time(), 'twicedaily', self::DABC_SYNC_CRON );

	}

	/**
	 * Store beer inventory information
	 *
	 * @param int $beer_post_id
	 * @param array $inventory
	 * @return int|bool
	 */
	function set_beer_inventory( $beer_post_id, $inventory ) {

		$data = array(
			'last_updated' => date( 'Y-m-d H:i:s' ),
			'inventory'    => $inventory
		);

		return update_post_meta( $beer_post_id, self::DABC_INVENTORY, $data );

	}

	/**
	 * Get stored beer inventory
	 *
	 * @param int $beer_post_id
	 * @return mixed
	 */
	function get_beer_inventory( $beer_post_id ) {

		$inventory = get_post_meta( $beer_post_id, self::DABC_INVENTORY, true );

		return $inventory;

	}

	function _get_inventory_meta( $post_id ) {

		return get_post_meta( $post_id, self::DABC_INVENTORY, true );

	}

	function get_inventory( $post_id ) {

		$inventory = $this->_get_inventory_meta( $post_id );

		return isset( $inventory['inventory'] ) ? $inventory['inventory'] : false;

	}

	function get_inventory_last_updated( $post_id ) {

		$inventory = $this->_get_inventory_meta( $post_id );

		return isset( $inventory['last_updated'] ) ? $inventory['last_updated'] : false;

	}

	function get_quantity_for_store( $post_id, $store_number ) {

		$inventory = $this->get_inventory( $post_id );

		if ( $inventory && isset( $inventory[$store_number] ) ) {

			return $inventory[$store_number];

		}

		return false;

	}

	/**
	 * Use Untappd data to set taxonomy terms for the beer
	 *
	 * @param int $post_id
	 * @param object $beer_info
	 */
	function set_taxonomy_data_from_untappd( $post_id, $beer_info ) {

		if ( isset( $beer_info->beer_style ) ) {

			wp_set_object_terms( $post_id, $beer_info->beer_style, self::STYLE_TAXONOMY );

		}

		if ( isset( $beer_info->brewery->brewery_name ) ) {

			wp_set_object_terms( $post_id, $beer_info->brewery->brewery_name, self::BREWERY_TAXONOMY );

		}

		if ( isset( $beer_info->brewery->country_name ) ) {

			wp_set_object_terms( $post_id, $beer_info->brewery->country_name, self::COUNTRY_TAXONOMY );

		}

		if ( isset( $beer_info->brewery->location->brewery_state ) ) {

			wp_set_object_terms( $post_id, $beer_info->brewery->location->brewery_state, self::STATE_TAXONOMY );

		}

	}

}

