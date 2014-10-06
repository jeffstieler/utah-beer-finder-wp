<?php

/**
 * Include the DABC beer post type
 */
require_once( 'dabc-beer-post-type.php' );

/**
 * Include the DABC store post type
 */
require_once( 'dabc-store-post-type.php' );

/**
 * Register connections between post types
 */
require_once( 'o2o-connections.php' );


class DABC {

	var $beers;
	var $stores;
	var $connections;

	function __construct() {

		$this->beers = new DABC_Beer_Post_Type();

		$this->stores = new DABC_Store_Post_Type();

		$this->connections = new DABC_O2O_Connections();

	}

	function init() {

		$this->beers->init();

		$this->stores->init();

		$this->connections->init();

	}

	/**
	 * Search DABC inventory for given beer, associate it with store posts
	 * where it's in stock and record the inventory quantities
	 *
	 * @param int $beer_post_id
	 */
	function sync_inventory_for_beer( $beer_post_id ) {

		$cs_code = $this->beers->get_cs_code( $beer_post_id );

		if ( ! $cs_code ) {

			return;

		}

		$inventory = $this->beers->search_dabc_inventory_for_cs_code( $cs_code );

		if ( ! $inventory ) {

			return;

		}

		// TODO: remove beer from stores it's no longer in stock

		foreach ( $inventory as $store_inventory ) {

			$store_post = $this->stores->get_store_by_store_number( $store_inventory['store'] );

			if ( $store_post ) {

				$this->connections->add_beer_to_store( $beer_post_id, $store_post->ID );

			}

		}

		$this->connections->set_beer_inventory( $beer_post_id, $inventory );

	}

}

add_action( 'init', array( new DABC(), 'init' ) );