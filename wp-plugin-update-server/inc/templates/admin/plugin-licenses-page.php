<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<h1><?php esc_html_e( 'WP Plugin Update Server', 'wppus' ); ?></h1>
	<?php if ( is_string( $result ) && ! empty( $result ) ) : ?>
		<div class="updated notice notice-success is-dismissible">
			<p>
				<?php echo esc_html( $result ); ?>
			</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.' ); ?></span>
			</button>
		</div>
	<?php elseif ( is_array( $result ) && ! empty( $result ) ) : ?>
		<div class="error notice notice-error is-dismissible">
			<ul>
				<?php foreach ( $result as $key => $message ) : ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.' ); ?></span>
			</button>
		</div>
	<?php endif; ?>
	<h2 class="nav-tab-wrapper">
		<a href="admin.php?page=wppus-page" class="nav-tab">
			<span class='dashicons dashicons-welcome-view-site'></span> <?php esc_html_e( 'Overview', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-remote-sources" class="nav-tab">
			<span class='dashicons dashicons-networking'></span> <?php esc_html_e( 'Remote Sources', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-licenses" class="nav-tab nav-tab-active">
			<span class='dashicons dashicons-admin-network'></span> <?php esc_html_e( 'Licenses', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-help" class="nav-tab">
			<span class='dashicons dashicons-editor-help'></span> <?php esc_html_e( 'Help', 'wppus' ); ?>
		</a>
	</h2>
	<?php if ( get_option( 'wppus_use_licenses' ) ) : ?>
	<form id="wppus-licenses-list" action="" method="post">
		<h3><?php esc_html_e( 'Licenses', 'wppus' ); ?></h3>
		<?php $licences_table->search_box( 'Search', 'wppus' ); ?>
		<?php $licences_table->display(); ?>
	</form>
	<div id="wppus_license_panel" class="postbox">
		<div class="inside">
			<form id="wppus_license" class="panel" action="" method="post">
				<h3><span class='wppus-add-license-label'><?php esc_html_e( 'Add License', 'wppus' ); ?></span><span class='wppus-edit-license-label'><?php esc_html_e( 'Edit License', 'wppus' ); ?> (ID <span id="wppus_license_id"></span>)</span><span class="small"> (<a class="close-panel reset" href="#"><?php esc_html_e( 'cancel', 'wppus' ); ?></a>)</span></h3>
				<div class="license-column-container">
					<div class="license-column">
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'License Key ', 'wppus' ); ?><span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></span></th>
								<td>
									<input type="text" id="wppus_license_key" data-random_key="<?php echo esc_html( bin2hex( openssl_random_pseudo_bytes( 12 ) ) ); ?>" name="wppus_license_key" class="no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The unique license key. This auto-generated value can be changed as long as it is unique in the database.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Package Type', 'wppus' ); ?></th>
								<td>
									<select id="wppus_license_package_type">
										<option value="plugin"><?php esc_html_e( 'Plugin' ); ?></option>
										<option value="theme"><?php esc_html_e( 'Theme' ); ?></option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Package Slug ', 'wppus' ); ?><span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_package_slug" name="wppus_license_package_slug" class="no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The plugin or theme slug. Only alphanumeric characters and dashes are allowed.', 'wppus' ); ?>
										<br/>
										<?php esc_html_e( 'Example of valid value: package-slug', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'License Status', 'wppus' ); ?></th>
								<td>
									<select id="wppus_license_status">
										<option value="pending"><?php esc_html_e( 'Pending', 'wppus' ); ?></option>
										<option value="activated"><?php esc_html_e( 'Activated', 'wppus' ); ?></option>
										<option value="deactivated"><?php esc_html_e( 'Deactivated', 'wppus' ); ?></option>
										<option value="blocked"><?php esc_html_e( 'Blocked', 'wppus' ); ?></option>
										<option value="expired"><?php esc_html_e( 'Expired', 'wppus' ); ?></option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Date Created ', 'wppus' ); ?><span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_date_created" name="wppus_license_date_created" class="wppus-license-date no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'Creation date of the license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top" class="wppus-license-show-if-edit">
								<th scope="row"><?php esc_html_e( 'Date Renewed', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_date_renewed" name="wppus_license_date_renewed" class="wppus-license-date no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'Date of the last time the license was renewed.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Expiry Date', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_date_expiry" name="wppus_license_date_expiry" class="wppus-license-date no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'Expiry date of the license. Leave empty for no expiry.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>
					<div class="license-column">
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Registered Email ', 'wppus' ); ?><span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="email" id="wppus_license_registered_email" name="wppus_license_registered_email" class="no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The email registered with this license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Owner Name ', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_owner_name" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The full name of the owner of the license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Company ', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_owner_company" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The company of the owner of this license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Max. Allowed Domains ', 'wppus' ); ?><span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="number" min="1" id="wppus_license_max_allowed_domains" name="wppus_license_max_allowed_domains" class="no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The maximum number of domains on which this license can be used.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top" class="wppus-license-show-if-edit">
								<th scope="row"><?php esc_html_e( 'Registered Domains ', 'wppus' ); ?></th>
								<td>
									<div id="wppus_license_registered_domains">
										<ul class="wppus-domains-list">
											<li class='wppus-domain-template'>
												<button type="button" class="wppus-remove-domain">
												<span class="wppus-remove-domain-icon" aria-hidden="true"></span>
												</button> <span class="wppus-domain-value"></span>
											</li>
										</ul>
										<span class="wppus-no-domain description"><?php esc_html_e( 'None', 'wppus' ); ?></span>
									</div>
									<p class="description">
										<?php esc_html_e( 'Domains currently allowed to use this license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Transaction ID ', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_transaction_id" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'If applicable, the transaction identifier associated to the purchase of the license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div class="license-form-actions clear">
					<?php wp_nonce_field( 'wppus_license_form_nonce', 'wppus_license_form_nonce' ); ?>
					<input type="hidden" id="wppus_license_values" name="wppus_license_values" value="">
					<input type="hidden" id="wppus_license_action" name="wppus_license_action" value="">
					<input type="submit" id="wppus_license_save" class="close-panel button button-primary" value="<?php esc_html_e( 'Save', 'wppus' ); ?>">
					<input type="button" id="wppus_license_cancel" class="close-panel button" value="<?php esc_html_e( 'Cancel', 'wppus' ); ?>">
				</div>
			</form>
		</div>
	</div>
	<hr>
	<?php endif; ?>
	<h3><?php esc_html_e( 'License Settings', 'wppus' ); ?></h3>
	<form id="wppus-licenses-settings" action="" method="post">
		<table class="form-table">
			<tr>
				<th>
					<label for="wppus_use_licenses"><?php esc_html_e( 'Enable Package Licenses', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="checkbox" id="wppus_use_licenses" name="wppus_use_licenses" value="1" <?php checked( get_option( 'wppus_use_licenses', 0 ), 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Check to activate license-enabled plugins and themes packages delivery.', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'It affects all the packages with a "Requires License" license status delivered by this installation of WP Plugin Update Server.', 'wppus' ); ?></strong>
						<br>
						<strong><?php esc_html_e( 'Settings of the "Licenses" section will be saved only if this option is checked.', 'wppus' ); ?></strong>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_license_private_api_auth_key"><?php esc_html_e( 'Private API Authentication Key', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="text" id="wppus_license_private_api_auth_key" name="wppus_license_private_api_auth_key" value="<?php echo esc_attr( get_option( 'wppus_license_private_api_auth_key', 'secret' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Ideally a random string - used to authenticate administration requests (create, update and delete).', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Keep this key secret, do not share it with customers!', 'wppus' ); ?></strong>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_license_hmac_key"><?php esc_html_e( 'Signatures HMAC Key', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="text" id="wppus_license_hmac_key" name="wppus_license_hmac_key" value="<?php echo esc_attr( get_option( 'wppus_license_hmac_key', 'hmac' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Ideally a random string, used to authenticate license signatures.', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Changing this value will invalidate all the licence signatures for current remote installations.', 'wppus' ); ?></strong>
						<br>
						<?php esc_html_e( 'You may grant a grace period and let webmasters deactivate and re-activate their license(s) by unchecking "Check License signature?" below.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_license_crypto_key"><?php esc_html_e( 'Signatures Encryption Key', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="text" id="wppus_license_crypto_key" name="wppus_license_crypto_key" value="<?php echo esc_attr( get_option( 'wppus_license_crypto_key', 'crypto' ) ); ?>">
					<p class="description"><?php esc_html_e( 'Ideally a random string, used to encrypt license signatures.', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'WARNING: Changing this value will invalidate all the licence signatures for current remote installations.', 'wppus' ); ?></strong>
						<br>
						<?php esc_html_e( 'You may grant a grace period and let webmasters deactivate and re-activate their license(s) by unchecking "Check License signature?" below.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_license_check_signature"><?php esc_html_e( 'Check License signature?', 'wppus' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="wppus_license_check_signature" name="wppus_license_check_signature" value="1" <?php checked( get_option( 'wppus_license_check_signature', 1 ), 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Check signatures - can be deactivated if the HMAC Key or the Encryption Key has been recently changed and remote installations have active licenses.', 'wppus' ); ?>
						<br>
						<?php esc_html_e( 'Typically, all webmasters would have to deactivate and re-activate their license(s) to re-build their signatures, and this could take time ; it allows to grant a grace period during which license checking is less strict to avoid conflicts.', 'wppus' ); ?>
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
