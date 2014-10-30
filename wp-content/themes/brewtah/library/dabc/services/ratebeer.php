<?php

class Ratebeer_Sync {

	const TITAN_NAMESPACE        = 'ratebeer';
	const RATEBEER_ID            = 'id';
	const RATEBEER_URL_OPTION    = 'url';
	const RATEBEER_OVERALL_SCORE = 'overall-score';
	const RATEBEER_STYLE_SCORE   = 'style-score';
	const RATEBEER_CALORIES      = 'calories';
	const RATEBEER_ABV           = 'abv';
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

	function init() {

		$this->register_post_meta();

	}

	function register_post_meta() {

		$rb_box = $this->titan->createMetaBox( array(
			'name'      => 'Ratebeer Info',
			'id'        => 'ratebeer-info',
			'post_type' => $this->post_type
		) );

		$rb_box->createOption( array(
			'name' => 'ID',
			'id'   => self::RATEBEER_ID
		) );

		$rb_box->createOption( array(
			'name' => 'URL',
			'id'   => self::RATEBEER_URL_OPTION
		) );

		$rb_box->createOption( array(
			'name' => 'Overall Score',
			'id'   => self::RATEBEER_OVERALL_SCORE
		) );

		$rb_box->createOption( array(
			'name' => 'Style Score',
			'id'   => self::RATEBEER_STYLE_SCORE
		) );

		$rb_box->createOption( array(
			'name' => 'Calories',
			'id'   => self::RATEBEER_CALORIES
		) );

		$rb_box->createOption( array(
			'name' => 'ABV',
			'id'   => self::RATEBEER_ABV
		) );

	}

}