<?php

abstract class Base_Beer_Service {

	protected $post_type;
	protected $service_name;
	protected $titan;
	protected $searched_flag;
	protected $search_cron_hook;
	protected $synced_flag;
	protected $sync_cron_hook;

	function __construct( $post_type ) {

		$this->post_type = $post_type;

		$this->searched_flag = "has-{$this->service_name}-searched";

		$this->search_cron_hook = 'search_' . $this->service_name;

		$this->synced_flag = "has-{$this->service_name}-sync";

		$this->sync_cron_hook = 'sync_' . $this->service_name;

		$this->titan = TitanFramework::getInstance( $this->service_name );

	}

	/**
	 * WP-Cron job scheduler helper
	 *
	 * @param string $job cron hook
	 * @param int|array $posts single post ID or array of IDs
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function _schedule_job_for_post( $job, $posts, $offset_in_minutes = 0 ) {

		$timestamp = ( time() + ( $offset_in_minutes * MINUTE_IN_SECONDS ) );

		$posts = (array) $posts;

		foreach ( $posts as $post_id ) {

			wp_schedule_single_event( $timestamp, $job, array( $post_id ) );

		}

	}

	function attach_hooks() {

		add_action( $this->search_cron_hook, array( $this, 'cron_map_post_to_beer' ) );

		add_action( $this->sync_cron_hook, array( $this, 'cron_sync_post_beer_info' ) );

	}

	/**
	 * WP-Cron hook callback for searching a beer on a service
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_map_post_to_beer( $post_id ) {

		$success = $this->search_for_post( $post_id );

		if ( $success ) {

			$this->mark_post_as_searched( $post_id );

		} else {

			$this->schedule_search_for_post( $post_id, 10 );

		}

	}

	/**
	 * WP-Cron hook callback for syncing a beer with a service
	 * Marks beer as processed on success, or rescedules itself on failure
	 *
	 * @param int $post_id beer post ID
	 */
	function cron_sync_post_beer_info( $post_id ) {

		$success = $this->sync_post_beer_info( $post_id );

		if ( $success ) {

			$this->mark_post_as_synced( $post_id );

		} else {

			$this->schedule_sync_for_post( $post_id, 10 );

		}

	}

	function init() {

		$this->register_post_meta();

		$this->attach_hooks();

	}

	/**
	 *
	 * @param int $post_id
	 * @param array|object $beer
	 */
	function map_post_to_beer( $post_id, $beer ) {}

	/**
	 * Flag a beer as having attempted to be mapped with the service
	 * NOTE: many won't be found and we don't want to keep looking
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_post_as_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, $this->searched_flag, true );

	}

	/**
	 * Flag a beer as having been synced with the service
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_post_as_synced( $post_id ) {

		return (bool) update_post_meta( $post_id, $this->synced_flag, true );

	}

	/**
	 *
	 */
	function register_post_meta() {}

	/**
	 * Find all posts that haven't been searched for on the service
	 * successfully and schedule a cron job to map them
	 */
	function schedule_search_for_all_posts() {

		$unmapped_posts = new WP_Query( array(
			'post_type'      => $this->post_type,
			'meta_query'     => array(
				array(
					'key'     => $this->searched_flag,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		$this->schedule_search_for_post( $unmapped_posts->posts );

	}

	/**
	 * Schedule a job to search beer(s) on the service
	 *
	 * @param int|array $posts post ID(s)
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_search_for_post( $posts, $offset_in_minutes = 0 ) {

		$this->_schedule_job_for_post( $this->search_cron_hook, $posts, $offset_in_minutes );

	}

	/**
	 * Sync all mapped beers with the service
	 */
	function schedule_sync_for_all_posts() {

		$unsynced_posts = new WP_Query( array(
			'post_type'      => $this->post_type,
			'meta_query'     => array(
				array(
					'key'     => $this->synced_flag,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				)
			),
			'no_found_rows'  => true,
			'posts_per_page' => -1,
			'fields'         => 'ids'
		) );

		$this->schedule_sync_for_post( $unsynced_posts->posts );

	}

	/**
	 * Schedule a job to sync beer(s) with the service
	 *
	 * @param int|array $posts post ID(s)
	 * @param int $offset_in_minutes optional. delay (from right now) of cron job
	 */
	function schedule_sync_for_post( $posts, $offset_in_minutes = 0 ) {

		$this->_schedule_job_for_post( $this->sync_cron_hook, $posts, $offset_in_minutes );

	}

	/**
	 *
	 * @param string $beer_name
	 */
	function search( $beer_name ) {}

	/**
	 * For a given post ID, search the service for it and associate data if found
	 *
	 * @param int $post_id
	 * @return bool success
	 */
	function search_for_post( $post_id ) {

		$post = get_post( $post_id );

		$beer_name = $post->post_title;

		$search_results = $this->search( $beer_name );

		if ( is_array( $search_results ) ) {

			$beer = array_shift( $search_results );

			if ( $beer ) {

				$this->map_post_to_beer( $post_id, $beer );

			}

			return true;

		}

		return false;

	}

	/**
	 *
	 * @param int $post_id
	 */
	function sync_post_beer_info( $post_id ) {}

}