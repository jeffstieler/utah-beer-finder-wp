<?php

use Symfony\Component\DomCrawler\Crawler;

class Untappd_Sync {

	const TITAN_NAMESPACE        = 'untappd';
	const UNTAPPD_SEARCHED       = 'has-untappd-searched';
	const UNTAPPD_MAP_CRON       = 'map_untappd';
	const UNTAPPD_SYNC_CRON      = 'sync_untappd';
	const UNTAPPD_IMAGE_CRON     = 'image_untappd';
	const UNTAPPD_ID             = 'untappd-id';
	const UNTAPPD_RATING_SCORE   = 'untappd-rating-score';
	const UNTAPPD_RATING_COUNT   = 'untappd-rating-count';
	const UNTAPPD_ABV            = 'untappd-abv';
	const UNTAPPD_HIT_LIMIT      = 'untappd-hit-limit';
	const UNTAPPD_SYNCED         = 'has-untappd-sync';
	const UNTAPPD_IMG_SEARCHED   = 'has-untappd-image';

	var $post_type;
	var $titan;

	function __construct( $post_type ) {

		$this->post_type = $post_type;

		$this->titan = TitanFramework::getInstance( self::TITAN_NAMESPACE );

	}

	function init() {

		$this->register_post_meta();

	}

	function register_post_meta() {

		$untappd_box = $this->titan->createMetaBox( array(
			'name'      => 'Untappd Info',
			'id'        => 'untappd-info',
			'post_type' => $this->post_type
		) );

		$untappd_box->createOption( array(
			'name' => 'ID',
			'id'   => self::UNTAPPD_ID
		) );

		$untappd_box->createOption( array(
			'name' => 'Rating Score',
			'id'   => self::UNTAPPD_RATING_SCORE
		) );

		$untappd_box->createOption( array(
			'name' => 'Ratings Count',
			'id'   => self::UNTAPPD_RATING_COUNT
		) );

		$untappd_box->createOption( array(
			'name' => 'ABV',
			'id'   => self::UNTAPPD_ABV
		) );

	}

}