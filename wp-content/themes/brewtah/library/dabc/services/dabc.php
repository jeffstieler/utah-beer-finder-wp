<?php

use Symfony\Component\DomCrawler\Crawler;

class DABC_Sync {

	const URL_BASE      = 'http://www.webapps.abc.utah.gov/Production';
	const BEER_LIST_URL = '/OnlinePriceList/DisplayPriceList.aspx?DivCd=T';
	const INVENTORY_URL = '/OnlineInventoryQuery/IQ/InventoryQuery.aspx';

	protected $column_map;
	protected $status_map;

	function __construct() {

		$this->column_map = array(
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

		$this->status_map = array(
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

		$result = $this->_make_http_request( self::URL_BASE . self::BEER_LIST_URL );

		if ( is_wp_error( $result ) ) {

			return $result;

		}

		$beers = $this->parse_beer_list( $result );

		return $beers;

	}

	function parse_beer_list( $html ) {

		$crawler = new Crawler( $html );

		$table_rows = $crawler->filter( '#ctl00_ContentPlaceHolderBody_gvPricelist > tr' );

		$beers = $table_rows->each( function( Crawler $row ) {
			return $this->parse_beer_table_row( $row );
		} );

		$beers = array_filter( $beers );

		return $beers;

	}

	function parse_beer_table_row( Crawler $row ) {

		$beer = false;

		$cols = $row->filter( 'td' );

		if ( iterator_count( $cols ) ) {

			$beer = array();

			foreach ( $this->column_map as $i => $key ) {

				$beer[$key] = $cols->eq( $i )->text();

			}

			$beer['name'] = $this->pretty_up_beer_name( $beer['description'] );

		}

		return $beer;

	}

	/**
	 * Parse the HTML response from searching DABC inventory
	 *
	 * @param string $html
	 * @return array list of store numbers and beer quantities
	 */
	function parse_inventory_search_response( $html ) {

		$crawler   = new Crawler( $html );

		$inventory = array();

		$rows      = $crawler->filter( '#ContentPlaceHolderBody_gvInventoryDetails tr.gridViewRow' );

		if ( iterator_count( $rows ) ) {

			$inventory_data = $rows->each( function( Crawler $row ) {
				return $this->parse_inventory_search_result_row( $row );
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
	 * Parse out the Store Number and Quantity from a DABC Inventory table row
	 *
	 * @param Crawler $row
	 * @return boolean|array false on parsing error, array of store/qty on success
	 */
	function parse_inventory_search_result_row( $row ) {

		$cols = $row->filter( 'td' );

		if ( iterator_count( $cols ) ) {

			$store    = preg_replace( '/^0+/', '', $cols->first()->text() );

			$quantity = (int) $cols->eq( 2 )->text();

			return compact( 'store', 'quantity' );

		}

		return false;

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
	 * Get store inventories for a given CS Code
	 *
	 * @param string $cs_code DABC Beer SKU
	 * @return boolean|array false on failure, array of store inventories on success
	 */
	function search_inventory_for_cs_code( $cs_code ) {

		$url    = self::URL_BASE . self::INVENTORY_URL;

		$result = $this->_make_http_request( $url );

		if ( ( false === $result ) || is_wp_error( $result ) ) {

			return false;

		}

		$form = $this->parse_required_asp_form_fields( $result );

		if ( false === $form ) {

			return false;

		}

		$result = $this->submit_inventory_form( $cs_code, $form );

		if ( ( false === $result ) || is_wp_error( $result ) ) {

			return false;

		}

		$inventory = $this->parse_inventory_search_response( $result );

		return $inventory;

	}

	/**
	 * POST the DABC inventory search form
	 *
	 * @param string $cs_code DABC beer SKU
	 * @param array $session_values __VIEWSTATE and __EVENTVALIDATION
	 * @return bool|WP_Error|string see _make_http_request()
	 */
	function submit_inventory_form( $cs_code, $session_values ) {

		$body = array_merge(
			$session_values,
			array(
				'__ASYNCPOST' => 'true',
				'ctl00$ContentPlaceHolderBody$tbCscCode' => $cs_code
			)
		);

		$result = $this->_make_http_request(
			self::URL_BASE . self::INVENTORY_URL,
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

}