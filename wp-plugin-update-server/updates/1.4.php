<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wppus_update_to_1_4() {
	global $wpdb;

	$executed = false;

	set_transient( 'wppus_flush', 1, 60 );
	update_option( 'wppus_license_private_api_auth_key', get_option( 'lic_creation_secret', uniqid( '', true ) ), true );

	if ( ! get_option( 'wppus_use_licenses' ) ) {
		update_option( 'wppus_use_licenses', get_option( 'wppus_use_license_server', true ) );
		delete_option( 'wppus_use_license_server' );
	}

	if ( ! get_option( 'wppus_license_hmac_key' ) ) {
		update_option( 'wppus_license_hmac_key', get_option( 'wppus_hmac_key', uniqid( '', true ) ), true );
		delete_option( 'wppus_hmac_key' );
	}

	if ( ! get_option( 'wppus_license_crypto_key' ) ) {
		update_option( 'wppus_license_crypto_key', get_option( 'wppus_crypto_key', uniqid( '', true ) ), true );
		delete_option( 'wppus_crypto_key' );
	}

	delete_option( 'wppus_license_server_url' );

	// @todo remove
	update_option( 'wppus_hmac_key', 'test' );
	update_option( 'wppus_crypto_key', 'test' );
	update_option( 'wppus_license_server_url', 'https://froger.me' );
	update_option( 'wppus_use_license_server', true );

	$executed = WP_Plugin_Update_Server::maybe_create_or_upgrade_db();
	$sql      = "SELECT COUNT(*) FROM {$wpdb->prefix}wppus_licenses;";
	$count    = $wpdb->get_var( $sql ); // @codingStandardsIgnoreLine

	if ( ! is_null( $count ) && 0 === absint( $count ) ) {
		$executed = $executed && wppus_migrate_slm_data();
	}

	if ( ! $executed ) {
		global $wpdb;

		$error_message = __( "<p>An error occurred while updating the database. Please delete the tables <code>{$wpdb->prefix}wppus_licenses</code> and <code>{$wpdb->prefix}wppus_licenses_domains</code> and reload the page. If the problem persists, reinstall the previous version of the plugin and contact the author.</p>", 'wppus' ); // @codingStandardsIgnoreLine
		$executed      = new WP_Error( __FUNCTION__, $error_message );
	} else {
		add_filter( 'wpum_update_success_extra_notice_hooks', function( $notices ) {
			$notices[] = 'wppus_update_to_1_4_whats_new';

			return $notices;
		} );
	}

	return $executed;
}

function wppus_update_to_1_4_whats_new() {
	$class = 'notice notice-info is-dismissible';
	// translators: %1$s is the package version to update to
	$title    = __( 'What\'s new in version 1.4?', 'wppus' );
	$content  = '<p>' . __( 'The update includes the following changes:', 'wppus' ) . '</p>';
	$content .= '<ul>';
	$content .= '<li>' . __( '- Create database structure for license management. Note: if you have any debugging tool enabled and see 2 errors "table xxx doesn\'t exist" only once after the update, do not panic: this is a WordPress core issue and will not affect the plugin. For more infomation, see this <a href="https://wordpress.stackexchange.com/questions/141971/why-does-dbdelta-not-catch-mysqlerrors" target="_blank">WordPress Stack Exchange post</a>.', 'wppus' ) . '</li>';
	$content .= '<li>' . __( '- Migration of all Software License Manager settings and data to WP Plugin Update Server.', 'wppus' ) . '</li>';
	$content .= '</ul>';
	$message  = '<h4>' . $title . '</h4><p>' . $content . '</p>';

	printf( '<div class="%1$s">%2$s</div>', $class, $message ); // @codingStandardsIgnoreLine
}

function wppus_migrate_slm_data() {
	global $wpdb;

	$result = true;

	if ( $wpdb->prefix . 'lic_key_tbl' === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'lic_key_tbl' ) ) ) {

		$sql = "INSERT INTO {$wpdb->prefix}wppus_licenses (id, license_key, max_allowed_domains, allowed_domains, status, owner_name, email, company_name, txn_id, date_created, date_renewed, date_expiry, package_slug, package_type) 
				SELECT id, license_key, max_allowed_domains, '', lic_status, CONCAT(first_name, ' ', last_name), email, company_name, txn_id, date_created, date_renewed, date_expiry, product_ref, ''
				FROM {$wpdb->prefix}lic_key_tbl;";

		$result = $result && ( false !== $wpdb->query( $sql ) ); // @codingStandardsIgnoreLine

		$sql  = "SELECT id, package_slug FROM {$wpdb->prefix}wppus_licenses;";
		$rows = $wpdb->get_results( $sql ); // @codingStandardsIgnoreLine

		if ( ! empty( $rows ) ) {

			foreach ( $rows as $row ) {
				$id   = $row->id;
				$slug = preg_replace( '/\/.*.php/', '', $row->package_slug );
				$type = ( false !== strpos( $row->package_slug, 'functions.php' ) ) ? 'theme' : 'plugin';
				$sql  = "UPDATE {$wpdb->prefix}wppus_licenses SET package_slug = %s, package_type = %s WHERE id = %d;";

				$result = $result && ( false !== $wpdb->query( $wpdb->prepare( $sql, $slug, $type, $id ) ) ); // @codingStandardsIgnoreLine
			}
		}

		$sql = "UPDATE {$wpdb->prefix}wppus_licenses SET status = 'activated' WHERE status = '';";

		$result = $result && ( false !== $wpdb->query( $sql ) ); // @codingStandardsIgnoreLine
	}

	if ( $wpdb->prefix . 'lic_reg_domain_tbl' === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'lic_reg_domain_tbl' ) ) ) {

		$sql  = "SELECT id, lic_key_id, lic_key, registered_domain, item_reference
			FROM {$wpdb->prefix}lic_reg_domain_tbl;";
		$rows = $wpdb->get_results( $sql ); // @codingStandardsIgnoreLine

		if ( ! empty( $rows ) ) {
			$values = array();

			foreach ( $rows as $row ) {
				$id     = $row->lic_key_id;
				$domain = $row->registered_domain;

				if ( ! isset( $values[ $id ] ) || ! is_array( $values[ $id ] ) ) {
					$values[ $id ] = array();
				}

				$values[ $id ][] = $domain;
			}

			if ( ! empty( $values ) ) {

				foreach ( $values as $id => $domains ) {
					$domains = maybe_serialize( $domains );

					$sql = "UPDATE {$wpdb->prefix}wppus_licenses SET allowed_domains = %s WHERE id = %d;";

					$result = $result && ( false !== $wpdb->query( $wpdb->prepare( $sql, $domains, $id ) ) ); // @codingStandardsIgnoreLine
				}
			}
		}
	}

	return $result;
}
