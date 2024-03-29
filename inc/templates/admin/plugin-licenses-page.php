<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<?php WP_Packages_Update_Server::get_instance()->display_settings_header( $result ); ?>
	<?php if ( get_option( 'wppus_use_licenses' ) ) : ?>
	<form autocomplete="off" id="wppus-licenses-list" action="" method="post">
		<h3><?php esc_html_e( 'Licenses', 'wppus' ); ?></h3>
		<?php $licenses_table->search_box( 'Search', 'wppus' ); ?>
		<?php $licenses_table->display(); ?>
	</form>
	<div id="wppus_license_panel" class="postbox">
		<div class="inside">
			<form autocomplete="off" id="wppus_license" class="panel" action="" method="post">
				<h3><span class='wppus-add-license-label'><?php esc_html_e( 'Add License', 'wppus' ); ?></span><span class='wppus-edit-license-label'><?php esc_html_e( 'Edit License', 'wppus' ); ?> (ID <span id="wppus_license_id"></span>)</span><span class="small"> (<a class="close-panel reset" href="#"><?php esc_html_e( 'cancel', 'wppus' ); ?></a>)</span></h3>
				<div class="license-column-container">
					<div class="license-column">
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'License Key', 'wppus' ); ?> <span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></span></th>
								<td>
									<input type="text" id="wppus_license_key" data-random_key="<?php echo esc_html( bin2hex( openssl_random_pseudo_bytes( 16 ) ) ); ?>" name="wppus_license_key" class="no-submit" value="" size="30">
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
										<option value="generic"><?php esc_html_e( 'Generic' ); ?></option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Package Slug', 'wppus' ); ?> <span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_package_slug" name="wppus_license_package_slug" class="no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The plugin, theme, or generic package slug. Only alphanumeric characters and dashes are allowed.', 'wppus' ); ?>
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
										<option value="on-hold"><?php esc_html_e( 'On Hold', 'wppus' ); ?></option>
										<option value="blocked"><?php esc_html_e( 'Blocked', 'wppus' ); ?></option>
										<option value="expired"><?php esc_html_e( 'Expired', 'wppus' ); ?></option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Date Created', 'wppus' ); ?> <span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="date" id="wppus_license_date_created" name="wppus_license_date_created" class="wppus-license-date no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'Creation date of the license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top" class="wppus-license-show-if-edit">
								<th scope="row"><?php esc_html_e( 'Date Renewed', 'wppus' ); ?></th>
								<td>
									<input type="date" id="wppus_license_date_renewed" name="wppus_license_date_renewed" class="wppus-license-date no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'Date of the last time the license was renewed.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Expiry Date', 'wppus' ); ?></th>
								<td>
									<input type="date" id="wppus_license_date_expiry" name="wppus_license_date_expiry" class="wppus-license-date no-submit" value="" size="30">
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
								<th scope="row"><?php esc_html_e( 'Registered Email', 'wppus' ); ?> <span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="email" id="wppus_license_registered_email" name="wppus_license_registered_email" class="no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The email registered with this license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Owner Name', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_owner_name" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The full name of the owner of the license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Company', 'wppus' ); ?></th>
								<td>
									<input type="text" id="wppus_license_owner_company" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The company of the owner of this license.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php esc_html_e( 'Max. Allowed Domains', 'wppus' ); ?> <span class="description"><?php esc_html_e( '(required)', 'wppus' ); ?></th>
								<td>
									<input type="number" min="1" id="wppus_license_max_allowed_domains" name="wppus_license_max_allowed_domains" class="no-submit" value="" size="30">
									<p class="description">
										<?php esc_html_e( 'The maximum number of domains on which this license can be used.', 'wppus' ); ?>
									</p>
								</td>
							</tr>
							<tr valign="top" class="wppus-license-show-if-edit">
								<th scope="row"><?php esc_html_e( 'Registered Domains', 'wppus' ); ?></th>
								<td>
									<div id="wppus_license_registered_domains">
										<ul class="wppus-domains-list">
											<li class='wppus-domain-template'>
												<button type="button" class="wppus-remove-domain">
												<span class="wppus-remove-icon" aria-hidden="true"></span>
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
								<th scope="row"><?php esc_html_e( 'Transaction ID', 'wppus' ); ?></th>
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
				<div class="license-form-extra-data clear">
					<h4><?php esc_html_e( 'Extra Data', 'wppus' ); ?></h4>
					<p class="description">
						<?php esc_html_e( 'Advanced - JSON-formatted custom data to add to the license.', 'wppus' ); ?><br>
						<?php esc_html_e( 'Typically used by plugins & API integrations ; proceed with caution when editing.', 'wppus' ); ?><br>
					</p>
					<textarea id="wppus_license_data"></textarea>
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
	<form autocomplete="off" id="wppus-licenses-settings" action="" method="post">
		<table class="form-table">
			<tr>
				<th>
					<label for="wppus_use_licenses"><?php esc_html_e( 'Enable Package Licenses', 'wppus' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="wppus_use_licenses" name="wppus_use_licenses" value="1" <?php checked( get_option( 'wppus_use_licenses', 0 ), 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Check to activate license-enabled plugin, theme, and generic packages delivery.', 'wppus' ); ?>
						<br>
						<strong><?php esc_html_e( 'It affects all the packages with a "Requires License" license status delivered by this installation of WP Packages Update Server.', 'wppus' ); ?></strong>
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
