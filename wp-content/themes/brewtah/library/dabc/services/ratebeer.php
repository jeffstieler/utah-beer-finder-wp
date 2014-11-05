<?php

require_once( __DIR__ . '/base-service.php' );

use Symfony\Component\DomCrawler\Crawler;

class Ratebeer_Sync extends Base_Beer_Service {

	const ID              = 'id';
	const URL             = 'url';
	const OVERALL_SCORE   = 'overall-score';
	const STYLE_SCORE     = 'style-score';
	const CALORIES        = 'calories';
	const ABV             = 'abv';
	const BASE_URL        = 'http://www.ratebeer.com';

	protected $service_name = 'ratebeer';
	protected $search_column_map;

	function __construct( $post_type ) {

		parent::__construct( $post_type );

		$this->search_column_map = array(
			0 => 'name',
			2 => 'status',
			3 => 'score',
			4 => 'ratings',
		);

	}

	function _get_titan_meta_key( $option_name ) {

		return ( $this->service_name . '_' . $option_name );

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

	function attach_hooks() {

		parent::attach_hooks();

		add_action( 'update_postmeta', array( $this, 'sync_post_beer_info_on_url_update' ), 10, 4 );

		add_action( 'add_post_meta', array( $this, 'sync_post_beer_info_on_url_add' ), 10, 3 );

	}

	function get_abv( $post_id ) {

		return $this->titan->getOption( self::ABV, $post_id );

	}

	/**
	 * Get info for a single beer from Ratebeer by path
	 *
	 * @param string $path beer path on Ratebeer
	 * @return boolean|array boolean false on error, array of beer info on success
	 */
	function get_beer_info( $path ) {

		$response = $this->sync_request( $path );

		if ( ( false === $response ) || is_wp_error( $response ) ) {

			return false;

		}

		$info = $this->parse_single_beer_page( $response );

		return $info;

	}

	function get_calories( $post_id ) {

		return $this->titan->getOption( self::CALORIES, $post_id );

	}

	function get_overall_rating( $post_id ) {

		return $this->titan->getOption( self::OVERALL_SCORE, $post_id );

	}

	function get_style_rating( $post_id ) {

		return $this->titan->getOption( self::STYLE_SCORE, $post_id );

	}

	/**
	 * Handler for when a beer is found on Ratebeer
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function map_post_to_beer( $post_id, $beer ) {

		$this->titan->setOption( self::URL, $beer['url'], $post_id );

		$this->titan->setOption( self::ID, $beer['id'], $post_id );

	}

	/**
	 * Produce an array of beers from Ratebeer search results page markup
	 *
	 * @param string $html HTML search results from Ratebeer
	 * @return array beers found in Ratebeer html response
	 */
	function parse_search_results_page( $html ) {

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

				return $this->parse_search_results_table_row( $row );

			} );

		}

		return $beers;

	}

	/**
	 * Callback to process rows from parse_ratebeer_response() and map
	 * table columns to beer info array keys
	 *
	 * @param \Symfony\Component\DomCrawler\Crawler $row
	 * @return array beer information from ratebeer
	 */
	function parse_search_results_table_row( Crawler $row ) {

		$beer = false;

		$cols = $row->filter( 'td' );

		if ( iterator_count( $cols ) ) {

			$beer = array();

			foreach ( $this->search_column_map as $i => $key ) {

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
	 * Produce an array of beer info from Ratebeer single beer page markup
	 *
	 * @param string $html HTML beer page from Ratebeer
	 * @return array beer info found in Ratebeer html response
	 */
	function parse_single_beer_page( $html ) {

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

	function register_post_meta() {

		$rb_box = $this->titan->createMetaBox( array(
			'name'      => 'Ratebeer Info',
			'id'        => 'ratebeer-info',
			'post_type' => $this->post_type
		) );

		$rb_box->createOption( array(
			'name' => 'ID',
			'id'   => self::ID
		) );

		$rb_box->createOption( array(
			'name' => 'URL',
			'id'   => self::URL
		) );

		$rb_box->createOption( array(
			'name' => 'Overall Score',
			'id'   => self::OVERALL_SCORE
		) );

		$rb_box->createOption( array(
			'name' => 'Style Score',
			'id'   => self::STYLE_SCORE
		) );

		$rb_box->createOption( array(
			'name' => 'Calories',
			'id'   => self::CALORIES
		) );

		$rb_box->createOption( array(
			'name' => 'ABV',
			'id'   => self::ABV
		) );

	}

	/**
	 * Search Ratebeer for beer(s), filtering out aliased beers
	 *
	 * @param string $query search query
	 * @return boolean|array boolean false on error, array of beers on success
	 */
	function search( $query ) {

		$response = $this->search_request( $query );

		if ( ( false === $response ) || is_wp_error( $response ) ) {

			return false;

		}

		$beers = $this->parse_search_results_page( $response );

		$beers = array_filter( $beers, function( $beer ) {

			return ( 'A' !== $beer['status'] );

		} );

		return $beers;

	}

	/**
	 * Make search request to Ratebeer
	 *
	 * @param string $query - search query for ratebeer
	 * @return bool|WP_Error|string boolean false if non 200, WP_Error on request error, HTML string on success
	 */
	function search_request( $query ) {

		$result = $this->_make_http_request(
			self::BASE_URL . '/findbeer.asp',
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
	 * Schedule a Ratebeer sync if a URL is added for a beer
	 *
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 */
	function sync_post_beer_info_on_url_add( $object_id, $meta_key, $meta_value ) {

		$ratebeer_url_key = $this->_get_titan_meta_key( self::URL );

		if ( ( $ratebeer_url_key === $meta_key ) && !empty( $meta_value ) ) {

			$this->schedule_sync_for_post( $object_id );

		}

	}

	/**
	 * Schedule a Ratebeer sync if the URL changes for a beer
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 */
	function sync_post_beer_info_on_url_update( $meta_id, $object_id, $meta_key, $meta_value ) {

		$this->sync_post_beer_info_on_url_add( $object_id, $meta_key, $meta_value );

	}



	/**
	 * For a given DABC beer post ID, sync date with ratebeer
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function sync_post_beer_info( $post_id ) {

		$beer_path = $this->titan->getOption( self::URL, $post_id );

		$beer_info = $this->get_beer_info( $beer_path );

		if ( is_array( $beer_info ) && $beer_info ) {

			$this->titan->setOption( self::OVERALL_SCORE, $beer_info['overall_score'], $post_id );

			$this->titan->setOption( self::STYLE_SCORE, $beer_info['style_score'], $post_id );

			$this->titan->setOption( self::ABV, $beer_info['abv'], $post_id );

			$this->titan->setOption( self::CALORIES, $beer_info['calories'], $post_id );

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
	 * Make sync request to Ratebeer
	 *
	 * @param string $path - beer path on ratebeer
	 * @return bool|WP_Error|string boolean false if non 200, WP_Error on request error, HTML string on success
	 */
	function sync_request( $path ) {

		$result = $this->_make_http_request(
			self::BASE_URL . $path,
			array(
				'timeout' => 10
			)
		);

		return $result;

	}

}