<?php
/**
 * Handles all plugin settings.
 *
 * @link       https://yourwebsite.com/
 * @since      1.0.0
 *
 * @package    WooCommerce_Shipping_Label_Printer
 * @subpackage WooCommerce_Shipping_Label_Printer/includes
 */

/**
 * Handles all plugin settings.
 *
 * @package    WooCommerce_Shipping_Label_Printer
 * @subpackage WooCommerce_Shipping_Label_Printer/includes
 * @author     Your Name <your-email@example.com>
 */
class WSPLP_Settings {

    /**
     * Holds the plugin options.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $options    The plugin options.
     */
    private $options;

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->options = get_option( 'wsplp_options' );
        if ( false === $this->options ) {
            $this->options = $this->get_default_options();
            add_option( 'wsplp_options', $this->options );
        }
    }

    /**
     * Get default plugin options.
     *
     * @since    1.0.0
     * @return   array    Default options.
     */
    public function get_default_options() {
        return array(
            'rows_per_page'  => 3,
            'cols_per_page'  => 2,
            'label_width'    => 99,  // Example for 2x3 A4 layout (approx)
            'label_height'   => 99,  // Example for 2x3 A4 layout (approx)
            'margin_top'     => 10,
            'margin_bottom'  => 10,
            'margin_left'    => 10,
            'margin_right'   => 10,
            'return_address' => get_bloginfo( 'name' ) . "\n" . get_option( 'woocommerce_store_address' ) . "\n" . get_option( 'woocommerce_store_city' ) . ", " . get_option( 'woocommerce_store_postcode' ),
            'output_format'  => 'pdf', // 'pdf' or 'html'
        );
    }

    /**
     * Get current plugin options.
     *
     * @since    1.0.0
     * @return   array    Current options.
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Sanitize and validate options.
     *
     * @since    1.0.0
     * @param    array    $input    The raw input from the settings form.
     * @return   array    The sanitized and validated options.
     */
    public function sanitize_options( $input ) {
        $sanitized_input = array();

        $sanitized_input['rows_per_page'] = absint( $input['rows_per_page'] );
        if ( $sanitized_input['rows_per_page'] < 1 ) $sanitized_input['rows_per_page'] = 1;

        $sanitized_input['cols_per_page'] = absint( $input['cols_per_page'] );
        if ( $sanitized_input['cols_per_page'] < 1 ) $sanitized_input['cols_per_page'] = 1;

        $sanitized_input['label_width'] = absint( $input['label_width'] );
        if ( $sanitized_input['label_width'] < 1 ) $sanitized_input['label_width'] = 1;

        $sanitized_input['label_height'] = absint( $input['label_height'] );
        if ( $sanitized_input['label_height'] < 1 ) $sanitized_input['label_height'] = 1;

        $sanitized_input['margin_top'] = absint( $input['margin_top'] );
        $sanitized_input['margin_bottom'] = absint( $input['margin_bottom'] );
        $sanitized_input['margin_left'] = absint( $input['margin_left'] );
        $sanitized_input['margin_right'] = absint( $input['margin_right'] );

        $sanitized_input['return_address'] = sanitize_textarea_field( $input['return_address'] );

        $sanitized_input['output_format'] = in_array( $input['output_format'], array( 'pdf', 'html' ), true ) ? sanitize_text_field( $input['output_format'] ) : 'pdf';

        // Merge with current options to ensure all are present, just in case a new option is added later.
        $current_options = $this->get_options();
        return array_merge( $current_options, $sanitized_input );
    }

    /**
     * Render the Labels Per Page (Rows) field.
     *
     * @since    1.0.0
     */
    public function render_rows_per_page_field() {
        ?>
        <input type="number" name="wsplp_options[rows_per_page]" value="<?php echo esc_attr( $this->options['rows_per_page'] ); ?>" min="1" />
        <p class="description"><?php esc_html_e( 'Number of label rows per A4 page.', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Labels Per Page (Columns) field.
     *
     * @since    1.0.0
     */
    public function render_cols_per_page_field() {
        ?>
        <input type="number" name="wsplp_options[cols_per_page]" value="<?php echo esc_attr( $this->options['cols_per_page'] ); ?>" min="1" />
        <p class="description"><?php esc_html_e( 'Number of label columns per A4 page.', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Label Width field.
     *
     * @since    1.0.0
     */
    public function render_label_width_field() {
        ?>
        <input type="number" name="wsplp_options[label_width]" value="<?php echo esc_attr( $this->options['label_width'] ); ?>" min="1" />
        <p class="description"><?php esc_html_e( 'Width of each label in millimeters (mm).', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Label Height field.
     *
     * @since    1.0.0
     */
    public function render_label_height_field() {
        ?>
        <input type="number" name="wsplp_options[label_height]" value="<?php echo esc_attr( $this->options['label_height'] ); ?>" min="1" />
        <p class="description"><?php esc_html_e( 'Height of each label in millimeters (mm).', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Top Margin field.
     *
     * @since    1.0.0
     */
    public function render_margin_top_field() {
        ?>
        <input type="number" name="wsplp_options[margin_top]" value="<?php echo esc_attr( $this->options['margin_top'] ); ?>" min="0" />
        <p class="description"><?php esc_html_e( 'Top margin of the A4 page in millimeters (mm).', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Bottom Margin field.
     *
     * @since    1.0.0
     */
    public function render_margin_bottom_field() {
        ?>
        <input type="number" name="wsplp_options[margin_bottom]" value="<?php echo esc_attr( $this->options['margin_bottom'] ); ?>" min="0" />
        <p class="description"><?php esc_html_e( 'Bottom margin of the A4 page in millimeters (mm).', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Left Margin field.
     *
     * @since    1.0.0
     */
    public function render_margin_left_field() {
        ?>
        <input type="number" name="wsplp_options[margin_left]" value="<?php echo esc_attr( $this->options['margin_left'] ); ?>" min="0" />
        <p class="description"><?php esc_html_e( 'Left margin of the A4 page in millimeters (mm).', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Right Margin field.
     *
     * @since    1.0.0
     */
    public function render_margin_right_field() {
        ?>
        <input type="number" name="wsplp_options[margin_right]" value="<?php echo esc_attr( $this->options['margin_right'] ); ?>" min="0" />
        <p class="description"><?php esc_html_e( 'Right margin of the A4 page in millimeters (mm).', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Return Address field.
     *
     * @since    1.0.0
     */
    public function render_return_address_field() {
        ?>
        <textarea name="wsplp_options[return_address]" rows="5" cols="50"><?php echo esc_textarea( $this->options['return_address'] ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Enter the return address for your labels. New lines will be respected.', 'wsplp' ); ?></p>
        <?php
    }

    /**
     * Render the Output Format field.
     *
     * @since    1.0.0
     */
    public function render_output_format_field() {
        ?>
        <select name="wsplp_options[output_format]">
            <option value="pdf" <?php selected( $this->options['output_format'], 'pdf' ); ?>><?php esc_html_e( 'PDF', 'wsplp' ); ?></option>
            <option value="html" <?php selected( $this->options['output_format'], 'html' ); ?>><?php esc_html_e( 'HTML (for direct printing)', 'wsplp' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Choose the output format for your labels.', 'wsplp' ); ?></p>
        <?php
    }
}
