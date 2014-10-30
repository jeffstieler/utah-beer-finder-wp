<?php

class Ratebeer_Sync {
	
	const TITAN_NAMESPACE        = 'ratebeer';
	const RATEBEER_ID            = 'ratebeer-id';
	const RATEBEER_URL_OPTION    = 'ratebeer-url';
	const RATEBEER_OVERALL_SCORE = 'ratebeer-overall-score';
	const RATEBEER_STYLE_SCORE   = 'ratebeer-style-score';
	const RATEBEER_CALORIES      = 'ratebeer-calories';
	const RATEBEER_ABV           = 'ratebeer-abv';
	const RATEBEER_IMGURL_FORMAT = 'http://res.cloudinary.com/ratebeer/image/upload/beer_%s.jpg';
	const RATEBEER_BASE_URL      = 'http://www.ratebeer.com';
	
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
	
}