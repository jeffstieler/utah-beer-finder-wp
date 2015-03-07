<?php

class DABC_O2O_Connections {

	const DABC_STORE_BEERS = 'dabc_store_beers';

	function init() {

		p2p_register_connection_type( array(
			'name'            => self::DABC_STORE_BEERS,
			'from'            => DABC_Store_Post_Type::POST_TYPE,
			'to'              => DABC_Beer_Post_Type::POST_TYPE,
			'can_create_post' => false,
			'fields'          => array(
				'quantity'     => array(
					'title' => 'Quantity',
					'type'  => 'text'
				),
				'last_updated' => array(
					'title' => 'Last Updated',
					'type'  => 'text'
				)
			)
		) );

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

	/**
	 * Connect a beer to a store, set it's store quantity and last updated date
	 *
	 * @param int $beer_post_id
	 * @param int $store_post_id
	 * @param int $store_quantity
	 * @return bool|WP_Error true on success, WP_Error otherwise
	 */
	function add_beer_to_store( $beer_post_id, $store_post_id, $store_quantity ) {

		$result = p2p_type( self::DABC_STORE_BEERS )->connect(
			$store_post_id,
			$beer_post_id,
			array(
				'quantity'     => $store_quantity,
				'last_updated' => date( 'Y-m-d H:i:s' )
			)
		);

		return $result;

	}

}