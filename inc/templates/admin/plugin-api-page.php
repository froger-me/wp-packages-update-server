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
		<h3><?php esc_html_e( 'Webhooks', 'wppus' ); ?></h3>
		<table class="form-table">
			<tr>
				<td colspan="2">
					<div class="webhook-multiple">
						<div class="webhook-items empty">
						</div>
						<div class="webhook-add-controls">
							<input type="text" class="new-webhook-item-url" placeholder="<?php esc_attr_e( 'Payload URL' ); ?>">
							<input type="text" class="new-webhook-item-secret" placeholder="<?php echo esc_attr( 'secret-key' ); ?>">
							<div class="webhook-event-types">
								<div class="webhook-event-container all">
									<label><input type="checkbox" data-webhook-event="all"> <?php esc_html_e( 'All events', 'wppus' ); ?></label>
								</div>
								<?php foreach ( $webhook_events as $top_event => $values ) : ?>
								<div class="webhook-event-container">
									<label class="top-level"><input type="checkbox" data-webhook-event="<?php echo esc_attr( $top_event ); ?>"> <?php echo esc_html( $values['label'] ); ?> <code>(<?php echo esc_html( $top_event ); ?>)</code></label>
									<?php if ( isset( $values['events'] ) && ! empty( $values['events'] ) ) : ?>
										<?php foreach ( $values['events'] as $event => $label ) : ?>
										<label class="child"><input type="checkbox" data-webhook-event="<?php echo esc_attr( $event ); ?>"> <?php echo esc_html( $label ); ?> <code>(<?php echo esc_html( $event ); ?>)</code></label>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
								<?php endforeach; ?>
							</div>
							<button disabled="disabled" class="webhook-add button" type="button"><?php esc_html_e( 'Add a Webhook' ); ?></button>
						</div>
						<input type="hidden" class="webhook-values" id="wppus_webhooks" name="wppus_webhooks" value="<?php echo esc_attr( get_option( 'wppus_webhooks', '{}' ) ); ?>">
					</div>
					<p class="description">
						<?php esc_html_e( 'Webhooks are event notifications sent to arbitrary URLs at next cronjob (1 min. latest after the event occured, depending on the server configuration) with a payload of data for third party services integration.', 'wppus' ); ?>
						<br>
						<?php
						printf(
							// translators: %1$s is <code>secret</code>, %2$s is <code>X-WPPUS-Signature</code>, %3$s is <code>X-WPPUS-Signature-256</code>
							esc_html__( 'To allow the recipients to authenticate the notifications, the payload is signed with a %1$s secret key using sha1 algorithm and sha256 algorithm ; the resulting hashes are made available in the %2$s and %3$s headers respectively.', 'wppus' ),
							'<code>secret-key</code>',
							'<code>X-WPPUS-Signature</code>',
							'<code>X-WPPUS-Signature-256</code>'
						);
						?>
						<br>
						<strong>
						<?php
						printf(
							// translators: %s is '<code>secret-key</code>'
							esc_html__( 'The %s must be at least 16 characters long, ideally a random string.', 'wppus' ),
							'<code>secret-key</code>'
						);
						?>
						</strong>
						<br>
						<?php
						printf(
							// translators: %s is <code>POST</code>
							esc_html__( 'The payload is sent in JSON format via a %s request.', 'wppus' ),
							'<code>POST</code>',
						);
						?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Only add URLs you trust!', 'wppus' ); ?></strong>
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