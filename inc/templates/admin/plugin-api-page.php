<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<?php WP_Packages_Update_Server::get_instance()->display_settings_header(); ?>
	<form autocomplete="off" id="wppus-api-settings" action="" method="post">
		<h3><?php esc_html_e( 'Packages Settings', 'wppus' ); ?></h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="wppus_package_private_api_auth_key"><?php esc_html_e( 'Private API Authentication Key', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text secret" type="password" autocomplete="new-password" id="wppus_package_private_api_auth_key" name="wppus_package_private_api_auth_key" value="<?php echo esc_attr( get_option( 'wppus_package_private_api_auth_key', 'private_api_auth_key' ) ); ?>">
					<p class="description">
						<?php esc_html_e( 'Ideally a random string - used to authenticate package administration requests (browse, read, edit, add, delete), requests for signed URLs of package, and requests for tokens & true nonces.', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Keep this key secret, do not share it with customers!', 'wppus' ); ?></strong>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_package_private_api_ip_whitelist"><?php esc_html_e( 'Private API IP Whitelist', 'wppus' ); ?></label>
				</th>
				<td>
					<textarea id="wppus_package_private_api_ip_whitelist" name="wppus_package_private_api_ip_whitelist"><?php echo esc_html( implode( "\n", get_option( 'wppus_package_private_api_ip_whitelist', array() ) ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'List of IP addresses and/or CIDRs of remote sites authorised to use the Private API (one IP address or CIDR per line).', 'wprus' ); ?> <br/>
						<?php esc_html_e( 'Leave blank to accept any IP address (not recommended).', 'wprus' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<hr>
		<h3><?php esc_html_e( 'License Settings', 'wppus' ); ?></h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="wppus_license_private_api_auth_key"><?php esc_html_e( 'Private API Authentication Key', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text secret" type="password" autocomplete="new-password" id="wppus_license_private_api_auth_key" name="wppus_license_private_api_auth_key" value="<?php echo esc_attr( get_option( 'wppus_license_private_api_auth_key', 'private_api_auth_key' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Ideally a random string - used to authenticate license administration requests (browse, read, edit, add, delete).', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Keep this key secret, do not share it with customers!', 'wppus' ); ?></strong>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_license_private_api_ip_whitelist"><?php esc_html_e( 'Private API IP Whitelist', 'wppus' ); ?></label>
				</th>
				<td>
					<textarea id="wppus_package_private_api_ip_whitelist" name="wppus_license_private_api_ip_whitelist"><?php echo esc_html( implode( "\n", get_option( 'wppus_license_private_api_ip_whitelist', array() ) ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'List of IP addresses and/or CIDRs of remote sites authorised to use the Private API (one IP address or CIDR per line).', 'wprus' ); ?> <br/>
						<?php esc_html_e( 'Leave blank to accept any IP address (not recommended).', 'wprus' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'wppus_plugin_options', 'wppus_plugin_options_handler_nonce' ); ?>
		<p class="submit">
			<input type="submit" name="wppus_options_save" value="<?php esc_attr_e( 'Save', 'wppus' ); ?>" class="button button-primary" />
		</p>
	</form>
</div>