<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Zip_Package_Manager {

	protected $package_slug;
	protected $received_package_path;
	protected $tmp_dir;
	protected $packages_dir;

	public function __construct( $package_slug, $received_package_path, $tmp_dir, $packages_dir ) {
		$this->package_slug          = $package_slug;
		$this->received_package_path = $received_package_path;
		$this->tmp_dir               = $tmp_dir;
		$this->packages_dir          = $packages_dir;
	}

	public function clean_package() {
		WP_Filesystem();

		global $wp_filesystem;

		$return        = true;
		$error_message = __METHOD__ . ': ';

		if ( is_wp_error( $this->received_package_path ) ) {
			$return         = false;
			$error_message .= $this->received_package_path->get_error_message();
		}

		if ( $return && ! $this->received_package_path ) {
			$return         = false;
			$error_message .= __( 'The received package path cannot be empty.', 'wppus' );
		}

		if ( $return && ! $wp_filesystem ) {
			$return         = false;
			$error_message .= __( 'Unavailable file system.', 'wppus' );
		}

		if ( $return ) {
			$source      = $this->received_package_path;
			$destination = $this->tmp_dir . $this->package_slug . '.zip';
			$result      = $wp_filesystem->move( $source, $destination, true );

			if ( $result ) {
				$repack_result = $this->repack_package();

				if ( ! $repack_result ) {
					$return         = false;
					$error_message .= sprintf( // @codingStandardsIgnoreLine
						'Could not repack %s.',
						esc_html( $destination )
					);
				} else {
					$return = $repack_result;
				}
			} else {
				$return         = false;
				$error_message .= sprintf( // @codingStandardsIgnoreLine
					'Could not move %s to %s.',
					esc_html( $source ),
					esc_html( $destination )
				);
			}
		}

		if ( $return ) {
			$source      = $this->tmp_dir . $this->package_slug . '.zip';
			$destination = trailingslashit( $this->packages_dir ) . $this->package_slug . '.zip'; // @codingStandardsIgnoreLine
			$result      = $wp_filesystem->move( $source, $destination, true );

			if ( ! $result ) {
				$return         = false;
				$error_message .= sprintf( // @codingStandardsIgnoreLine
					'Could not move %s to %s.',
					esc_html( $source ),
					esc_html( $destination )
				);
			}
		}

		if ( ! $return ) {

			if ( (bool) ( constant( 'WP_DEBUG' ) ) ) {
				trigger_error( $error_message, E_USER_WARNING ); // @codingStandardsIgnoreLine
			}

			error_log( $error_message ); // @codingStandardsIgnoreLine

			$wp_filesystem->delete( $this->received_package_path, true );
		}

		return $return;
	}

	protected function repack_package() {
		WP_Filesystem();

		global $wp_filesystem;

		$temp_path    = trailingslashit( $this->tmp_dir . $this->package_slug );
		$archive_path = $this->tmp_dir . $this->package_slug . '.zip';

		if ( ! is_dir( $temp_path ) ) {
			$wp_filesystem->mkdir( $temp_path );
			$wp_filesystem->chmod( $temp_path, 0755, true );
		}

		$unzipped      = self::unzip_package( $archive_path, $temp_path );
		$return        = true;
		$error_message = __METHOD__ . ': ';

		$wp_filesystem->delete( $archive_path, true );

		if ( ! $unzipped ) {
			$return         = false;
			$error_message .= sprintf( // @codingStandardsIgnoreLine
				'Could not unzip %s.',
				esc_html( $archive_path )
			);
		} else {
			$content         = array_diff( scandir( $temp_path ), array( '..', '.' ) );
			$maybe_directory = $temp_path . reset( $content );

			if ( ( 1 === count( $content ) && is_dir( $maybe_directory ) ) ) {
				$directory = $maybe_directory;

				$wp_filesystem->move( $directory, $temp_path . $this->package_slug, true );
				$wp_filesystem->chmod( $temp_path, false, true );

				do_action( 'wppus_before_remote_package_zip', $this->package_slug, $temp_path, $archive_path );

				$zipped = self::zip_package( $temp_path, $archive_path );

				if ( $zipped ) {
					$wp_filesystem->chmod( $archive_path, 0755 );
				} else {
					$return         = false;
					$error_message .= sprintf( // @codingStandardsIgnoreLine
						'Could not create archive from %s to %s - zipping failed',
						esc_html( $temp_path ),
						esc_html( $zip )
					);
				}
			} else {
				$return         = false;
				$error_message .= sprintf( // @codingStandardsIgnoreLine
					'Could not create archive for %s - invalid remote package (must contain only one directory)',
					esc_html( $package ),
					esc_html( trailingslashit( dirname( $package ) ) . $this->package_slug . '.zip' )
				);
			}
		}

		$wp_filesystem->delete( $temp_path, true );

		if ( ! $return ) {

			if ( (bool) ( constant( 'WP_DEBUG' ) ) ) {
				trigger_error( $error_message, E_USER_WARNING ); // @codingStandardsIgnoreLine
			}

			error_log( $error_message ); // @codingStandardsIgnoreLine
		}

		return $return;
	}

	public static function unzip_package( $source, $destination ) {

		return unzip_file( $source, $destination );
	}

	public static function zip_package( $source, $destination, $container_dir = '' ) {
		global $wp_filesystem;

		$zip = new ZipArchive();

		if ( ! $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {

			return false;
		}

		if ( ! empty( $container_dir ) ) {
			$container_dir = trailingslashit( $container_dir );
		}

		$source = str_replace( '\\', '/', realpath( $source ) );

		if ( true === $wp_filesystem->is_dir( $source ) ) {

			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator(
					$source
				)
			);

			$it->rewind();

			while ( $it->valid() ) {

				if ( ! $it->isDot() ) {
					$file      = str_replace( '\\', '/', $it->key() );
					$file_name = $it->getSubPathName();

					if ( true === $wp_filesystem->is_dir( $file ) ) {
						$dir_name = $container_dir . trailingslashit( $file_name );

						$zip->addEmptyDir( $dir_name );
					} elseif ( true === $wp_filesystem->is_file( $file ) ) {
						$zip->addFromString( $container_dir . $file_name, $wp_filesystem->get_contents( $file ) );
					}
				}

				$it->next();
			}
		} elseif ( true === $wp_filesystem->is_file( $source ) && '.' !== $file && '..' !== $file ) {
			$file_name = str_replace( ' ', '', basename( $source ) );

			if ( ! empty( $file_name ) ) {
				$zip->addFromString( $file_name, $wp_filesystem->get_contents( $source ) );
			}
		}

		return $zip->close();
	}
}
