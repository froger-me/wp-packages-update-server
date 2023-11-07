<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<tr>
    <th>
        <label for="wppus_remote_repository_use_webhooks"><?php esc_html_e( 'Use Webhooks', 'wppus' ); ?></label>
    </th>
    <td>
        <input type="checkbox" id="wppus_remote_repository_use_webhooks" name="wppus_remote_repository_use_webhooks" value="1" <?php checked( $use_webhooks, 1 ); ?>>
        <p class="description">
            <?php esc_html_e( 'Check this if you wish for each repository of the remote repository service to call a Webhook when updates are pushed.', 'wppus' ); ?><br>
            <?php esc_html_e( 'When checked, WP Plugin Update Server will not regularly poll repositories for package version changes, but relies on events sent by the repositories to schedule a package download.', 'wppus' ); ?>
            <br/>
            <?php
            printf(
                // translators: %1$s is the webhook URL, %2$s is <code>package-type</code>, %3$s is <code>plugin</code>, %4$s is <code>theme</code>, %5$s is <code>package-name</code>
                esc_html__( 'Webhook URL: %1$s - where %2$s is the package type ( %3$s or %4$s ) and %5$s is the package needing updates.', 'wppus' ),
                '<code>' . esc_url( home_url( '/wppus-webhook/package-type/package-name' ) ) . '</code>',
                '<code>package-type</code>',
                '<code>plugin</code>',
                '<code>theme</code>',
                '<code>package-name</code>'
            );
            ?>
            <br>
            <?php esc_html_e( 'Note that WP Plugin Update Server does not rely on the content of the payload to schedule a package download, so any type of event can be used to trigger the Webhook.', 'wppus' ); ?>
        </p>
    </td>
</tr>
<tr class="webhooks <?php echo ( $use_webhooks ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_remote_repository_check_delay"><?php esc_html_e( 'Remote download delay', 'wppus' ); ?></label>
    </th>
    <td>
        <input type="number" min="0" id="wppus_remote_repository_check_delay" name="wppus_remote_repository_check_delay" value="<?php echo esc_attr( get_option( 'wppus_remote_repository_check_delay', 0 ) ); ?>">
        <p class="description">
            <?php esc_html_e( 'Delay in minutes after which WP Plugin Update Server will poll the remote repository for package updates when the Webhook has been called.', 'wppus' ); ?><br>
            <?php esc_html_e( 'Leave at 0 to schedule a package update during the cron run happening immediately after the Webhook was called.', 'wppus' ); ?>
        </p>
    </td>
</tr>
<tr class="webhooks <?php echo ( $use_webhooks ) ? '' : 'hidden'; ?>">
    <th>
        <label for="wppus_remote_repository_webhook_secret"><?php esc_html_e( 'Remote repository Webhook Secret', 'wppus' ); ?></label>
    </th>
    <td>
        <input class="regular-text" type="text" id="wppus_remote_repository_webhook_secret" name="wppus_remote_repository_webhook_secret" value="<?php echo esc_attr( get_option( 'wppus_remote_repository_webhook_secret', 'repository_webhook_secret' ) ); ?>">
        <p class="description">
            <?php esc_html_e( 'Ideally a random string, the secret string included in the request by the repository service when calling the Webhook.', 'wppus' ); ?>
            <br>
            <strong><?php esc_html_e( 'WARNING: Changing this value will invalidate all the existing Webhooks set up on all package repositories.', 'wppus' ); ?></strong>
            <br>
            <?php esc_html_e( 'After changing this setting, make sure to update the Webhooks secrets in the repository service.', 'wppus' ); ?></strong>
        </p>
    </td>
</tr>