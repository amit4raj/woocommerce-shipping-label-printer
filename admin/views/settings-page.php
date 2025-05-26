<?php
/**
 * Provides the user interface for the plugin settings.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://yourwebsite.com/
 * @since      1.0.0
 *
 * @package    WooCommerce_Shipping_Label_Printer
 * @subpackage WooCommerce_Shipping_Label_Printer/admin/views
 */
?>

<div class="wrap">
    <h1><?php esc_html_e( 'WooCommerce Shipping Label Printer Settings', 'wsplp' ); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'wsplp_options_group' ); // Settings group name
        do_settings_sections( 'wsplp_settings' );  // Page slug
        submit_button();
        ?>
    </form>
</div>
