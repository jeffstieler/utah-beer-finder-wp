<?php

abstract class Base_Beer_Service {

	protected $post_type;
	protected $service_name;
	protected $titan;
	protected $searched_flag;
	protected $searching_flag;
	protected $search_all_cron_hook;
	protected $synced_flag;
	protected $sync_all_cron_hook;
	protected $sync_cron_hook;

	function __construct( $post_type ) {

		$this->post_type = $post_type;

		$this->searched_flag = "has-{$this->service_name}-searched";

		$this->searching_flag = "is-{$this->service_name}-searching";

		$this->search_all_cron_hook = $this->service_name . '_search_all';

		$this->synced_flag = "has-{$this->service_name}-sync";

		$this->sync_cron_hook = 'sync_' . $this->service_name;

		$this->sync_all_cron_hook = $this->service_name . '_sync_all';

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

	/**
	 * Add non-standard cron schedules for use in beer services
	 *
	 * @param array $schedules list of registered cron schedules
	 * @return array filtered cron schedules
	 */
	function add_cron_schedules( $schedules ) {

		$schedules['everytwominutes'] = array(
			'interval' => 2 * MINUTE_IN_SECONDS,
			'display' => __( 'Every Two Minutes' )
		);

		return $schedules;

	}

	function attach_hooks() {

		add_action( $this->sync_cron_hook, array( $this, 'cron_sync_post_beer_info' ) );

		add_action( $this->search_all_cron_hook, array( $this, 'schedule_search_for_all_posts' ) );

		add_action( $this->sync_all_cron_hook, array( $this, 'schedule_sync_for_all_posts' ) );

		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules') );

	}

	/**
	 * Clear a beer post's flag for being currently searched
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function clear_post_as_being_searched( $post_id ) {

		return (bool) delete_post_meta( $post_id, $this->searched_flag );

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

		$this->schedule_jobs();

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
	 * Flag a beer as being currently searched for with the service
	 *
	 * @param int $post_id beer post ID
	 * @return bool success
	 */
	function mark_post_as_being_searched( $post_id ) {

		return (bool) update_post_meta( $post_id, $this->searching_flag, true );

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
	 * Setup recurring search/sync for the service
	 */
	function schedule_jobs() {

		wp_schedule_event( time(), 'everytwominutes', $this->search_all_cron_hook );

		wp_schedule_event( time(), 'twicedaily', $this->sync_all_cron_hook );

	}

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
				),
				array(
					'key'     => $this->searching_flag,
					'value'   => '',
					'compare' => 'NOT EXISTS'
				),
			),
			'no_found_rows'  => true,
			'posts_per_page' => 1,
			'fields'         => 'ids'
		) );

		if ( $unmapped_posts->posts ) {

			$post_id = (int) array_shift( $unmapped_posts->posts );

			$this->mark_post_as_being_searched( $post_id );

			$this->cron_map_post_to_beer( $post_id );

			$this->clear_post_as_being_searched( $post_id );

		}

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