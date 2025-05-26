<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://yourwebsite.com/
 * @since      1.0.0
 *
 * @package    WooCommerce_Shipping_Label_Printer
 * @subpackage WooCommerce_Shipping_Label_Printer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooCommerce_Shipping_Label_Printer
 * @subpackage WooCommerce_Shipping_Label_Printer/admin
 * @author     Your Name <your-email@example.com>
 */
class WSPLP_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * Stores the plugin settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      WSPLP_Settings    $settings    The settings instance.
     */
    private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
		$this->plugin_name = 'woocommerce-shipping-label-printer';
		$this->version = WSPLP_VERSION;
        $this->settings = new WSPLP_Settings();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, WSPLP_PLUGIN_URL . 'assets/css/admin-styles.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, WSPLP_PLUGIN_URL . 'assets/js/admin-scripts.js', array( 'jquery' ), $this->version, false );
        wp_localize_script(
            $this->plugin_name,
            'wsplp_admin_vars',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wsplp_print_labels_nonce' ),
            )
        );
	}

    /**
     * Add the plugin's admin menu page.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Shipping Label Printer', 'wsplp' ),
            __( 'Shipping Labels', 'wsplp' ),
            'manage_woocommerce', // Capability required to access this page
            $this->plugin_name,
            array( $this, 'display_settings_page' )
        );
    }

    /**
     * Display the plugin settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        $options = $this->settings->get_options();
        include_once WSPLP_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Register settings for the plugin.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'wsplp_options_group', // Option group
            'wsplp_options',       // Option name
            array( $this->settings, 'sanitize_options' ) // Sanitize callback
        );

        add_settings_section(
            'wsplp_main_section', // ID
            __( 'Label Printing Settings', 'wsplp' ), // Title
            null, // Callback for description
            'wsplp_settings' // Page
        );

        add_settings_field(
            'rows_per_page',
            __( 'Labels Per Page (Rows)', 'wsplp' ),
            array( $this->settings, 'render_rows_per_page_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'cols_per_page',
            __( 'Labels Per Page (Columns)', 'wsplp' ),
            array( $this->settings, 'render_cols_per_page_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'label_width',
            __( 'Label Width (mm)', 'wsplp' ),
            array( $this->settings, 'render_label_width_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'label_height',
            __( 'Label Height (mm)', 'wsplp' ),
            array( $this->settings, 'render_label_height_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'margin_top',
            __( 'Top Margin (mm)', 'wsplp' ),
            array( $this->settings, 'render_margin_top_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'margin_bottom',
            __( 'Bottom Margin (mm)', 'wsplp' ),
            array( $this->settings, 'render_margin_bottom_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'margin_left',
            __( 'Left Margin (mm)', 'wsplp' ),
            array( $this->settings, 'render_margin_left_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'margin_right',
            __( 'Right Margin (mm)', 'wsplp' ),
            array( $this->settings, 'render_margin_right_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'return_address',
            __( 'Return Address', 'wsplp' ),
            array( $this->settings, 'render_return_address_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );

        add_settings_field(
            'output_format',
            __( 'Output Format', 'wsplp' ),
            array( $this->settings, 'render_output_format_field' ),
            'wsplp_settings',
            'wsplp_main_section'
        );
    }

    /**
     * Add bulk action to WooCommerce orders list.
     *
     * @since 1.0.0
     * @param array $actions
     * @return array
     */
    public function add_bulk_actions_to_orders( $actions ) {
        $actions['wsplp_print_shipping_labels'] = __( 'Print Shipping Labels', 'wsplp' );
        return $actions;
    }

    /**
     * Handle the bulk action for printing shipping labels.
     *
     * @since 1.0.0
     */
    public function handle_bulk_action_print_shipping_labels() {
        if ( ! isset( $_GET['post_type'] ) || 'shop_order' !== $_GET['post_type'] ) {
            return;
        }

        $wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
        $action = $wp_list_table->current_action();

        if ( 'wsplp_print_shipping_labels' === $action && isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ) {
            check_admin_referer( 'bulk-posts' ); // Security check

            $order_ids = array_map( 'absint', $_GET['post'] );

            // Redirect to a page that handles the printing logic.
            // We'll use a hidden admin page or an AJAX endpoint.
            $redirect_url = add_query_arg(
                array(
                    'page'      => $this->plugin_name . '_print_labels',
                    'order_ids' => implode( ',', $order_ids ),
                    'nonce'     => wp_create_nonce( 'wsplp_print_labels_bulk_action' ),
                ),
                admin_url( 'admin.php' )
            );
            wp_redirect( esc_url_raw( $redirect_url ) );
            exit;
        }
    }

    /**
     * Add a hidden admin page for printing labels.
     * This page will generate the PDF directly.
     *
     * @since 1.0.0
     */
    public function add_print_labels_page() {
        add_submenu_page(
            null, // Hide from menu
            __( 'Print Shipping Labels', 'wsplp' ),
            __( 'Print Shipping Labels', 'wsplp' ),
            'manage_woocommerce',
            $this->plugin_name . '_print_labels',
            array( $this, 'display_print_labels_page' )
        );
    }

    /**
     * Display the print labels page and generate the PDF/HTML.
     *
     * @since 1.0.0
     */
    public function display_print_labels_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'wsplp' ) );
        }

        if ( ! isset( $_GET['order_ids'] ) || empty( $_GET['order_ids'] ) ) {
            wp_die( __( 'No order IDs provided for printing.', 'wsplp' ) );
        }

        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'wsplp_print_labels_bulk_action' ) ) {
            wp_die( __( 'Security check failed.', 'wsplp' ) );
        }

        $order_ids_str = sanitize_text_field( wp_unslash( $_GET['order_ids'] ) );
        $order_ids = array_map( 'absint', explode( ',', $order_ids_str ) );

        $label_generator = new WSPLP_Label_Generator();
        $label_generator->generate_labels( $order_ids );
    }

    /**
     * Initialize all the hooks.
     *
     * @since    1.0.0
     */
    public function run() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions_to_orders' ) );
        add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_action_print_shipping_labels' ), 10, 3 );
        add_action( 'admin_menu', array( $this, 'add_print_labels_page' ) ); // For the hidden print page
    }
}
