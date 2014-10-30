<?php

use Symfony\Component\DomCrawler\Crawler;

class Ratebeer_Sync {

	const TITAN_NAMESPACE = 'ratebeer';
	const ID              = 'id';
	const URL             = 'url';
	const OVERALL_SCORE   = 'overall-score';
	const STYLE_SCORE     = 'style-score';
	const CALORIES        = 'calories';
	const ABV             = 'abv';
	const SEARCH_CRON     = 'search_ratebeer';
	const SEARCHED        = 'has-ratebeer-searched';
	const IMGURL_FORMAT   = 'http://res.cloudinary.com/ratebeer/image/upload/beer_%s.jpg';
	const BASE_URL        = 'http://www.ratebeer.com';

	var $post_type;
	var $titan;
	var $search_column_map;

	function __construct( $post_type ) {

		$this->post_type = $post_type;

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

		$this->search_column_map = array(
			0 => 'name',
			2 => 'status',
			3 => 'score',
			4 => 'ratings',
		);

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

		add_action( self::SEARCH_CRON, array( $this, 'cron_map_post_to_beer' ) );

	}

	/**
	 * WP-Cron hook callback for searching a beer on Ratebeer
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_map_post_to_beer( $post_id ) {

		$success = $this->map_post_to_beer( $post_id );

		if ( $success ) {

			$this->mark_post_as_searched( $post_id );

		} else {

			$this->schedule_search_for_post( $post_id, 10 );

		}

	}

	function init() {

		$this->register_post_meta();

		$this->attach_hooks();

	}

	/**
	 * For a given DABC beer post ID, search ratebeer for it
	 * and associate the URL if found
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function map_post_to_beer( $post_id ) {

		$post = get_post( $post_id );

		$beer_name = $post->post_title;

		$search_results = $this->search( $beer_name );

		if ( is_array( $search_results ) ) {

			$beer = array_shift( $search_results );

			if ( $beer ) {

				$titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

				$titan->setOption( self::URL, $beer['url'], $post_id );

				$titan->setOption( self::ID, $beer['id'], $post_id );

			}

			return true;

		}

		return false;

	}

	/**
	 * Flag a beer as having attempted to be mapped with Ratebeer
	 * NOTE: many won't be found and we don't want to keep looking
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_post_as_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, self::SEARCHED, true );

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
	 * Schedule a job to search a single beer on Ratebeer
	 *
	 * @param int $post_id beer post ID
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_search_for_post( $post_id, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		wp_schedule_single_event( $timestamp, self::SEARCH_CRON, array( $post_id ) );

	}

	/**
	 * Find all posts that haven't been searched for on Ratebeer
	 * successfully and schedule a cron job to map them
	 */
	function schedule_search_for_all_posts() {

		$unmapped_posts = new WP_Query( array(
			'post_type'      => $this->post_type,
			'meta_query'     => array(
				array(
					'key'     => self::SEARCHED,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		array_map( array( $this, 'schedule_search_for_post' ), $unmapped_posts->posts );

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

}