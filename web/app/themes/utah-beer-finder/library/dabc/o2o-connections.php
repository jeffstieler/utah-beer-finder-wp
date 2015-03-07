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
				)
			)
		) );

	}

	/**
	 * Connect a beer to a store and set it's store quantity
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
				'quantity' => $store_quantity
			)
		);

		return $result;

	}

}