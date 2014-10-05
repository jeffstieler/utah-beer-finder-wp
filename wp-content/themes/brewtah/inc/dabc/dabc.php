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

}

add_action( 'init', array( new DABC(), 'init' ) );