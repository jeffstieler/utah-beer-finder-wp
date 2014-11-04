<?php
/**
 * Implement the Beer post type
 *
 * @package Brewtah
 */
use Symfony\Component\DomCrawler\Crawler;

/**
 * Include beer service syncing
 */
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
	const DABC_URL_BASE          = 'http://www.webapps.abc.utah.gov/Production';
	const DABC_BEER_LIST_URL     = '/OnlinePriceList/DisplayPriceList.aspx?DivCd=T';
	const DABC_INVENTORY_URL     = '/OnlineInventoryQuery/IQ/InventoryQuery.aspx';

	var $titan;
	var $dabc_column_map;
	var $dabc_status_map;
	var $ratebeer_sync;
	var $untappd_sync;

	function __construct() {

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

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

		$this->dabc_status_map = array(
			'1' => 'General Distribution',
			'D' => 'Discontinued General Item',
			'S' => 'Special Order',
			'L' => 'Regular Limited Item',
			'X' => 'Limited Discontinued',
			'N' => 'Unavailable General Item',
			'A' => 'Limited Allocated Product',
			'U' => 'Unavailable Limited Item',
			'T' => 'Trial'
		);

		$this->ratebeer_sync = new Ratebeer_Sync( self::POST_TYPE );

		$this->untappd_sync  = new Untappd_Sync( self::POST_TYPE );

	}

	function attach_hooks() {

		add_action( 'untappd_sync_post_beer_info', array( $this, 'set_taxonomy_data_from_untappd' ), 10, 2 );

	}

	function init() {

		$this->attach_hooks();

		$this->register_post_type();

		$this->register_post_meta();

		$this->register_taxonomies();

		$this->add_post_columns();

		$this->ratebeer_sync->init();

		$this->untappd_sync->init();

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
			'label' => 'Style'
		) );

		register_taxonomy( self::BREWERY_TAXONOMY, self::POST_TYPE, array(
			'label' => 'Brewery'
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
	 * Beautify common ugliness in DABC beer descriptions
	 *
	 * @param string $beer_name original beer description from the DABC
	 * @return string Prettier beer name
	 */
	function pretty_up_beer_name( $beer_name ) {

		// remove size from end of description
		// "BEER NAME         355ml" => "BEER NAME"
		$beer_name = trim( preg_replace( '/\d+ml$/', '', $beer_name ) );

		// BEER NAME => Beer Name
		$beer_name = ucwords( strtolower( $beer_name ) );

		// set abbreviations back to all caps (IPA, IPL, etc)
		$beer_name = strtr( $beer_name, array(
			'Ipa' => 'IPA',
			'Ipl' => 'IPL',
			'Esb' => 'ESB',
			'Apa' => 'APA'
		) );

		return $beer_name;

	}

	/**
	 * HTTP request helper
	 *
	 * @param string $url URL to request
	 * @param array $args wp_remote_request() arguments
	 * @return boolean|WP_Error|string - bool on non-200, WP_Error on error, string body on 200
	 */
	function _make_http_request( $url, $args = array() ) {

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {

			return $response;

		} else if ( 200 === wp_remote_retrieve_response_code( $response ) ) {

			return wp_remote_retrieve_body( $response );

		}

		return false;

	}

	function get_beer_list_from_dabc() {

		$result = $this->_make_http_request( self::DABC_URL_BASE . self::DABC_BEER_LIST_URL );

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

			$beer['name'] = $this->pretty_up_beer_name( $beer['description'] );

		}

		return $beer;

	}

	function parse_dabc_beer_list( $html ) {

		$crawler = new Crawler( $html );

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

		// map status code to label before creating taxonomy terms
		$status = $beer_info['status'];
		$beer_info['status'] = isset( $this->dabc_status_map[$status] ) ? $this->dabc_status_map[$status] : 'N/A';

		foreach ( $taxonomy_map as $info_key => $taxonomy ) {

			wp_set_object_terms( $post_id, $beer_info[$info_key], $taxonomy );

		}

		return $post_id;

	}

	/**
	 * Sync post type with DABC site data
	 */
	function sync_beers_with_dabc() {

		$result = $this->get_beer_list_from_dabc();

		if ( is_wp_error( $result ) ) {

			// reschedule job if connection timeout ?
			return;

		}

		$beers = $this->parse_dabc_beer_list( $result );

		foreach ( $beers as $beer_info ) {

			$existing_beer = $this->get_beer_by_cs_code( $beer_info['cs_code'] );

			if ( ! $existing_beer ) {

				$this->create_beer( $beer_info );

			}

		}

	}

	/**
	 * Grab the __VIEWSTATE and __EVENTVALIDATION hidden inputs from
	 * a block of HTML containing an ASP.Net form
	 *
	 * @param string $html HTML containing an ASP.Net form
	 * @return bool|array false on error, array of viewstate and validation field values on success
	 */
	function parse_required_asp_form_fields( $html ) {

		$crawler    = new Crawler( $html );

		$viewstate  = $crawler->filter( '#__VIEWSTATE' );

		$validation = $crawler->filter( '#__EVENTVALIDATION' );

		if ( iterator_count( $viewstate ) && iterator_count( $validation ) ) {

			$form = array(
				'__VIEWSTATE'       => $viewstate->attr( 'value' ),
				'__EVENTVALIDATION' => $validation->attr( 'value' )
			);

			return $form;

		}

		return false;

	}

	/**
	 * POST the DABC inventory search form
	 *
	 * @param string $cs_code DABC beer SKU
	 * @param array $session_values __VIEWSTATE and __EVENTVALIDATION
	 * @return bool|WP_Error|string see _make_http_request()
	 */
	function submit_dabc_inventory_form( $cs_code, $session_values ) {

		$body = array_merge(
			$session_values,
			array(
				'__ASYNCPOST' => 'true',
				'ctl00$ContentPlaceHolderBody$tbCscCode' => $cs_code
			)
		);

		$result = $this->_make_http_request(
			self::DABC_URL_BASE . self::DABC_INVENTORY_URL,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
					'User-Agent'   => 'Mozilla'
				),
				'body'    => $body,
				'timeout' => 10
			)
		);

		return $result;

	}

	/**
	 * Parse out the Store Number and Quantity from a DABC Inventory table row
	 *
	 * @param Crawler $row
	 * @return boolean|array false on parsing error, array of store/qty on success
	 */
	function parse_dabc_inventory_search_result_row( $row ) {

		$cols = $row->filter( 'td' );

		if ( iterator_count( $cols ) ) {

			$store    = preg_replace( '/^0+/', '', $cols->first()->text() );

			$quantity = (int) $cols->eq( 2 )->text();

			return compact( 'store', 'quantity' );

		}

		return false;

	}

	/**
	 * Parse the HTML response from searching DABC inventory
	 *
	 * @param string $html
	 * @return array list of store numbers and beer quantities
	 */
	function parse_dabc_inventory_search_response( $html ) {

		$crawler   = new Crawler( $html );

		$inventory = array();

		$rows      = $crawler->filter( '#ContentPlaceHolderBody_gvInventoryDetails tr.gridViewRow' );

		if ( iterator_count( $rows ) ) {

			$inventory_data = $rows->each( function( Crawler $row ) {
				return $this->parse_dabc_inventory_search_result_row( $row );
			} );

			$inventory_data = array_filter( $inventory_data );

			/**
			 * Switch inventory to array of [ store # => quantity ]
			 */
			foreach ( $inventory_data as $inventory_info ) {

				$inventory[$inventory_info['store']] = $inventory_info['quantity'];

			}

		}

		return $inventory;

	}

	/**
	 * Get store inventories for a given CS Code
	 *
	 * @param string $cs_code DABC Beer SKU
	 * @return boolean|array false on failure, array of store inventories on success
	 */
	function search_dabc_inventory_for_cs_code( $cs_code ) {

		$url    = self::DABC_URL_BASE . self::DABC_INVENTORY_URL;

		$result = $this->_make_http_request( $url );

		if ( ( false === $result ) || is_wp_error( $result ) ) {

			return false;

		}

		$form = $this->parse_required_asp_form_fields( $result );

		if ( false === $form ) {

			return false;

		}

		$result = $this->submit_dabc_inventory_form( $cs_code, $form );

		if ( ( false === $result ) || is_wp_error( $result ) ) {

			return false;

		}

		$inventory = $this->parse_dabc_inventory_search_response( $result );

		return $inventory;

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

