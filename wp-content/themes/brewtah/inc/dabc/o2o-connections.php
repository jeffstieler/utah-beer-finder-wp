<?php

class DABC_O2O_Connections {
	
	const DABC_STORE_BEERS = 'dabc_store_beers';
	
	function init() {
		
		O2O::Register_Connection(
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
	
}