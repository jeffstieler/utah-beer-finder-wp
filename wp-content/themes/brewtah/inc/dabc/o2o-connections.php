<?php

class DABC_O2O_Connections {

	const DABC_STORE_BEERS = 'dabc_store_beers';

	var $o2o;
	var $o2o_connection_factory;

	function __construct() {

		$this->o2o = O2O::GetInstance();

		$this->o2o_connection_factory = $this->o2o->get_connection_factory();

	}

	function init() {

		$this->o2o->Register_Connection(
			self::DABC_STORE_BEERS,
			DABC_Store_Post_Type::POST_TYPE,
			DABC_Beer_Post_Type::POST_TYPE,
			array(
				'reciprocal'   => true,
				'hierarchical' => false,
				'to'           => array(
					'sortable' => false,
					'labels'   => array(
						'name'          => 'Beers',
						'singular_name' => 'Beer'
					)
				),
				'from'         => array(
					'sortable' => false,
					'labels'   => array(
						'name'          => 'Stores',
						'singular_name' => 'Store'
					)
				)
			)
		);

	}

	/**
	 * Helper to call a given O2O connetion's set_connected_to() method
	 *
	 * @param string $connection_name
	 * @param int $from_object_id
	 * @param array|int $connected_to_ids
	 * @param bool $append
	 * @return bool|WP_Error true on success, WP_Error on connection not found or other error
	 */
	function set_connected_to( $connection_name, $from_object_id, $connected_to_ids = array(), $append = false ) {

		$connection = $this->o2o_connection_factory->get_connection( $connection_name );

		if ( false === $connection ) {

			return new WP_Error( 'invalid_connection_name', 'The given connection name is not valid.' );

		}

		return $connection->set_connected_to( $from_object_id, $connected_to_ids, $append );

	}

}