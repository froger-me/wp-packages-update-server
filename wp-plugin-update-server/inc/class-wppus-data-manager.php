<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Data_Manager {
	public static $transient_data_dirs = array(
		'cache',
		'logs',
		'tmp',
	);

	public static $persistent_data_dirs = array(
		'packages',
	);

	public static $transient_data_db = array(
		'update_from_remote_locks',
	);

	protected static $root_data_dirname = 'wppus';

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {

			$this->scheduler = new WPPUS_Scheduler(
				array_merge( self::$transient_data_dirs, self::$transient_data_db ),
				self::$persistent_data_dirs
			);

			add_action( 'init', array( $this->scheduler, 'register_cleanup_events' ), 10, 0 );
			add_action( 'init', array( $this->scheduler, 'register_cleanup_schedules' ), 10, 0 );
		}
	}

	public static function clear_schedules() {
		$scheduler = new WPPUS_Scheduler(
			array_merge( self::$transient_data_dirs, self::$transient_data_db ),
			self::$persistent_data_dirs
		);

		$scheduler->clear_cleanup_schedules();
	}

	public static function register_schedules() {
		$scheduler = new WPPUS_Scheduler(
			array_merge( self::$transient_data_dirs, self::$transient_data_db ),
			self::$persistent_data_dirs
		);

		$scheduler->register_cleanup_events();
	}

	public static function maybe_setup_directories() {
		$root_dir = self::get_data_dir();
		$result   = true;

		global $wp_filesystem;

		if ( ! $wp_filesystem->is_dir( $root_dir ) ) {
			$result = $result && self::create_data_dir( 'wpppus', false, true );
		}

		if ( $result ) {

			foreach ( array_merge( self::$transient_data_dirs, self::$persistent_data_dirs ) as $directory ) {

				if ( ! $wp_filesystem->is_dir( $root_dir . DIRECTORY_SEPARATOR . $directory ) ) {
					$result = $result && self::create_data_dir( $directory );
				}
			}
		}

		return $result;
	}

	public static function get_data_dir( $dir = 'root' ) {
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			wp_die( 'File system not available.', __METHOD__ );
		}

		$data_dir = trailingslashit( $wp_filesystem->wp_content_dir() . self::$root_data_dirname );

		if ( 'root' !== $dir ) {

			if ( ! self::is_valid_data_dir( $dir ) ) {
				// translators: %1$s is the path to the plugin's data directory
				$error_message = sprintf( __( 'Directory <code>%1$s</code> is not a valid WPPUS data directory.', 'wppus' ), $dir );

				wp_die( $error_message, __METHOD__ ); // @codingStandardsIgnoreLine
			}

			$data_dir .= $dir;
		}

		return trailingslashit( $data_dir );
	}

	public static function is_valid_data_dir( $dir, $require_persistent = false ) {
		$is_valid = false;

		if ( ! $require_persistent ) {
			$is_valid = in_array( $dir, array_merge( self::$transient_data_dirs, self::$persistent_data_dirs ), true );
		} else {
			$is_valid = in_array( $dir, self::$persistent_data_dirs, true );
		}

		return $is_valid;
	}

	public static function maybe_cleanup_data_dir( $type, $force ) {
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			return false;
		}

		$directory              = self::get_data_dir( $type );
		$max_size_constant_name = 'WPPUS_DEFAULT_' . strtoupper( $type ) . '_MAX_SIZE';
		$default_max_size       = defined( $max_size_constant_name ) ? constant( $max_size_constant_name ) : 0;
		$cleanup                = false;
		$is_dir                 = $wp_filesystem->is_dir( $directory );
		$total_size             = 0;

		if ( $default_max_size && $is_dir && false === $force ) {
			$max_size = get_option( 'wppus_' . $type . '_max_size', $default_max_size );

			foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) ) as $file ) {
				$size = $file->getSize();

				if ( false !== $size ) {
					$total_size += $size;
				}
			}

			if ( $total_size >= ( $max_size * WPPUS_MB_TO_B ) ) {
				$cleanup = true;
			}
		}

		if ( $is_dir && ( $cleanup || $force ) ) {
			$result = true;
			$result = $result && $wp_filesystem->rmdir( $directory, true );
			$result = $result && $wp_filesystem->mkdir( $directory );

			if ( self::is_valid_data_dir( $type ) ) {
				$result = $result && self::generate_restricted_htaccess( $directory );
			}

			do_action( 'wppus_did_cleanup', $result, $type, $total_size, $force );

			return $result;
		}

		return false;
	}

	public static function maybe_cleanup( $type, $force = false ) {

		if ( in_array( $type, self::$transient_data_db ) ) { // @codingStandardsIgnoreLine
			$method_name = 'maybe_cleanup_' . $type;

			if ( method_exists( get_called_class(), $method_name ) && ! $force ) {

				return call_user_func( array( get_called_class(), $method_name ) );
			} else {

				return delete_option( 'wppus_' . $type );
			}
		}

		if ( self::is_valid_data_dir( $type ) ) {

			return self::maybe_cleanup_data_dir( $type, $force );
		}

		return false;
	}

	protected static function maybe_cleanup_update_from_remote_locks() {
		$locks = get_option( 'wppus_update_from_remote_locks' );

		if ( is_array( $locks ) && ! empty( $locks ) ) {

			foreach ( $locks as $slug => $timestamp ) {

				if ( $timestamp <= current_time( 'timestamp' ) ) {
					unset( $locks[ $slug ] );
				}
			}

			update_option( 'wppus_update_from_remote_locks', $locks );
		}
	}

	protected static function create_data_dir( $name, $include_htaccess = true, $is_root_dir = false ) {
		global $wp_filesystem;

		$root_dir = self::get_data_dir();
		$path     = ( $is_root_dir ) ? $root_dir : $root_dir . $name;

		$result = $wp_filesystem->mkdir( $path );

		if ( $result && $include_htaccess ) {
			self::generate_restricted_htaccess( $path );
		}

		return $result;
	}

	protected static function generate_restricted_htaccess( $directory ) {
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			return;
		}

		$contents = "Order deny,allow\nDeny from all";
		$htaccess = trailingslashit( $directory ) . '.htaccess';

		$wp_filesystem->touch( $htaccess );

		return $wp_filesystem->put_contents( $htaccess, $contents, 0644 );
	}
}
