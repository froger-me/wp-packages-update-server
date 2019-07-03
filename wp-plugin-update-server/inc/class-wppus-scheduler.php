<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Scheduler {

	protected $cleanable_datatypes  = array();
	protected $persistent_datatypes = array();

	public function __construct( $cleanable_datatypes = array(), $persistent_datatypes = array() ) {
		$this->cleanable_datatypes  = $cleanable_datatypes;
		$this->persistent_datatypes = $persistent_datatypes;
	}

	public function register_renew_download_url_token_schedule() {
		$scheduled_hook = array( 'WPPUS_Update_Manager', 'renew_download_url_token' );

		add_action( 'wppus_renew_download_url_token', $scheduled_hook, 10, 2 );
		do_action( 'wppus_registered_renew_download_url_token_schedule', $scheduled_hook );
	}

	public function clear_renew_download_url_token_schedule() {
		$scheduled_hook = array( 'WPPUS_Update_Manager', 'renew_download_url_token' );

		wp_clear_scheduled_hook( 'wppus_renew_download_url_token' );
		do_action( 'wppus_cleared_renew_download_url_token_schedule', $scheduled_hook );
	}

	public function register_renew_download_url_token_event() {
		$hook = 'wppus_renew_download_url_token';

		if ( ! wp_next_scheduled( $hook ) ) {
			$frequency = apply_filters( 'wppus_schedule_renew_download_url_token_frequency', 'daily' );
			$timestamp = strtotime( 'today midnight' );
			$result    = wp_schedule_event( $timestamp, $frequency, $hook );

			do_action( 'wppus_scheduled_renew_download_url_token_event', $result, $timestamp, $frequency, $hook );
		}
	}

	public function clear_remote_check_schedule( $slug ) {
		$params         = array( $slug );
		$scheduled_hook = 'wppus_check_remote_' . $slug;

		wp_clear_scheduled_hook( $scheduled_hook, $params );
		do_action( 'wppus_cleared_check_remote_schedule', $slug, $scheduled_hook, $params );
	}

	public function register_remote_check_event( $slug, $frequency ) {
		$hook = 'wppus_check_remote_' . $slug;

		if ( ! wp_next_scheduled( $hook, array( $slug ) ) ) {
			$params    = array( $slug );
			$frequency = apply_filters( 'wppus_check_remote_frequency', $frequency, $slug );
			$timestamp = current_time( 'timestamp' );
			$result    = wp_schedule_event( $timestamp, $frequency, $hook, $params );

			do_action( 'wppus_scheduled_check_remote_event', $result, $slug, $timestamp, $frequency, $hook, $params );
		}
	}

	public function register_remote_check_schedules() {

		return $this->remote_check_schedules_alter( 'register' );
	}

	public function clear_remote_check_schedules() {

		return $this->remote_check_schedules_alter( 'clear' );
	}

	public function reschedule_remote_check_events( $frequency ) {

		if ( WPPUS_Update_API::is_doing_api_request() ) {

			return false;
		}

		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			return;
		}

		$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );

		if ( $wp_filesystem->is_dir( $package_directory ) ) {
			$package_paths = glob( trailingslashit( $package_directory ) . '*.zip' );

			if ( ! empty( $package_paths ) ) {

				foreach ( $package_paths as $package_path ) {
					$package_path_parts = explode( '/', $package_path );
					$safe_slug          = str_replace( '.zip', '', end( $package_path_parts ) );

					$this->clear_remote_check_schedule( $safe_slug );
					$this->register_remote_check_event( $safe_slug, $frequency );
				}

				return true;
			}
		}

		return false;
	}

	public function register_license_schedules() {
		$scheduled_hook = array( 'WPPUS_License_Manager', 'expire_licenses' );

		add_action( 'wppus_expire_licenses', $scheduled_hook, 10, 2 );
		do_action( 'wppus_registered_license_schedule', $scheduled_hook );
	}

	public function clear_license_schedules() {
		$scheduled_hook = array( 'WPPUS_License_Manager', 'expire_licenses' );

		wp_clear_scheduled_hook( 'wppus_expire_licenses' );
		do_action( 'wppus_cleared_license_schedule', $scheduled_hook );
	}

	public function register_license_events() {
		$hook = 'wppus_expire_licenses';

		if ( ! wp_next_scheduled( $hook ) ) {
			$frequency = apply_filters( 'wppus_schedule_license_frequency', 'hourly' );
			$timestamp = current_time( 'timestamp' );
			$result    = wp_schedule_event( $timestamp, $frequency, $hook );

			do_action( 'wppus_scheduled_license_event', $result, $timestamp, $frequency, $hook );
		}
	}

	public function register_cleanup_schedules() {

		foreach ( $this->cleanable_datatypes as $type ) {
			$this->cleanup_schedules_alter( $type, 'register' );
		}
	}

	public function clear_cleanup_schedules() {

		foreach ( $this->cleanable_datatypes as $type ) {
			$this->cleanup_schedules_alter( $type, 'clear' );
		}
	}

	public function register_cleanup_events() {

		foreach ( $this->cleanable_datatypes as $type ) {
			$params = array( $type );
			$hook   = 'wppus_cleanup';

			if ( 'tmp' === $type ) {
				$params[] = true;
			}

			if ( ! wp_next_scheduled( $hook, $params ) ) {
				$frequency = apply_filters( 'wppus_schedule_cleanup_frequency', 'hourly', $type );
				$timestamp = current_time( 'timestamp' );
				$result    = wp_schedule_event( $timestamp, $frequency, $hook, $params );

				do_action( 'wppus_scheduled_cleanup_event', $result, $type, $timestamp, $frequency, $hook, $params );
			}
		}
	}

	protected function remote_check_schedules_alter( $action ) {

		if ( WPPUS_Update_API::is_doing_api_request() ) {

			return false;
		}

		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			return;
		}

		$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );

		if ( $wp_filesystem->is_dir( $package_directory ) ) {
			$package_paths = glob( trailingslashit( $package_directory ) . '*.zip' );

			if ( ! empty( $package_paths ) ) {

				foreach ( $package_paths as $package_path ) {
					$package_path_parts = explode( '/', $package_path );
					$slug               = str_replace( '.zip', '', end( $package_path_parts ) );

					switch ( $action ) {
						case 'register':
							$action_hook    = array( 'WPPUS_Update_API', 'maybe_download_remote_update' );
							$scheduled_hook = 'wppus_check_remote_' . $slug;

							add_action( 'wppus_check_remote_' . $slug, $action_hook, 10, 1 );
							do_action( 'wppus_registered_check_remote_schedule', $slug, $scheduled_hook, $action_hook );
							break;
						case 'clear':
							$scheduled_hook = 'wppus_check_remote_' . $slug;
							$params         = array( $slug );

							wp_clear_scheduled_hook( $scheduled_hook, $params );
							do_action( 'wppus_cleared_check_remote_schedule', $slug, $scheduled_hook, $params );
							break;
					}
				}

				return true;
			}
		}

		return false;
	}

	protected function cleanup_schedules_alter( $type, $action ) {

		if ( WPPUS_Update_API::is_doing_api_request() ) {

			return false;
		}

		$params = array( $type );

		if ( 'tmp' === $type ) {
			$params[] = true;
		}

		switch ( $action ) {
			case 'register':
				$hook = array( 'WPPUS_Data_Manager', 'maybe_cleanup' );

				add_action( 'wppus_cleanup', $hook, 10, 2 );
				do_action( 'wppus_registered_cleanup_schedule', $type, $params );
				break;
			case 'clear':
				wp_clear_scheduled_hook( 'wppus_cleanup', $params );
				do_action( 'wppus_cleared_cleanup_schedule', $type, $params );
				break;
		}
	}

}
