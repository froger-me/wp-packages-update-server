<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<h1><?php esc_html_e( 'WP Plugin Update Server', 'wppus' ); ?></h1>
	<?php if ( is_string( $updated ) && ! empty( $updated ) ) : ?>
		<div class="updated notice notice-success is-dismissible">
			<p>
				<?php echo esc_html( $updated ); ?>
			</p>
		</div>
	<?php elseif ( is_array( $updated ) && ! empty( $updated ) ) : ?>
		<div class="error notice notice-error is-dismissible">
			<ul>
				<?php foreach ( $updated as $option_name => $message ) : ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<?php if ( is_string( $action_error ) && ! empty( $action_error ) ) : ?>
		<div class="error notice notice-error is-dismissible">
			<p>
				<?php echo esc_html( $action_error ); ?>
			</p>
		</div>
	<?php endif; ?>
	<h2 class="nav-tab-wrapper">
		<a href="admin.php?page=wppus-page" class="nav-tab">
			<span class='dashicons dashicons-welcome-view-site'></span> <?php esc_html_e( 'Overview', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-remote-sources" class="nav-tab nav-tab-active">
			<span class='dashicons dashicons-networking'></span> <?php esc_html_e( 'Remote Sources', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-licenses" class="nav-tab">
			<span class='dashicons dashicons-admin-network'></span> <?php esc_html_e( 'Licenses', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-help" class="nav-tab">
			<span class='dashicons dashicons-editor-help'></span> <?php esc_html_e( 'Help', 'wppus' ); ?>
		</a>
	</h2>
	<form action="" method="post">
		<table class="form-table package-source">
			<tr>
				<th>
					<label for="wppus_use_remote_repository"><?php esc_html_e( 'Use remote repository service', 'wppus' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="wppus_use_remote_repository" name="wppus_use_remote_repository" value="1" <?php checked( get_option( 'wppus_use_remote_repository', 0 ), 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Enables this server to download plugins and themes from a remote repository before delivering updates.', 'wppus' ); ?>
						<br>
						<?php esc_html_e( 'Supports Bitbucket, Github and Gitlab.', 'wppus' ); ?>
						<br>
						<?php
						printf(
							// translators: %s is the path where zip packages need to be uploaded
							esc_html__( 'If left unchecked, zip packages need to be manually uploaded to %s.', 'wppus' ),
							'<code>' . esc_html( WPPUS_Data_Manager::get_data_dir( 'packages' ) ) . '</code>'
						);
						?>
						<br>
						<strong><?php esc_html_e( 'It affects all the packages delivered by this installation of WP Plugin Update Server if they have a corresponding repository in the remote repository service.', 'wppus' ); ?></strong>
						<br>
						<strong><?php esc_html_e( 'Settings of the "Remote Sources" section will be saved only if this option is checked.', 'wppus' ); ?></strong>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_remote_repository_url"><?php esc_html_e( 'Remote repository service URL', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="text" id="wppus_remote_repository_url" name="wppus_remote_repository_url" value="<?php echo esc_attr( get_option( 'wppus_remote_repository_url' ) ); ?>">
					<p class="description">
						<?php esc_html_e( 'The URL of the remote repository service where packages are hosted.', 'wppus' ); ?>
						<br>
						<?php
						printf(
							// translators: %1$s is <code>https://repository-service.tld/something/</code>, %2$s is <code>something</code>, %3$s is <code>https://repository-service.tld</code>
							esc_html__( 'Must follow the following pattern: %1$s where %2$s is the user in case of Github and BitBucket, a group in case of Gitlab (no support for Gitlab subgroups), and where %3$s may be a self-hosted instance of Gitlab.', 'wppus' ),
							'<code>https://repository-service.tld/something/</code>',
							'<code>something</code>',
							'<code>https://repository-service.tld</code>'
						);
						?>
						<br>
						<?php
						printf(
							// translators: %1$s is <code>https://repository-service.tld/something/package-name/</code>, %2$s is <code>something</code>
							esc_html__( 'Each package repository URL must follow the following pattern: %1$s ; the package files must be located at the root of the repository, and in the case of plugins the main plugin file must follow the pattern %2$s.', 'wppus' ),
							'<code>https://repository-service.tld/something/package-name/</code>',
							'<code>package-name.php</code>',
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_remote_repository_self_hosted"><?php esc_html_e( 'Self-hosted remote repository service', 'wppus' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="wppus_remote_repository_self_hosted" name="wppus_remote_repository_self_hosted" value="1" <?php checked( get_option( 'wppus_remote_repository_self_hosted', 0 ), 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Check this only if the remote repository service is a self-hosted instance of Gitlab.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_remote_repository_branch"><?php esc_html_e( 'Packages branch name', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="text" id="wppus_remote_repository_branch" name="wppus_remote_repository_branch" value="<?php echo esc_attr( get_option( 'wppus_remote_repository_branch', 'master' ) ); ?>">
					<p class="description">
						<?php esc_html_e( 'The branch to download when getting remote packages from the remote repository service.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_remote_repository_credentials"><?php esc_html_e( 'Remote repository service credentials', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="text" id="wppus_remote_repository_credentials" name="wppus_remote_repository_credentials" value="<?php echo esc_attr( get_option( 'wppus_remote_repository_credentials' ) ); ?>">
					<p class="description">
						<?php esc_html_e( 'Credentials for non-publicly accessible repositories.', 'wppus' ); ?>
						<br>
						<?php
						printf(
							// translators: %s is <code>token</code>
							esc_html__( 'In the case of Github and Gitlab, an access token (%s).', 'wppus' ),
							'<code>token</code>'
						);
						?>
						<br>
						<?php
						printf(
							// translators: %s is <code>consumer_key|consumer_secret</code>
							esc_html__( 'In the case of Bitbucket, the Consumer key and secret separated by a pipe (%s). ', 'wppus' ),
							'<code>consumer_key|consumer_secret</code>'
						);
						?>
						<br>
						<?php esc_html_e( 'IMPORTANT: when creating the consumer, "This is a private consumer" must be checked.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_package_private_api_auth_key"><?php esc_html_e( 'Private API Authentication Key', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="text" id="wppus_package_private_api_auth_key" name="wppus_package_private_api_auth_key" value="<?php echo esc_attr( get_option( 'wppus_package_private_api_auth_key', 'private_api_auth_key' ) ); ?>">
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
			<?php do_action( 'wppus_template_remote_source_manager_option_before_recurring_check' ); ?>
			<tr class="check-frequency <?php echo ( $hide_check_frequency ) ? 'hidden' : ''; ?>">
				<th>
					<label for="wppus_remote_repository_check_frequency"><?php esc_html_e( 'Remote update check frequency', 'wppus' ); ?></label>
				</th>
				<td>
					<select name="wppus_remote_repository_check_frequency" id="wppus_remote_repository_check_frequency">
						<?php foreach ( $schedules as $display => $schedule ) : ?>
							<option value="<?php echo esc_attr( $schedule['slug'] ); ?>" <?php selected( get_option( 'wppus_remote_repository_check_frequency', 'daily' ), $schedule['slug'] ); ?>><?php echo esc_html( $display ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'How often WP Plugin Update Server will poll each remote repository for package updates - checking too often may slow down the server (recommended "Once Daily").', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<?php do_action( 'wppus_template_remote_source_manager_option_after_recurring_check' ); ?>
		</table>
		<input type="hidden" name="wppus_settings_section" value="package-source">
		<?php wp_nonce_field( 'wppus_plugin_options', 'wppus_plugin_options_handler_nonce' ); ?>
		<p class="submit">
			<input type="submit" name="wppus_options_save" value="<?php esc_attr_e( 'Save', 'wppus' ); ?>" class="button button-primary" />
		</p>
		<?php if ( get_option( 'wppus_use_remote_repository' ) ) : ?>
		<hr>
		<table class="form-table package-source check-frequency <?php echo ( $hide_check_frequency ) ? 'hidden' : ''; ?>">
			<tr>
				<th>
					<label for="wppus_remote_repository_force_remove_schedules"><?php esc_html_e( 'Clear scheduled remote updates', 'wppus' ); ?></label>
				</th>
				<td>
					<input type="button" value="<?php print esc_attr_e( 'Force Clear Schedules', 'wppus' ); ?>" id="wppus_remote_repository_force_remove_schedules" class="button ajax-trigger" data-action="force_clean" data-type="schedules" />
					<p class="description">
						<?php esc_html_e( 'Clears all scheduled remote updates coming from the repository service.', 'wppus' ); ?>
						<br/>
						<?php esc_html_e( 'WARNING: after cleaning the schedules, packages will not be automatically updated from their remote repository.', 'wppus' ); ?>
						<br/>
						<?php esc_html_e( 'Use the "Prime a package using a remote repository " feature in the "Overview" tab to selectively reschedule remote updates, or use the "Reschedule remote updates" option below to reschedule all the existing packages.', 'wppus' ); ?>
						<br/>
						<?php esc_html_e( 'Useful for maintenance and tests purposes - for example, if the packages have to be manually altered directly on the file system.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_remote_repository_force_register_schedules"><?php esc_html_e( 'Reschedule remote updates', 'wppus' ); ?></label>
				</th>
				<td>
					<input type="button" value="<?php print esc_attr_e( 'Force Register Schedules', 'wppus' ); ?>" id="wppus_remote_repository_force_register_schedules" class="button ajax-trigger" data-action="force_register" data-type="schedules" />
					<p class="description">
						<?php esc_html_e( 'Reschedules remote updates from the repository service for all the packages.', 'wppus' ); ?>
						<br/>
						<?php esc_html_e( 'WARNING: after rescheduling remote updates, a wp-cron event will be scheduled for all the packages, including those uploaded manually and without a corresponding remote repository.', 'wppus' ); ?>
						<br/>
						<?php esc_html_e( 'Make sure either all packages have a corresponding remote repository in the repository service, or to use the delete operation and re-upload the packages that were previously manually uploaded to clear the useless scheduled wp-cron events.', 'wppus' ); ?>
						<br/>
						<?php esc_html_e( 'If there were useless scheduled wp-cron events left, they would not trigger any error, but the server would be querying the repository service needlessly.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php endif; ?>
	</form>
</div>
