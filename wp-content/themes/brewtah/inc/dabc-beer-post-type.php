<?php
/**
 * Implement the Beer post type
 *
 * @package Brewtah
 */
use Symfony\Component\DomCrawler\Crawler;

class DABC_Beer_Post_Type {

	const POST_TYPE              = 'dabc-beer';
	const DEPT_TAXONOMY          = 'dabc-dept';
	const CAT_TAXONOMY           = 'dabc-cat';
	const SIZE_TAXONOMY          = 'beer-size';
	const STATUS_TAXONOMY        = 'dabc-status';
	const STYLE_TAXONOMY         = 'beer-style';
	const TITAN_NAMESPACE        = 'dabc-beer';
	const DABC_NAME_OPTION       = 'dabc-name';
	const CS_CODE_OPTION         = 'cs-code';
	const PRICE_OPTION           = 'price';
	const RATEBEER_URL_OPTION    = 'ratebeer-url';
	const RATEBEER_SEARCHED      = 'has-ratebeer-searched';
	const RATEBEER_MAP_CRON      = 'map_ratebeer';
	const RATEBEER_SYNC_CRON     = 'sync_ratebeer';
	const RATEBEER_IMAGE_CRON    = 'image_ratebeer';
	const RATEBEER_SYNCED        = 'has-ratebeer-sync';
	const RATEBEER_ID            = 'ratebeer-id';
	const RATEBEER_OVERALL_SCORE = 'ratebeer-overall-score';
	const RATEBEER_STYLE_SCORE   = 'ratebeer-style-score';
	const RATEBEER_CALORIES      = 'ratebeer-calories';
	const RATEBEER_ABV           = 'ratebeer-abv';
	const RATEBEER_IMGURL_FORMAT = 'http://res.cloudinary.com/ratebeer/image/upload/beer_%s.jpg';
	const RATEBEER_IMG_SEARCHED  = 'has-ratebeer-image';
	const RATEBEER_BASE_URL      = 'http://www.ratebeer.com';
	const DABC_URL_BASE          = 'http://www.webapps.abc.utah.gov/Production';
	const DABC_BEER_LIST_URL     = '/OnlinePriceList/DisplayPriceList.aspx?DivCd=T';
	const DABC_INVENTORY_URL     = '/OnlineInventoryQuery/IQ/InventoryQuery.aspx';

	var $titan;
	var $dabc_column_map;
	var $ratebeer_search_column_map;

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

		$this->ratebeer_search_column_map = array(
			0 => 'name',
			2 => 'status',
			3 => 'score',
			4 => 'ratings',
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

	}

	function attach_hooks() {

		add_action( self::RATEBEER_MAP_CRON, array( $this, 'cron_map_dabc_beer_to_ratebeer' ) );

		add_action( self::RATEBEER_SYNC_CRON, array( $this, 'cron_sync_dabc_beer_with_ratebeer' ) );

		add_action( self::RATEBEER_IMAGE_CRON, array( $this, 'sync_featured_image_with_ratebeer' ), 10, 2 );

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

		foreach ( $taxonomy_map as $info_key => $taxonomy ) {

			if ( ! term_exists( $beer_info[$info_key], $taxonomy ) ) {

				wp_insert_term( $beer_info[$info_key], $taxonomy );

			}

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

		$post = get_post( $post_id );

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
	 * Grab featured images from Ratebeer
	 */
	function sync_featured_images_with_ratebeer() {

		$no_image_beers = new WP_Query( array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			)
		) );

		foreach ( $no_image_beers->posts as $post_id ) {

			$ratebeer_id  = $this->titan->getOption( self::RATEBEER_ID, $post_id );

			$image_synced = $this->titan->getOption( self::RATEBEER_IMG_SEARCHED, $post_id );

			if ( $ratebeer_id && ! $image_synced ) {

				$this->schedule_ratebeer_image_sync_for_beer( $ratebeer_id, $post_id );

			}

		}

	}

	/**
	 * Schedule a job to download a beer image from Ratebeer
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_ratebeer_image_sync_for_beer( $ratebeer_id, $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::RATEBEER_IMAGE_CRON, array( $ratebeer_id, $post_id ) );

	}

	/**
	 * Flag a beer as having an image sync attempt with Ratebeer
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_beer_as_image_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, self::RATEBEER_IMG_SEARCHED, true );

	}

	/**
	 * Download a beer's image from Ratebeer and set as it's featured image
	 *
	 * @param int $post_id
	 */
	function sync_featured_image_with_ratebeer( $ratebeer_id, $post_id ) {

		$image_url = $this->get_ratebeer_image_url( $ratebeer_id );

		$result    = media_sideload_image( $image_url, $post_id );

		if ( is_wp_error( $result ) ) {

			if ( 'http_404' === $result->get_error_code() ) {

				$this->mark_beer_as_image_searched( $post_id );

			} else {

				$this->schedule_ratebeer_image_sync_for_beer( $ratebeer_id, $post_id, 10 );

			}

		} else {

			$images = get_attached_media( 'image', $post_id );

			$thumbnail = array_shift( $images );

			if ( ! is_null( $thumbnail ) ) {

				set_post_thumbnail( $post_id, $thumbnail->ID );

			}

			$this->mark_beer_as_image_searched( $post_id );

		}

	}

	/**
	 * Get store inventories for a given CS Code
	 *
	 * @param string $cs_code DABC Beer SKU
	 * @return boolean|array false on failure, array of store inventories on success
	 */
	function search_dabc_inventory_for_cs_code( $cs_code ) {

		$result = $this->_make_http_request( self::DABC_URL_BASE . self::DABC_INVENTORY_URL );

		if ( $result && ! is_wp_error( $result ) ) {

			$crawler = new Crawler( $result );

			$viewstate  = $crawler->filter( '#__VIEWSTATE' )->attr( 'value' );

			$validation = $crawler->filter( '#__EVENTVALIDATION' )->attr( 'value' );

			$result = $this->_make_http_request(
				$url,
				array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
						'User-Agent' => 'Mozilla'
					),
					'body' => array(
						'__VIEWSTATE' => $viewstate,
						'__EVENTVALIDATION' => $validation,
						'__ASYNCPOST' => 'true',
						'ctl00$ContentPlaceHolderBody$tbCscCode' => $cs_code
					),
					'timeout' => 10
				)
			);

			if ( $result && ! is_wp_error( $result ) ) {

				$crawler->clear();

				$crawler->addHtmlContent( $result );

				$rows = $crawler->filter( '#ContentPlaceHolderBody_gvInventoryDetails tr.gridViewRow' );

				$inventory = $rows->each( function( $row ) {
					$cols = $row->filter('td');
					return array(
						'store' => $cols->first()->text(),
						'quantity' => $cols->eq( 2 )->text()
					);
				} );

				return $inventory;

			}

		}

		return false;

	}

}

add_action( 'init', array( new DABC_Beer_Post_Type(), 'init' ) );