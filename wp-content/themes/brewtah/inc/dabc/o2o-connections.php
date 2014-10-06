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
	 * Add a beer post to a store's connected beers
	 *
	 * @param int $beer_post_id
	 * @param int $store_post_id
	 * @return bool|WP_Error true on success, WP_Error otherwise
	 */
	function add_beer_to_store( $beer_post_id, $store_post_id ) {

		return $this->set_connected_to( self::DABC_STORE_BEERS, $store_post_id, $beer_post_id, true );

	}

}