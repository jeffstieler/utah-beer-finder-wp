<?php
/**
 * Implement the Beer post type
 *
 * @package Brewtah
 */
use Symfony\Component\DomCrawler\Crawler;

/**
 * Include Ratebeer syncing
 */
require_once( __DIR__ . '/services/ratebeer.php' );

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
	const RATEBEER_SEARCHED      = 'has-ratebeer-searched';
	const RATEBEER_MAP_CRON      = 'map_ratebeer';
	const RATEBEER_SYNC_CRON     = 'sync_ratebeer';
	const RATEBEER_SYNCED        = 'has-ratebeer-sync';
	const UNTAPPD_SEARCHED       = 'has-untappd-searched';
	const UNTAPPD_MAP_CRON       = 'map_untappd';
	const UNTAPPD_SYNC_CRON      = 'sync_untappd';
	const UNTAPPD_IMAGE_CRON     = 'image_untappd';
	const UNTAPPD_ID             = 'untappd-id';
	const UNTAPPD_RATING_SCORE   = 'untappd-rating-score';
	const UNTAPPD_RATING_COUNT   = 'untappd-rating-count';
	const UNTAPPD_ABV            = 'untappd-abv';
	const UNTAPPD_HIT_LIMIT      = 'untappd-hit-limit';
	const UNTAPPD_SYNCED         = 'has-untappd-sync';
	const UNTAPPD_IMG_SEARCHED   = 'has-untappd-image';
	const DABC_URL_BASE          = 'http://www.webapps.abc.utah.gov/Production';
	const DABC_BEER_LIST_URL     = '/OnlinePriceList/DisplayPriceList.aspx?DivCd=T';
	const DABC_INVENTORY_URL     = '/OnlineInventoryQuery/IQ/InventoryQuery.aspx';

	var $titan;
	var $dabc_column_map;
	var $dabc_status_map;

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

	}

	function init() {

		$this->register_post_type();

		$this->register_post_meta();

		$this->register_taxonomies();

		$this->attach_hooks();

		$this->add_post_columns();

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

		$rb_box = $this->titan->createMetaBox( array(
			'name'      => 'Ratebeer Info',
			'id'        => 'ratebeer-info',
			'post_type' => self::POST_TYPE
		) );

		$rb_box->createOption( array(
			'name' => 'ID',
			'id'   => self::RATEBEER_ID
		) );

		$rb_box->createOption( array(
			'name' => 'URL',
			'id'   => self::RATEBEER_URL_OPTION
		) );

		$rb_box->createOption( array(
			'name' => 'Overall Score',
			'id'   => self::RATEBEER_OVERALL_SCORE
		) );

		$rb_box->createOption( array(
			'name' => 'Style Score',
			'id'   => self::RATEBEER_STYLE_SCORE
		) );

		$rb_box->createOption( array(
			'name' => 'Calories',
			'id'   => self::RATEBEER_CALORIES
		) );

		$rb_box->createOption( array(
			'name' => 'ABV',
			'id'   => self::RATEBEER_ABV
		) );

		$untappd_box = $this->titan->createMetaBox( array(
			'name'      => 'Untappd Info',
			'id'        => 'untappd-info',
			'post_type' => self::POST_TYPE
		) );

		$untappd_box->createOption( array(
			'name' => 'ID',
			'id'   => self::UNTAPPD_ID
		) );

		$untappd_box->createOption( array(
			'name' => 'Rating Score',
			'id'   => self::UNTAPPD_RATING_SCORE
		) );

		$untappd_box->createOption( array(
			'name' => 'Ratings Count',
			'id'   => self::UNTAPPD_RATING_COUNT
		) );

		$untappd_box->createOption( array(
			'name' => 'ABV',
			'id'   => self::UNTAPPD_ABV
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

	function attach_hooks() {

		add_action( self::RATEBEER_MAP_CRON, array( $this, 'cron_map_dabc_beer_to_ratebeer' ) );

		add_action( self::RATEBEER_SYNC_CRON, array( $this, 'cron_sync_dabc_beer_with_ratebeer' ) );

		add_action( self::UNTAPPD_IMAGE_CRON, array( $this, 'sync_featured_image_with_untappd' ), 10, 2 );

		add_action( self::UNTAPPD_MAP_CRON, array( $this, 'cron_map_dabc_beer_to_untappd' ) );

		add_action( 'update_postmeta', array( $this, 'sync_ratebeer_on_url_update' ), 10, 4 );

		add_action( 'add_post_meta', array( $this, 'sync_ratebeer_on_url_add' ), 10, 3 );

	}

	function add_post_columns() {

		Jigsaw::add_column( self::POST_TYPE, 'DABC ID', array( $this, 'display_dabc_id_column' ) );

		Jigsaw::add_column( self::POST_TYPE, 'Price', array( $this, 'display_price_column' ) );

		Jigsaw::add_column( self::POST_TYPE, 'Ratebeer URL', array( $this, 'display_ratebeer_url_column' ) );

		Jigsaw::add_column( self::POST_TYPE, 'Overall', array( $this, 'display_ratebeer_overall_score_column' ) );

		Jigsaw::add_column( self::POST_TYPE, 'Style', array( $this, 'display_ratebeer_style_score_column' ) );

		Jigsaw::add_column( self::POST_TYPE, 'Calories', array( $this, 'display_ratebeer_calories_column' ) );

		Jigsaw::add_column( self::POST_TYPE, 'ABV', array( $this, 'display_ratebeer_abv_column' ) );

	}

	function display_dabc_id_column( $post_id ) {

		echo $this->titan->getOption( self::CS_CODE_OPTION, $post_id );

	}

	function display_price_column( $post_id ) {

		echo $this->titan->getOption( self::PRICE_OPTION, $post_id );

	}

	function display_ratebeer_url_column( $post_id ) {

		echo $this->titan->getOption( self::RATEBEER_URL_OPTION, $post_id );

	}

	function display_ratebeer_overall_score_column( $post_id ) {

		echo $this->titan->getOption( self::RATEBEER_OVERALL_SCORE, $post_id );

	}

	function display_ratebeer_style_score_column( $post_id ) {

		echo $this->titan->getOption( self::RATEBEER_STYLE_SCORE, $post_id );

	}

	function display_ratebeer_calories_column( $post_id ) {

		echo $this->titan->getOption( self::RATEBEER_CALORIES, $post_id );

	}

	function display_ratebeer_abv_column( $post_id ) {

		echo $this->titan->getOption( self::RATEBEER_ABV, $post_id );

	}

	function get_titan_meta_key( $option_name ) {

		return ( self::TITAN_NAMESPACE . '_' . $option_name );

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
	 * Make search request to Ratebeer
	 *
	 * @param string $query - search query for ratebeer
	 * @return bool|WP_Error|string boolean false if non 200, WP_Error on request error, HTML string on success
	 */
	function ratebeer_search_request( $query ) {

		$result = $this->_make_http_request(
			self::RATEBEER_BASE_URL . '/findbeer.asp',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded'
				),
				'body' => array(
					'BeerName' => $query
				),
				'timeout' => 10
			)
		);

		return $result;

	}

	/**
	 * Callback to process rows from parse_ratebeer_response() and map
	 * table columns to beer info array keys
	 *
	 * @param \Symfony\Component\DomCrawler\Crawler $row
	 * @return array beer information from ratebeer
	 */
	function parse_ratebeer_search_results_table_row( Crawler $row ) {

		$beer = false;

		$cols = $row->filter( 'td' );

		if ( iterator_count( $cols ) ) {

			$beer = array();

			foreach ( $this->ratebeer_search_column_map as $i => $key ) {

				$column_text = $cols->eq( $i )->text();

				$beer[$key] = trim( str_replace( "\xc2\xa0", ' ', $column_text ) );

			}

			$beer['url'] = $cols->eq( 0 )->filter( 'a' )->attr( 'href' );

			$id_pattern_matches = array();

			preg_match( '/\/(\d+)\/$/', $beer['url'], $id_pattern_matches );

			$beer['id'] = $id_pattern_matches[1];

		}

		return $beer;

	}

	/**
	 * Produce an array of beers from Ratebeer search results page markup
	 *
	 * @param string $html HTML search results from Ratebeer
	 * @return array beers found in Ratebeer html response
	 */
	function parse_ratebeer_search_response( $html ) {

		$beers = array();

		$crawler = new Crawler( $html );

		$no_results = $crawler->filter( 'span.greenbeerhed' );

		// when there are no results, a "Search Tips" box displays
		if ( iterator_count( $no_results ) && ( 'SEARCH TIPS' === $no_results->text() ) ) {

			return $beers;

		}

		// only the "beers" result table has a row with bgcolor specified (F0F0F0)
		$result_rows = $crawler->filter( 'table.results tr[bgcolor] ~ tr' );

		if ( iterator_count( $result_rows ) ) {

			$beers = $result_rows->each( function( Crawler $row ) {

				return $this->parse_ratebeer_search_results_table_row( $row );

			} );

		}

		return $beers;

	}

	/**
	 * Search Ratebeer for beer(s), filtering out aliased beers
	 *
	 * @param string $query search query
	 * @return boolean|array boolean false on error, array of beers on success
	 */
	function search_ratebeer( $query ) {

		$response = $this->ratebeer_search_request( $query );

		if ( ( false === $response ) || is_wp_error( $response ) ) {

			return false;

		}

		$beers = $this->parse_ratebeer_search_response( $response );

		$beers = array_filter( $beers, function( $beer ) {

			return ( 'A' !== $beer['status'] );

		} );

		return $beers;

	}

	/**
	 * Flag a beer as having attempted to be mapped with Ratebeer
	 * NOTE: many won't be found and we don't want to keep looking
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_beer_as_ratebeer_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, self::RATEBEER_SEARCHED, true );

	}

	/**
	 * For a given DABC beer post ID, search ratebeer for it
	 * and associate the URL if found
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function map_dabc_beer_to_ratebeer( $post_id ) {

		$post = get_post( $post_id );

		$beer_name = $post->post_title;

		$search_results = $this->search_ratebeer( $beer_name );

		if ( is_array( $search_results ) ) {

			$beer = array_shift( $search_results );

			if ( $beer ) {

				$titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

				$titan->setOption( self::RATEBEER_URL_OPTION, $beer['url'], $post_id );

				$titan->setOption( self::RATEBEER_ID, $beer['id'], $post_id );

			}

			return true;

		}

		return false;

	}

	/**
	 * Find all beers that haven't been searched for on Ratebeer
	 * successfully and schedule a cron job to map them
	 */
	function search_beers_on_ratebeer() {

		$unmapped_beers = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => self::RATEBEER_SEARCHED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		array_map( array( $this, 'schedule_ratebeer_search_for_beer' ), $unmapped_beers->posts );

	}

	/**
	 * Schedule a job to search a single beer on Ratebeer
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_ratebeer_search_for_beer( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::RATEBEER_MAP_CRON, array( $post_id ) );

	}

	/**
	 * WP-Cron hook callback for searching a beer on Ratebeer
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_map_dabc_beer_to_ratebeer( $post_id ) {

		$success = $this->map_dabc_beer_to_ratebeer( $post_id );

		if ( $success ) {

			$this->mark_beer_as_ratebeer_searched( $post_id );

		} else {

			$this->schedule_ratebeer_search_for_beer( $post_id, 10 );

		}

	}

	/**
	 * Flag a beer as having been synced with Ratebeer (single beer page)
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_beer_as_ratebeer_synced( $post_id ) {

		return (bool) update_post_meta( $post_id, self::RATEBEER_SYNCED, true );

	}

	/**
	 * Retrieve info and ratings from Ratebeer for beers that have been mapped
	 */
	function sync_beers_with_ratebeer() {

		$unsynced_beers = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => self::RATEBEER_SYNCED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		foreach ( $unsynced_beers->posts as $post_id ) {

			if ( $this->titan->getOption( self::RATEBEER_URL_OPTION, $post_id ) ) {

				$this->schedule_ratebeer_sync_for_beer( $post_id );

			}

		}

	}

	/**
	 * Schedule a job to sync a single beer with Ratebeer
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_ratebeer_sync_for_beer( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::RATEBEER_SYNC_CRON, array( $post_id ) );

	}

	/**
	 * WP-Cron hook callback for syncing a beer with Ratebeer
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_sync_dabc_beer_with_ratebeer( $post_id ) {

		$success = $this->sync_dabc_beer_with_ratebeer( $post_id );

		if ( $success ) {

			$this->mark_beer_as_ratebeer_synced( $post_id );

		} else {

			$this->schedule_ratebeer_sync_for_beer( $post_id, 10 );

		}

	}

	/**
	 * For a given DABC beer post ID, sync date with ratebeer
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function sync_dabc_beer_with_ratebeer( $post_id ) {

		$beer_path = $this->titan->getOption( self::RATEBEER_URL_OPTION, $post_id );

		$beer_info = $this->sync_ratebeer( $beer_path );

		if ( is_array( $beer_info ) && $beer_info ) {

			$this->titan->setOption( self::RATEBEER_OVERALL_SCORE, $beer_info['overall_score'], $post_id );

			$this->titan->setOption( self::RATEBEER_STYLE_SCORE, $beer_info['style_score'], $post_id );

			$this->titan->setOption( self::RATEBEER_ABV, $beer_info['abv'], $post_id );

			$this->titan->setOption( self::RATEBEER_CALORIES, $beer_info['calories'], $post_id );

			if ( ! empty( $beer_info['description'] ) ) {

				wp_update_post( array(
					'ID'           => $post_id,
					'post_content' => $beer_info['description']
				) );

			}

			return true;

		}

		return false;

	}

	/**
	 * Sync Ratebeer
	 *
	 * @param string $path beer path on Ratebeer
	 * @return boolean|array boolean false on error, array of beer info on success
	 */
	function sync_ratebeer( $path ) {

		$response = $this->ratebeer_sync_request( $path );

		if ( ( false === $response ) || is_wp_error( $response ) ) {

			return false;

		}

		$info = $this->parse_ratebeer_sync_response( $response );

		return $info;

	}

	/**
	 * Make sync request to Ratebeer
	 *
	 * @param string $path - beer path on ratebeer
	 * @return bool|WP_Error|string boolean false if non 200, WP_Error on request error, HTML string on success
	 */
	function ratebeer_sync_request( $path ) {

		$result = $this->_make_http_request(
			self::RATEBEER_BASE_URL . $path,
			array(
				'timeout' => 10
			)
		);

		return $result;

	}

	/**
	 * Produce an array of beer info from Ratebeer single beer page markup
	 *
	 * @param string $html HTML beer page from Ratebeer
	 * @return array beer info found in Ratebeer html response
	 */
	function parse_ratebeer_sync_response( $html ) {

		$crawler = new Crawler( $html );

		// overall score
		$overall_score = $crawler->filter( 'span[itemprop="rating"] span:not([style])' );

		$overall_score = iterator_count( $overall_score ) ? $overall_score->text() : 'N/A';

		// style score
		$style_score   = $crawler->filter( 'span[itemprop="average"]' );

		$style_score   = iterator_count( $style_score ) ? $style_score->text() : 'N/A';

		// commerical description
		$description   = $crawler->filter( 'td[width=650] > div > div > div' );

		$description   = iterator_count( $description ) ? $description->last()->text() : '';

		$description   = preg_replace( '/^COMMERCIAL DESCRIPTION/', '', $description );

		// "info" bar: ratings, weighted avg, calories, abv
		$info     = $crawler->filter( 'td[width=650] > div > div > small > big' );
		$calories = '';
		$abv      = '';

		if ( $info_count = iterator_count( $info ) ) {

			// calories (per 12oz)
			$calories = $info->eq( $info_count - 2 )->text();

			// abv %
			$abv = $info->last()->text();

		}

		$beer_info = compact( 'overall_score', 'style_score', 'description', 'calories', 'abv' );

		return $beer_info;

	}

	/**
	 * Build a beer image URL from it's Ratebeer ID
	 *
	 * @param int $id
	 * @return string URL for Ratebeer image
	 */
	function get_ratebeer_image_url( $id ) {

		return sprintf( self::RATEBEER_IMGURL_FORMAT, $id );

	}

	/**
	 * Schedule a job to download a beer image from Untappd
	 *
	 * @param sting $image_url
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_untappd_image_sync_for_beer( $image_url, $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::UNTAPPD_IMAGE_CRON, array( $image_url, $post_id ) );

	}

	/**
	 * Download a beer's image from Untappd and set as it's featured image
	 *
	 * @param int $image_url
	 * @param int $post_id
	 */
	function sync_featured_image_with_untappd( $image_url, $post_id ) {

		if ( ! function_exists( 'media_sideload_image' ) ) {

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/media.php' );

			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/file.php' );

		}

		$result = media_sideload_image( $image_url, $post_id );

		if ( is_wp_error( $result ) ) {

			$this->schedule_untappd_image_sync_for_beer( $image_url, $post_id, 10 );

		} else {

			$images = get_attached_media( 'image', $post_id );

			$thumbnail = array_shift( $images );

			if ( ! is_null( $thumbnail ) ) {

				set_post_thumbnail( $post_id, $thumbnail->ID );

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

	/**
	 * Schedule a Ratebeer sync if the URL changes for a beer
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 */
	function sync_ratebeer_on_url_update( $meta_id, $object_id, $meta_key, $meta_value ) {

		$this->sync_ratebeer_on_url_add( $object_id, $meta_key, $meta_value );

	}

	/**
	 * Schedule a Ratebeer sync if a URL is added for a beer
	 *
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 */
	function sync_ratebeer_on_url_add( $object_id, $meta_key, $meta_value ) {

		$ratebeer_url_key = $this->get_titan_meta_key( self::RATEBEER_URL_OPTION );

		if ( ( $ratebeer_url_key === $meta_key ) && !empty( $meta_value ) ) {

			$this->schedule_ratebeer_sync_for_beer( $object_id );

		}

	}

	function get_overall_rating( $post_id ) {

		return $this->titan->getOption( self::RATEBEER_OVERALL_SCORE, $post_id );

	}

	function get_style_rating( $post_id ) {

		return $this->titan->getOption( self::RATEBEER_STYLE_SCORE, $post_id );

	}

	function get_calories( $post_id ) {

		return $this->titan->getOption( self::RATEBEER_CALORIES, $post_id );

	}

	function get_abv( $post_id ) {

		return $this->titan->getOption( self::RATEBEER_ABV, $post_id );

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
	 * Set a flag that we've hit the Untappd API rate limit
	 */
	function set_hit_untappd_rate_limit() {

		set_transient( self::UNTAPPD_HIT_LIMIT, true, HOUR_IN_SECONDS );

	}

	/**
	 * Have we hit the Untappd API rate limit?
	 *
	 * @return bool
	 */
	function have_hit_untappd_rate_limit() {

		return get_transient( self::UNTAPPD_HIT_LIMIT );

	}

	/**
	 * Search for beers on Untappd
	 *
	 * @param string $query
	 * @return bool|WP_Error|array
	 */
	function search_untappd( $query ) {

		if (
			( false === defined( 'UNTAPPD_CLIENT_ID' ) ) ||
			( false === defined( 'UNTAPPD_CLIENT_SECRET' ) ) ||
			$this->have_hit_untappd_rate_limit()
		) {

			return false;

		}

		$url = add_query_arg(
			array(
				'q'             => urlencode( $query ),
				'sort'          => 'count',
				'client_id'     => UNTAPPD_CLIENT_ID,
				'client_secret' => UNTAPPD_CLIENT_SECRET
			),
			'https://api.untappd.com/v4/search/beer'
		);

		$response = wp_remote_request( $url );

		if ( is_wp_error( $response ) ) {

			return false;

		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$body_data     = json_decode( $response_body );

		// handle rate limit
		if ( 500 === $response_code ) {

			if (
				isset( $body_data->meta->error_type ) &&
				( 'invalid_limit' === $body_data->meta->error_type )
			) {
				$this->set_hit_untappd_rate_limit();
			}

		} else if ( ( 200 === $response_code ) && isset( $body_data->response->beers->items ) ) {

			return $body_data->response->beers->items;

		}

		return false;

	}

	/**
	 * Get beer info for a given Untappd BID
	 *
	 * @param int $beer_id
	 * @return booean|object false on failure, beer object on success
	 */
	function get_untappd_beer_info( $beer_id ) {

		// mock response for now..

		$response_body = <<<EOB
{
	"response":{
      "beer":{
         "bid":3558,
         "beer_name":"Brooklyn Lager",
         "beer_label":"https://untappd.s3.amazonaws.com/site/beer_logos/beer-BrooklynLager_3558.jpeg",
         "beer_abv":5.2,
         "beer_description":"Brooklyn Lager is a wonderfully flavorful beer, smooth, refreshing and very versatile with food. It is amber-gold in color and displays a firm malt center supported by a refreshing bitterness and floral hop aroma. Caramel malts show in the finish. The aromatic qualities of the beer are enhanced by “dry-hopping”, the centuries-old practice of steeping the beer with fresh hops as it undergoes a long, cold maturation.",
         "beer_style":"Vienna Lager",
         "is_in_production":1,
         "beer_slug":"brooklyn-brewery-brooklyn-lager",
         "is_homebrew":0,
         "created_at":"Sat, 21 Aug 2010 07:26:35 +0000",
         "rating_count":4757,
         "rating_score":3.42,
         "stats":{
            "total_count":12682,
            "monthly_count":1333,
            "user_count":24,
            "total_user_count":7332
         },
         "brewery":{
            "brewery_id":259,
            "brewery_name":"Brooklyn Brewery",
            "brewery_label":"https://untappd.s3.amazonaws.com/site/brewery_logos/brewery-BrooklynBrewery_259.jpeg",
            "country_name":"United States",
            "contact":{
               "twitter":"brooklynbrewery",
               "facebook":"https://www.facebook.com/thebrooklynbrewery",
               "url":"http://brooklynbrewery.com"
            },
            "location":{
               "brewery_city":"Brooklyn",
               "brewery_state":"NY",
               "lat":40.7215,
               "lng":-73.9575
            }
         },
         "auth_rating":3,
         "wish_list":false,
         "media":{
            "count":10,
            "items":[
               {
                  "photo_id":738199,
                  "photo":{
                     "photo_img_sm":"https://untappd.s3.amazonaws.com/photo/2012_07_29/6520952d6e365dd8d32dda3151a38466_100x100.jpg",
                     "photo_img_md":"https://untappd.s3.amazonaws.com/photo/2012_07_29/6520952d6e365dd8d32dda3151a38466_320x320.jpg",
                     "photo_img_lg":"https://untappd.s3.amazonaws.com/photo/2012_07_29/6520952d6e365dd8d32dda3151a38466_640x640.jpg",
                     "photo_img_og":"https://untappd.s3.amazonaws.com/photo/2012_07_29/6520952d6e365dd8d32dda3151a38466_raw.jpg"
                  }
				}
			]
          },
          "checkins": {}
         }
     },
     "meta": {}
}
EOB;

		$body_data = json_decode( $response_body );

		if ( isset( $body_data->response->beer ) ) {

			return $body_data->response->beer;

		}

		return false;

	}

	/**
	 * For a given DABC beer post ID, search Untappd and associate ID if found
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function map_dabc_beer_to_untappd( $post_id ) {

		$post = get_post( $post_id );

		$beer_name = $post->post_title;

		$search_results = $this->search_untappd( $beer_name );

		if ( is_array( $search_results ) ) {

			$beer = array_shift( $search_results );

			if ( $beer ) {

				$titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

				$titan->setOption( self::UNTAPPD_ID, $beer->beer->bid, $post_id );

			}

			return true;

		}

		return false;

	}

	/**
	 * Flag a beer as having attempted to be mapped with Untappd
	 * NOTE: many won't be found and we don't want to keep looking
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_beer_as_untappd_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, self::UNTAPPD_SEARCHED, true );

	}

	/**
	 * Schedule a job to search a single beer on Untappd
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_untappd_search_for_beer( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::UNTAPPD_MAP_CRON, array( $post_id ) );

	}

	/**
	 * WP-Cron hook callback for searching a beer on Untappd
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_map_dabc_beer_to_untappd( $post_id ) {

		$success = $this->map_dabc_beer_to_untappd( $post_id );

		if ( $success ) {

			$this->mark_beer_as_untappd_searched( $post_id );

		} else {

			$this->schedule_untappd_search_for_beer( $post_id, 10 );

		}

	}

	/**
	 * Find all beers that haven't been searched for on Untappd
	 * successfully and schedule a cron job to map them
	 */
	function search_beers_on_untappd() {

		$unmapped_beers = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => self::UNTAPPD_SEARCHED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		array_map( array( $this, 'schedule_untappd_search_for_beer' ), $unmapped_beers->posts );

	}

	/**
	 * Schedule a job to sync a single beer with Untappd
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_untappd_sync_for_beer( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::UNTAPPD_SYNC_CRON, array( $post_id ) );

	}


	/**
	 * Retrieve info and ratings from Untappd for beers that have been mapped
	 */
	function sync_beers_with_untappd() {

		$unsynced_beers = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => self::UNTAPPD_SYNCED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		foreach ( $unsynced_beers->posts as $post_id ) {

			if ( $this->titan->getOption( self::UNTAPPD_ID, $post_id ) ) {

				$this->schedule_untappd_sync_for_beer( $post_id );

			}

		}

	}

	/**
	 * Flag a beer as having been synced with Untappd
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_beer_as_untappd_synced( $post_id ) {

		return (bool) update_post_meta( $post_id, self::UNTAPPD_SYNCED, true );

	}

	/**
	 * WP-Cron hook callback for syncing a beer with Untappd
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_sync_dabc_beer_with_untappd( $post_id ) {

		$success = $this->sync_dabc_beer_with_untappd( $post_id );

		if ( $success ) {

			$this->mark_beer_as_untappd_synced( $post_id );

		} else {

			$this->schedule_untappd_sync_for_beer( $post_id, 10 );

		}

	}

	/**
	 * For a given DABC beer post ID, sync date with Untappd
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function sync_dabc_beer_with_untappd( $post_id ) {

		$untappd_id = $this->titan->getOption( self::UNTAPPD_ID, $post_id );

		$beer_info  = $this->get_untappd_beer_info( $untappd_id );

		if ( is_object( $beer_info ) ) {

			var_dump($beer_info);

			if ( isset( $beer_info->rating_count ) ) {

				$this->titan->setOption( self::UNTAPPD_RATING_COUNT, $beer_info->rating_count, $post_id );

			}

			if ( isset( $beer_info->rating_score ) ) {

				$this->titan->setOption( self::UNTAPPD_RATING_SCORE, $beer_info->rating_score, $post_id );

			}

			if ( isset( $beer_info->beer_abv ) ) {

				$this->titan->setOption( self::UNTAPPD_ABV, $beer_info->beer_abv, $post_id );

			}

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

			if ( isset( $beer_info->beer_label ) ) {

				$this->sync_featured_image_with_untappd( $beer_info->beer_label, $post_id );

			}

			return true;

		}

		return false;

	}

}

