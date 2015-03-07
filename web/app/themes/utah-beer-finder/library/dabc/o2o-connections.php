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
	 * Pack data (serialized) into the O2O connection term description
	 *
	 * @param string $connection_name
	 * @param int $object_id
	 * @param mixed $data
	 * @return boolean|WP_Error|WP_Term
	 */
	function set_object_connection_term_data( $connection_name, $object_id, $data ) {

		$connection = $this->o2o_connection_factory->get_connection( $connection_name );

		$connection_term_id = $connection->get_object_termID( $object_id, false );

		if ( $connection_term_id ) {

			$args = array(
				'description' => serialize( $data )
			);

			return wp_update_term( $connection_term_id, $connection->get_taxonomy(), $args );

		}

		return false;

	}

	/**
	 * Retrieve the data packed into an O2O connection term's description
	 *
	 * @param string $connection_name
	 * @param int $object_id
	 * @return null|mixed null on failure, unserialized data on success
	 */
	function get_object_connection_term_data( $connection_name, $object_id ) {

		$connection = $this->o2o_connection_factory->get_connection( $connection_name );

		$connection_term_id = $connection->get_object_termID( $object_id, false );

		$data = null;

		if ( $connection_term_id ) {

			$term = get_term( $connection_term_id, $connection->get_taxonomy() );

			if ( ! is_wp_error( $term ) ) {

				$data = unserialize( $term->description );

			}

		}

		return $data;

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

	/**
	 * Add beer inventory information to it's O2O connection term
	 *
	 * @param int $beer_post_id
	 * @param array $inventory
	 * @return bool|WP_Error|WP_Term
	 */
	function set_beer_inventory( $beer_post_id, $inventory ) {

		$data = array(
			'last_updated' => date( 'Y-m-d H:i:s' ),
			'inventory'    => $inventory
		);

		return $this->set_object_connection_term_data( self::DABC_STORE_BEERS, $beer_post_id, $data );

	}

}