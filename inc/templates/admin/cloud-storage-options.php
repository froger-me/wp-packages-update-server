<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<tr>
    <th>
        <label for="wppus_use_cloud_storage"><?php esc_html_e( 'Use Cloud Storage', 'wppus' ); ?></label>
    </th>
    <td>
        <input type="checkbox" id="wppus_use_cloud_storage" name="wppus_use_cloud_storage" value="1" <?php checked( $use_cloud_storage, 1 ); ?>>
        <p class="description">
            <?php esc_html_e( 'Check this if you wish to use a Cloud Storage service - S3 Compatible.', 'wppus' ); ?><br>
            <?php
            printf(
                // translators: %s is the packages folder
                esc_html__( 'If it does not exist, a virtual folders %s will be created in the Storage Unit chosen for package storage.', 'wppus' ),
                '<code>wppus-packages</code>',
            );
            ?>
        </p>
    </td>
</tr>
<tr class="hide-if-no-cloud-storage <?php echo ( $use_cloud_storage ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_cloud_storage_access_key"><?php esc_html_e( 'Cloud Storage Access Key', 'wppus' ); ?></label>
    </th>
    <td>
        <input class="regular-text cloud-storage-setting" type="text" id="wppus_cloud_storage_access_key" name="wppus_cloud_storage_access_key" value="<?php echo esc_attr( get_option( 'wppus_cloud_storage_access_key' ) ); ?>">
        <p class="description">
            <?php esc_html_e( 'The Access Key provided by the Cloud Storage service.', 'wppus' ); ?>
        </p>
    </td>
</tr>
<tr class="hide-if-no-cloud-storage <?php echo ( $use_cloud_storage ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_cloud_storage_secret_key"><?php esc_html_e( 'Cloud Storage Secret Key', 'wppus' ); ?></label>
    </th>
    <td>
        <input class="regular-text cloud-storage-setting" type="text" id="wppus_cloud_storage_secret_key" name="wppus_cloud_storage_secret_key" value="<?php echo esc_attr( get_option( 'wppus_cloud_storage_secret_key' ) ); ?>">
        <p class="description">
            <?php esc_html_e( 'The Secret Key provided by the Cloud Storage service.', 'wppus' ); ?>
        </p>
    </td>
</tr>
<tr class="hide-if-no-cloud-storage <?php echo ( $use_cloud_storage ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_cloud_storage_endpoint"><?php esc_html_e( 'Cloud Storage Endpoint', 'wppus' ); ?></label>
    </th>
    <td>
        <input class="regular-text cloud-storage-setting" type="text" id="wppus_cloud_storage_endpoint" name="wppus_cloud_storage_endpoint" value="<?php echo esc_attr( get_option( 'wppus_cloud_storage_endpoint' ) ); ?>">
        <p class="description">
            <?php
            printf(
                // translators: %1$s is <code>http://</code>, %2$s is <code>https://</code>
                esc_html__( 'The domain (without %1$s or %2$s) of the endpoint for the Cloud Storage service.', 'wppus' ),
                '<code>http://</code>',
                '<code>https://</code>',
            );
            ?>
        </p>
    </td>
</tr>
<tr class="hide-if-no-cloud-storage <?php echo ( $use_cloud_storage ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_cloud_storage_unit"><?php esc_html_e( 'Cloud Storage Unit', 'wppus' ); ?></label>
    </th>
    <td>
        <input class="regular-text cloud-storage-setting" type="text" id="wppus_cloud_storage_unit" name="wppus_cloud_storage_unit" value="<?php echo esc_attr( get_option( 'wppus_cloud_storage_unit' ) ); ?>">
        <p class="description">
            <?php esc_html_e( 'Usually known as a "bucket" or a "container" depending on the Cloud Storage service provider.', 'wppus' ); ?>
        </p>
    </td>
</tr>
<tr class="hide-if-no-cloud-storage <?php echo ( $use_cloud_storage ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_cloud_storage_region"><?php esc_html_e( 'Cloud Storage Region', 'wppus' ); ?></label>
    </th>
    <td>
        <input class="regular-text cloud-storage-setting" type="text" id="wppus_cloud_storage_region" name="wppus_cloud_storage_region" value="<?php echo esc_attr( get_option( 'wppus_cloud_storage_region' ) ); ?>">
        <p class="description">
            <?php esc_html_e( 'The region of the Cloud Storage Unit, as indicated by the Cloud Storage service provider.', 'wppus' ); ?>
        </p>
    </td>
</tr>
<tr class="hide-if-no-cloud-storage <?php echo ( $use_cloud_storage ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_cloud_storage_test"><?php esc_html_e( 'Test Cloud Storage Settings', 'wppus' ); ?></label>
    </th>
    <td>
        <input type="button" value="<?php print esc_attr_e( 'Test Now', 'wppus' ); ?>" id="wppus_cloud_storage_test" class="button ajax-trigger" data-selector=".cloud-storage-setting" data-action="cloud_storage_test" data-type="none" />
        <p class="description">
            <?php esc_html_e( 'Send a test request to the Cloud Storage service provider.', 'wppus' ); ?>
            <br/>
            <?php esc_html_e( 'The request checks whether the provider is reachable and if the Storage Unit exists and is writable.', 'wppus' ); ?><br>
            <?php
            printf(
                // translators: %s is the packages folder
                esc_html__( 'If it does not exist, a virtual folders %s will be created in the Storage Unit chosen for package storage.', 'wppus' ),
                '<code>wppus-packages</code>',
            );
            ?>
        </p>
    </td>
</tr>
