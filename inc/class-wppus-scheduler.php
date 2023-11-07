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

	public function reschedule_remote_check_recurring_events( $frequency ) {

		if ( WPPUS_Update_API::is_doing_api_request() ) {

			return false;
		}

		$slugs = $this->get_package_slugs();

		if ( ! empty( $slugs ) ) {

			foreach ( $slugs as $slug ) {
				$hook = 'wppus_check_remote_' . $slug;

				$this->clear_remote_check_schedule( $slug, null, false );

				if ( ! wp_next_scheduled( $hook, array( $slug, null, false ) ) ) {
					$params    = array( $slug, null, false );
					$frequency = apply_filters( 'wppus_check_remote_frequency', $frequency, $slug );
					$timestamp = time();
					$result    = wp_schedule_event( $timestamp, $frequency, $hook, $params );

					do_action(
						'wppus_scheduled_check_remote_event',
						$result,
						$slug,
						$timestamp,
						$frequency,
						$hook,
						$params
					);
				}
			}

			return true;
		}

		return false;
	}

	public function register_remote_check_recurring_event( $slug, $frequency ) {
		$hook = 'wppus_check_remote_' . $slug;

		if ( ! wp_next_scheduled( $hook, array( $slug, null, false ) ) ) {
			$params    = array( $slug, null, false );
			$frequency = apply_filters( 'wppus_check_remote_frequency', $frequency, $slug );
			$timestamp = time();
			$result    = wp_schedule_event( $timestamp, $frequency, $hook, $params );

			do_action( 'wppus_scheduled_check_remote_event', $result, $slug, $timestamp, $frequency, $hook, $params );
		}
	}

	public function register_remote_check_single_event( $slug, $type, $delay ) {
		$hook = 'wppus_check_remote_' . $slug;

		if ( ! wp_next_scheduled( $hook, array( $slug, $type, true ) ) ) {
			$params    = array( $slug, $type, true );
			$delay     = apply_filters( 'wppus_check_remote_delay', $delay, $slug );
			$timestamp = time() + ( abs( intval( $delay ) ) * MINUTE_IN_SECONDS );
			$result    = wp_schedule_single_event( $timestamp, $hook, $params );

			do_action( 'wppus_scheduled_check_remote_event', $result, $slug, $timestamp, false, $hook, $params );
		}
	}

	public function clear_remote_check_schedule( $slug, $type, $force = false ) {
		$params         = array( $slug, $type, $force );
		$scheduled_hook = 'wppus_check_remote_' . $slug;

		wp_clear_scheduled_hook( $scheduled_hook, $params );
		do_action( 'wppus_cleared_check_remote_schedule', $slug, $scheduled_hook, $params );
	}

	public function register_remote_check_scheduled_hooks() {
		$result = false;

		if ( ! WPPUS_Update_API::is_doing_api_request() ) {
			$slugs = $this->get_package_slugs();

			if ( ! empty( $slugs ) ) {
				$api         = WPPUS_Update_API::get_instance();
				$action_hook = array( $api, 'download_remote_package' );

				foreach ( $slugs as $slug ) {
					add_action( 'wppus_check_remote_' . $slug, $action_hook, 10, 3 );
					do_action(
						'wppus_registered_check_remote_schedule',
						$slug,
						'wppus_check_remote_' . $slug,
						$action_hook
					);
				}

				$result = true;
			}
		}

		return $result;
	}

	public function clear_remote_check_scheduled_hooks() {
		$result = false;

		if ( ! WPPUS_Update_API::is_doing_api_request() ) {
			$slugs = $this->get_package_slugs();

			if ( ! empty( $slugs ) ) {

				foreach ( $slugs as $slug ) {
					$scheduled_hook = 'wppus_check_remote_' . $slug;
					$params         = array( $slug, null, false );

					wp_clear_scheduled_hook( $scheduled_hook, $params );
					do_action( 'wppus_cleared_check_remote_schedule', $slug, $scheduled_hook, $params );
				}

				$result = true;
			}
		}

		return $result;
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
			$timestamp = time();
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
				$timestamp = time();
				$result    = wp_schedule_event( $timestamp, $frequency, $hook, $params );

				do_action(
					'wppus_scheduled_cleanup_event',
					$result,
					$type,
					$timestamp,
					$frequency,
					$hook,
					$params
				);
			}
		}
	}

	protected function get_package_slugs() {
		$slugs = wp_cache_get( 'package_slugs', 'wppus' );

		if ( false === $slugs ) {
			WP_Filesystem();

			global $wp_filesystem;

			$slugs = array();

			if ( $wp_filesystem ) {
				$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );

				if ( $wp_filesystem->is_dir( $package_directory ) ) {
					$package_paths = glob( trailingslashit( $package_directory ) . '*.zip' );

					if ( ! empty( $package_paths ) ) {

						foreach ( $package_paths as $package_path ) {
							$package_path_parts = explode( '/', $package_path );
							$slugs[]            = str_replace( '.zip', '', end( $package_path_parts ) );
						}
					}
				}
			}

			// @todo doc
			$slugs = apply_filters( 'wppus_scheduler_get_package_slugs', $slugs );

			wp_cache_set( 'package_slugs', $slugs, 'wppus' );
		}

		return $slugs;
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
