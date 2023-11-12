<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<?php WP_Packages_Update_Server::get_instance()->display_settings_header(); ?>
	<form id="wppus-packages-list" action="" method="post">
		<h3><?php esc_html_e( 'Packages', 'wppus' ); ?></h3>
		<?php $packages_table->search_box( 'Search', 'wppus' ); ?>
		<?php $packages_table->display(); ?>
		<?php if ( get_option( 'wppus_use_remote_repository' ) || get_option( 'wppus_use_licenses' ) ) : ?>
		<br/>
		<p class="description">
			<?php esc_html_e( 'Notes:', 'wppus' ); ?>
			<?php if ( get_option( 'wppus_use_remote_repository' ) ) : ?>
			<br/>
				<?php esc_html_e( '- It is not necessary to prime or upload packages linked to a Remote Repository for them to appear in this list: they will be automatically added whenever a client checks for updates.', 'wppus' ); ?>
			<br/>
				<?php esc_html_e( '- If packages linked to a Remote Repository are deleted using this interface, they will be added again to the list automatically whenever a client checks for updates.', 'wppus' ); ?>
			<?php endif; ?>
			<?php if ( get_option( 'wppus_use_licenses' ) ) : ?>
			<br/>
				<?php esc_html_e( '- All packages deleted from this interface will have their license status set to "Does not Require License" when added again.', 'wppus' ); ?>
			<br/>
				<?php esc_html_e( '- Packages removed directly on the file system will keep their previously set license status when added again.', 'wppus' ); ?>
			<?php endif; ?>
		</p>
		<?php endif; ?>
	</form>
	<br>
	<hr>
	<h3><?php esc_html_e( 'Add packages', 'wppus' ); ?></h3>
	<table class="form-table wppus-add-packages">
		<?php if ( get_option( 'wppus_use_remote_repository' ) ) : ?>
		<tr>
			<th>
				<label for="wppus_prime_package_slug"><?php esc_html_e( 'Prime a package using a Remote Repository (recommended)', 'wppus' ); ?></label>
			</th>
			<td>
				<input class="regular-text" type="text" id="wppus_prime_package_slug" placeholder="<?php esc_attr_e( 'package-name' ); ?>" name="wppus_prime_package_slug" value=""> <input type="button" id="wppus_prime_package_trigger" value="<?php print esc_attr_e( 'Get remote package', 'wppus' ); ?>" class="button button-primary" disabled /><div class="spinner"></div>
				<p class="description">
					<?php
					printf(
						// translators: %s is <code>$packages_dir</code>
						esc_html__( 'Get an archive of a package from a Remote Repository an put it in the %s directory by entering the package slug.', 'wppus' ),
						'<code>' . esc_html( $packages_dir ) . '</code>',
					);
					?>
					<?php
					printf(
						// translators: %s is <code>package-name</code>
						esc_html__( 'The repository name should be  and all the files should be located at the root of the repository.', 'wppus' ),
						'<code>package-name</code>',
					);
					?>
					<br>
					<?php
					printf(
						// translators: %1$s is <code>package-name</code>, %2$s is <code>package-name.php</code>
						esc_html__( 'In the case of a plugin, the main plugin file must have the same name as the repository name - for example, the main plugin file in %1$s repository would be %2$s.', 'wppus' ),
						'<code>package-name</code>',
						'<code>package-name.php</code>',
					);
					?>
					<br>
					<?php esc_html_e( 'Using this method adds the package to the list if not present or forcefully downloads its latest version from the Remote Repository and overwrites the existing package.', 'wppus' ); ?>
					<br>
					<?php esc_html_e( 'Note: packages will be overwritten automatically regularly with their counterpart from the Remote Repository if a newer version exists.', 'wppus' ); ?>
				</p>
			</td>
		</tr>
		<?php endif; ?>
		<tr id="wppus_manual_package_upload_dropzone">
			<th>
				<label for="wppus_manual_package_upload"><?php esc_html_e( 'Upload a package', 'wppus' ); ?>
				<?php if ( get_option( 'wppus_use_remote_repository' ) ) : ?>
					<?php esc_html_e( ' (discouraged)', 'wppus' ); ?>
				<?php endif; ?>
				</label>
			</th>
			<td>
				<input class="input-file hidden" type="file" id="wppus_manual_package_upload" name="wppus_manual_package_upload" value=""><label for="wppus_manual_package_upload" class="button"><?php esc_html_e( 'Choose package archive', 'wppus' ); ?></label> <input type="text" id="wppus_manual_package_upload_filename" placeholder="package-name.zip" value="" disabled> <input type="button" value="<?php print esc_attr_e( 'Upload package', 'wppus' ); ?>" class="button button-primary manual-package-upload-trigger" id="wppus_manual_package_upload_trigger" disabled /><div class="spinner"></div>
				<p class="description">
					<?php
					printf(
						// translators: %s is <code>$packages_dir</code>
						esc_html__( 'Add a package zip archive to the %s directory. The archive needs to be a valid WordPress plugin or theme package.', 'wppus' ),
						'<code>' . esc_html( $packages_dir ) . '</code>',
					);
					?>
					<br>
					<?php
					printf(
						// translators: %1$s is <code>package-name.zip</code>, %2$s is <code>package-name.php</code>
						esc_html__( 'In the case of a plugin the main plugin file must have the same name as the zip archive - for example, the main plugin file in %1$s would be %2$s.', 'wppus' ),
						'<code>package-name.zip</code>',
						'<code>package-name.php</code>',
					);
					?>
					<br>
					<?php esc_html_e( 'Using this method adds the package to the list if not present or overwrites the existing package.', 'wppus' ); ?>
					<?php if ( get_option( 'wppus_use_remote_repository' ) ) : ?>
					<br>
						<?php esc_html_e( 'Note: a manually uploaded package that does not have its counterpart in a Remote Repository will need to be re-uploaded manually to provide updates for each new release.', 'wppus' ); ?>
					<?php endif; ?>
				</p>
			</td>
		</tr>
	</table>
	<hr>
	<h3><?php esc_html_e( 'General Settings', 'wppus' ); ?></h3>
	<form action="" method="post">
		<table class="form-table general-options">
			<tr>
				<th>
					<label for="wppus_archive_max_size"><?php esc_html_e( 'Archive max size (in MB)', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="number" id="wppus_archive_max_size" name="wppus_archive_max_size" value="<?php echo esc_attr( get_option( 'wppus_archive_max_size', $default_archive_size ) ); ?>">
					<p class="description">
						<?php esc_html_e( 'Maximum file size when uploading or downloading packages.', 'wppus' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_cache_max_size"><?php esc_html_e( 'Cache max size (in MB)', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="number" id="wppus_cache_max_size" name="wppus_cache_max_size" value="<?php echo esc_attr( get_option( 'wppus_cache_max_size', $default_cache_size ) ); ?>"> <input type="button" value="<?php print esc_attr_e( 'Force Clean', 'wppus' ); ?> (<?php print esc_attr( $cache_size ); ?>)" class="button ajax-trigger" data-action="force_clean" data-type="cache" />
					<p class="description">
						<?php
						printf(
							// translators: %s is <code>cache_dir_path</code>
							esc_html__( 'Maximum size in MB for the %s directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.', 'wppus' ),
							'<code>' . esc_html( WPPUS_Data_Manager::get_data_dir( 'cache' ) ) . '</code>'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wppus_logs_max_size"><?php esc_html_e( 'Logs max size (in MB)', 'wppus' ); ?></label>
				</th>
				<td>
					<input class="regular-text" type="number" id="wppus_logs_max_size" name="wppus_logs_max_size" value="<?php echo esc_attr( get_option( 'wppus_logs_max_size', $default_logs_size ) ); ?>"> <input type="button" value="<?php print esc_attr_e( 'Force Clean', 'wppus' ); ?> (<?php print esc_attr( $logs_size ); ?>)" class="button ajax-trigger" data-action="force_clean" data-type="logs" />
					<p class="description">
						<?php
						printf(
							// translators: %s is <code>logs_dir_path</code>
							esc_html__( 'Maximum size in MB for the %s directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.', 'wppus' ),
							'<code>' . esc_html( WPPUS_Data_Manager::get_data_dir( 'logs' ) ) . '</code>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>
		<input type="hidden" name="wppus_settings_section" value="general-options">
		<?php wp_nonce_field( 'wppus_plugin_options', 'wppus_plugin_options_handler_nonce' ); ?>
		<p class="submit">
			<input type="submit" name="wppus_options_save" value="<?php esc_attr_e( 'Save', 'wppus' ); ?>" class="button button-primary" />
		</p>
	</form>
</div>
