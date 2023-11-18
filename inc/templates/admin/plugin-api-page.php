<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<?php WP_Packages_Update_Server::get_instance()->display_settings_header( $result ); ?>
	<form autocomplete="off" id="wppus-api-settings" action="" method="post">
		<h3><?php esc_html_e( 'Package Private API', 'wppus' ); ?></h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="wppus_package_private_api_auth_keys"><?php esc_html_e( 'Authentication Keys', 'wppus' ); ?></label>
				</th>
				<td>
					<div class="api-keys-multiple">
						<div class="api-keys-items empty">
						</div>
						<div>
							<input type="text" class="new-api-key-item-name" placeholder="<?php esc_attr_e( 'Package Key Name' ); ?>">
							<button disabled="disabled" class="api-keys-add button" type="button"><?php esc_html_e( 'Add a Package API Key' ); ?></button>
						</div>
						<input type="hidden" class="api-key-values" id="wppus_package_private_api_auth_keys" name="wppus_package_private_api_auth_keys" value="<?php echo esc_attr( get_option( 'wppus_package_private_api_auth_keys', '{}' ) ); ?>">
					</div>
					<p class="description">
						<?php esc_html_e( 'Used to authenticate package administration requests (browse, read, edit, add, delete), requests for signed URLs of package, and requests for tokens & true nonces.', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Keep these keys secret, do not share any of them with customers!', 'wppus' ); ?></strong>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_package_private_api_ip_whitelist"><?php esc_html_e( 'IP Whitelist', 'wppus' ); ?></label>
				</th>
				<td>
					<textarea class="ip-whitelist" id="wppus_package_private_api_ip_whitelist" name="wppus_package_private_api_ip_whitelist"><?php echo esc_html( implode( "\n", get_option( 'wppus_package_private_api_ip_whitelist', array() ) ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'List of IP addresses and/or CIDRs of remote sites authorised to use the Private API (one IP address or CIDR per line).', 'wprus' ); ?> <br/>
						<?php esc_html_e( 'Leave blank to accept any IP address (not recommended).', 'wprus' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<hr>
		<h3><?php esc_html_e( 'License Private API', 'wppus' ); ?></h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="wppus_license_private_api_auth_keys"><?php esc_html_e( 'Authentication Keys', 'wppus' ); ?></label>
				</th>
				<td>
					<div class="api-keys-multiple">
						<div class="api-keys-items empty">
						</div>
						<div>
							<input type="text" class="new-api-key-item-name" placeholder="<?php esc_attr_e( 'License Key Name' ); ?>">
							<button disabled="disabled" class="api-keys-add button" type="button"><?php esc_html_e( 'Add a License API Key' ); ?></button>
						</div>
						<input type="hidden" class="api-key-values" id="wppus_license_private_api_auth_keys" name="wppus_license_private_api_auth_keys" value="<?php echo esc_attr( get_option( 'wppus_license_private_api_auth_keys', '{}' ) ); ?>">
					</div>
					<p class="description">
						<?php esc_html_e( 'Used to authenticate license administration requests (browse, read, edit, add, delete).', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Keep these keys secret, do not share any of them with customers!', 'wppus' ); ?></strong>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_license_private_api_ip_whitelist"><?php esc_html_e( 'IP Whitelist', 'wppus' ); ?></label>
				</th>
				<td>
					<textarea class="ip-whitelist" id="wppus_license_private_api_ip_whitelist" name="wppus_license_private_api_ip_whitelist"><?php echo esc_html( implode( "\n", get_option( 'wppus_license_private_api_ip_whitelist', array() ) ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'List of IP addresses and/or CIDRs of remote sites authorised to use the Private API (one IP address or CIDR per line).', 'wprus' ); ?> <br/>
						<?php esc_html_e( 'Leave blank to accept any IP address (not recommended).', 'wprus' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<hr>
		<?php wp_nonce_field( 'wppus_plugin_options', 'wppus_plugin_options_handler_nonce' ); ?>
		<p class="submit">
			<input type="submit" name="wppus_options_save" value="<?php esc_attr_e( 'Save', 'wppus' ); ?>" class="button button-primary" />
		</p>
	</form>
</div>