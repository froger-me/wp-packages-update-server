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
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.' ); ?></span>
			</button>
		</div>
	<?php elseif ( is_array( $updated ) && ! empty( $updated ) ) : ?>
		<div class="error notice notice-error is-dismissible">
			<ul>
				<?php foreach ( $updated as $option_name => $message ) : ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.' ); ?></span>
			</button>
		</div>
	<?php endif; ?>
	<?php if ( is_string( $action_error ) && ! empty( $action_error ) ) : ?>
		<div class="error notice notice-error is-dismissible">
			<p>
				<?php echo esc_html( $action_error ); ?>
			</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.' ); ?></span>
			</button>
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
					<input class="regular-text" type="checkbox" id="wppus_use_remote_repository" name="wppus_use_remote_repository" value="1" <?php checked( get_option( 'wppus_use_remote_repository', 0 ), 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Enables this server to download plugins and themes from a remote repository before delivering updates.', 'wppus' ); ?>
						<br>
						<?php esc_html_e( 'Supports Bitbucket, Github and Gitlab.', 'wppus' ); ?>
						<br>
						<?php echo sprintf( __( 'If left unchecked, zip packages need to be manually uploaded to <code>%s</code>.', 'wppus' ), WPPUS_Data_Manager::get_data_dir( 'packages' ) ); ?><?php // @codingStandardsIgnoreLine ?>
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
						<?php _e( 'Must follow the following pattern: <code>https://repository-service.tld/something/</code> where <code>something</code> is the user in case of Github and BitBucket, a group in case of Gitlab (no support for Gitlab subgroups), and where <code>https://repository-service.tld</code> may be a self-hosted instance of Gitlab.', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
						<br>
						<?php _e( 'Each package repository URL must follow the following pattern: <code>https://repository-service.tld/something/package-name/</code> ; the package files must be located at the root of the repository, and in the case of plugins the main plugin file must follow the pattern <code>package-name.php</code>.', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_remote_repository_self_hosted"><?php esc_html_e( 'Self-hosted remote repository service', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="checkbox" id="wppus_remote_repository_self_hosted" name="wppus_remote_repository_self_hosted" value="1" <?php checked( get_option( 'wppus_remote_repository_self_hosted', 0 ), 1 ); ?>>
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
						<?php _e( 'In the case of Github and Gitlab, an access token (<code>token</code>).', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
						<br>
						<?php _e( 'In the case of Bitbucket, the Consumer key and secret separated by a pipe (<code>consumer_key|consumer_secret</code>). IMPORTANT: when creating the consumer, "This is a private consumer" must be checked.', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
						<br>
					</p>
				</td>
			</tr>
			<tr>
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
		</table>
		<input type="hidden" name="wppus_settings_section" value="package-source">
		<?php wp_nonce_field( 'wppus_plugin_options', 'wppus_plugin_options_handler_nonce' ); ?>
		<p class="submit">
			<input type="submit" name="wppus_options_save" value="<?php esc_attr_e( 'Save', 'wppus' ); ?>" class="button button-primary" />
		</p>
		<?php if ( get_option( 'wppus_use_remote_repository', false ) ) : ?>
		<hr>
		<table class="form-table package-source">
			<tr>
				<th>
					<label for="wppus_remote_repository_force_remove_schedules"><?php esc_html_e( 'Clear scheduled remote updates', 'wppus' ); ?></label>
				</th>
				<td>
					<input type="button" value="<?php print esc_attr_e( 'Force Clear Schedules', 'wppus' ); ?>" id="wppus_remote_repository_force_remove_schedules" class="button ajax-trigger" data-action="clean" data-type="schedules" />
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
					<input type="button" value="<?php print esc_attr_e( 'Force Register Schedules', 'wppus' ); ?>" id="wppus_remote_repository_force_register_schedules" class="button ajax-trigger" data-action="register" data-type="schedules" />
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
