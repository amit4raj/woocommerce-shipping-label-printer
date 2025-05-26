<?php
/**
 * Handles the generation of shipping labels.
 *
 * @link       https://yourwebsite.com/
 * @since      1.0.0
 *
 * @package    WooCommerce_Shipping_Label_Printer
 * @subpackage WooCommerce_Shipping_Label_Printer/includes
 */

// Require TCPDF if output format is PDF.
// IMPORTANT: You need to download TCPDF and place it in the 'lib/tcpdf/' directory.
// Download from: https://tcpdf.org/downloads/
if ( class_exists( 'WSPLP_Settings' ) && ( new WSPLP_Settings() )->get_options()['output_format'] === 'pdf' ) {
    if ( ! class_exists( 'TCPDF' ) ) {
        require_once WSPLP_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';
    }
}

/**
 * Handles the generation of shipping labels.
 *
 * @package    WooCommerce_Shipping_Label_Printer
 * @subpackage WooCommerce_Shipping_Label_Printer/includes
 * @author     Your Name <your-email@example.com>
 */
class WSPLP_Label_Generator {

    /**
     * Plugin settings.
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
        $settings = new WSPLP_Settings();
        $this->options = $settings->get_options();
    }

    /**
     * Generates shipping labels for given order IDs.
     *
     * @since    1.0.0
     * @param    array    $order_ids    Array of WooCommerce order IDs.
     */
    public function generate_labels( $order_ids ) {
        if ( empty( $order_ids ) ) {
            return;
        }

        $orders_data = array();
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }

            $shipping_address = $order->get_formatted_shipping_address();
            if ( empty( $shipping_address ) ) {
                $shipping_address = $order->get_billing_address_1() . "\n" .
                                    $order->get_billing_address_2() . "\n" .
                                    $order->get_billing_city() . ", " .
                                    $order->get_billing_state() . " " .
                                    $order->get_billing_postcode() . "\n" .
                                    $order->get_billing_country();
            }

            $orders_data[] = array(
                'customer_name'    => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'shipping_address' => str_replace( '<br/>', "\n", $shipping_address ), // Convert HTML breaks to newlines for plain text
                'phone_number'     => $order->get_billing_phone(),
                'order_id'         => $order->get_id(),
                'return_address'   => $this->options['return_address'],
            );
        }

        if ( 'pdf' === $this->options['output_format'] ) {
            $this->generate_pdf_labels( $orders_data );
        } else {
            $this->generate_html_labels( $orders_data );
        }
    }

    /**
     * Generates PDF shipping labels using TCPDF.
     *
     * @since    1.0.0
     * @param    array    $orders_data    Array of order data for labels.
     */
    private function generate_pdf_labels( $orders_data ) {
        if ( ! class_exists( 'TCPDF' ) ) {
            wp_die( __( 'TCPDF library not found. Please ensure it\'s installed in the `lib/tcpdf/` directory.', 'wsplp' ) );
        }

        // Create new PDF document
        // PAGE_FORMAT: 'A4' (210mm x 297mm)
        // UNIT: 'mm'
        $pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

        // Set document information
        $pdf->SetCreator( 'WooCommerce Shipping Label Printer' );
        $pdf->SetAuthor( 'Your Name' );
        $pdf->SetTitle( 'Shipping Labels' );
        $pdf->SetSubject( 'WooCommerce Shipping Labels' );

        // Remove default header/footer
        $pdf->setPrintHeader( false );
        $pdf->setPrintFooter( false );

        // Set auto page breaks
        $pdf->SetAutoPageBreak( true, $this->options['margin_bottom'] );

        // Set image scale factor
        $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

        // Set font
        $pdf->SetFont( 'helvetica', '', 10 );

        $rows_per_page = $this->options['rows_per_page'];
        $cols_per_page = $this->options['cols_per_page'];
        $label_width = $this->options['label_width'];
        $label_height = $this->options['label_height'];
        $margin_top = $this->options['margin_top'];
        $margin_bottom = $this->options['margin_bottom'];
        $margin_left = $this->options['margin_left'];
        $margin_right = $this->options['margin_right'];

        // Calculate printable area
        $page_width = $pdf->getPageWidth();
        $page_height = $pdf->getPageHeight();
        $printable_width = $page_width - $margin_left - $margin_right;
        $printable_height = $page_height - $margin_top - $margin_bottom;

        // Calculate horizontal and vertical spacing between labels
        $h_spacing = ( $printable_width - ( $cols_per_page * $label_width ) ) / ( $cols_per_page > 1 ? ( $cols_per_page - 1 ) : 1 );
        $v_spacing = ( $printable_height - ( $rows_per_page * $label_height ) ) / ( $rows_per_page > 1 ? ( $rows_per_page - 1 ) : 1 );

        if ( $cols_per_page == 1 ) $h_spacing = 0; // No horizontal spacing if only one column
        if ( $rows_per_page == 1 ) $v_spacing = 0; // No vertical spacing if only one row

        $current_label = 0;
        $total_labels = count( $orders_data );

        foreach ( $orders_data as $data ) {
            if ( $current_label % ( $rows_per_page * $cols_per_page ) === 0 ) {
                // Add a new page if it's the first label on a page or a new page is needed.
                $pdf->AddPage();
            }

            $row_index = floor( ( $current_label % ( $rows_per_page * $cols_per_page ) ) / $cols_per_page );
            $col_index = ( $current_label % ( $rows_per_page * $cols_per_page ) ) % $cols_per_page;

            $x = $margin_left + ( $col_index * ( $label_width + $h_spacing ) );
            $y = $margin_top + ( $row_index * ( $label_height + $v_spacing ) );

            // Start a new transaction to draw the label rectangle.
            // If the label content overflows, it won't be committed.
            $pdf->startTransaction();

            // Draw a border for visualization (optional)
            // $pdf->Rect($x, $y, $label_width, $label_height, 'D');

            // Set Y position for content within the label.
            // Add some padding inside the label.
            $padding_x = 3;
            $padding_y = 3;
            $content_x = $x + $padding_x;
            $content_y = $y + $padding_y;
            $content_width = $label_width - ( $padding_x * 2 );
            $content_height = $label_height - ( $padding_y * 2 );

            // Set font size for content
            $pdf->SetFont( 'helvetica', '', 9 );

            // Return Address
            $pdf->SetXY( $content_x, $content_y );
            $pdf->MultiCell( $content_width, 0, $data['return_address'], 0, 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', false );
            $pdf->Ln(2); // Small line break

            // Customer Name
            $pdf->SetFont( 'helvetica', 'B', 12 ); // Bold for customer name
            $pdf->SetX( $content_x );
            $pdf->Cell( $content_width, 0, $data['customer_name'], 0, 1, 'L', 0, '', 0, false, 'T', 'M' );
            $pdf->SetFont( 'helvetica', '', 10 ); // Reset font

            // Shipping Address
            $pdf->SetX( $content_x );
            $pdf->MultiCell( $content_width, 0, $data['shipping_address'], 0, 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', false );

            // Phone Number
            if ( ! empty( $data['phone_number'] ) ) {
                $pdf->SetX( $content_x );
                $pdf->Cell( $content_width, 0, 'Phone: ' . $data['phone_number'], 0, 1, 'L', 0, '', 0, false, 'T', 'M' );
            }

            // Order ID
            $pdf->SetX( $content_x );
            $pdf->Cell( $content_width, 0, 'Order ID: #' . $data['order_id'], 0, 1, 'L', 0, '', 0, false, 'T', 'M' );

            // Commit the transaction only if content fits
            if ( $pdf->getY() > ($y + $label_height) ) {
                // Content overflowed, rollback and add a new page.
                $pdf->rollbackTransaction(true); // true to reset Y position
                // Adjust current_label to re-render on the next page
                $current_label--; // This label needs to be re-drawn
            } else {
                $pdf->commitTransaction();
            }

            $current_label++;
        }

        // Output the PDF
        $pdf->Output( 'shipping_labels_' . date( 'Ymd_His' ) . '.pdf', 'I' ); // 'I' for inline, 'D' for download
        exit; // Important to exit after PDF output
    }

    /**
     * Generates HTML shipping labels.
     *
     * @since    1.0.0
     * @param    array    $orders_data    Array of order data for labels.
     */
    private function generate_html_labels( $orders_data ) {
        // Prepare styles for HTML output to simulate A4 and labels
        $rows_per_page = $this->options['rows_per_page'];
        $cols_per_page = $this->options['cols_per_page'];
        $label_width_mm = $this->options['label_width'];
        $label_height_mm = $this->options['label_height'];
        $margin_top_mm = $this->options['margin_top'];
        $margin_bottom_mm = $this->options['margin_bottom'];
        $margin_left_mm = $this->options['margin_left'];
        $margin_right_mm = $this->options['margin_right'];

        // A4 dimensions in mm: 210mm x 297mm
        // Convert mm to px (approx, 1mm = 3.77953px at 96 DPI)
        $mm_to_px_ratio = 3.77953;
        $label_width_px = $label_width_mm * $mm_to_px_ratio;
        $label_height_px = $label_height_mm * $mm_to_px_ratio;

        // Calculate total required space for labels including margins
        $total_labels_width_mm = ($label_width_mm * $cols_per_page);
        $total_labels_height_mm = ($label_height_mm * $rows_per_page);

        // A4 dimensions
        $a4_width_mm = 210;
        $a4_height_mm = 297;

        // Calculate horizontal and vertical gap between labels in mm
        $h_gap_mm = ($a4_width_mm - $margin_left_mm - $margin_right_mm - $total_labels_width_mm) / ($cols_per_page > 1 ? ($cols_per_page - 1) : 1);
        $v_gap_mm = ($a4_height_mm - $margin_top_mm - $margin_bottom_mm - $total_labels_height_mm) / ($rows_per_page > 1 ? ($rows_per_page - 1) : 1);

        if ($cols_per_page == 1) $h_gap_mm = 0;
        if ($rows_per_page == 1) $v_gap_mm = 0;


        ob_start();
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <title><?php esc_html_e( 'Shipping Labels', 'wsplp' ); ?></title>
            <style type="text/css">
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact; /* For better print output */
                }
                @page {
                    size: A4;
                    margin: <?php echo esc_attr( $margin_top_mm ); ?>mm <?php echo esc_attr( $margin_right_mm ); ?>mm <?php echo esc_attr( $margin_bottom_mm ); ?>mm <?php echo esc_attr( $margin_left_mm ); ?>mm;
                }
                .label-page {
                    box-sizing: border-box;
                    width: 210mm; /* A4 width */
                    min-height: 297mm; /* A4 height */
                    padding: 0; /* Margins set by @page */
                    display: grid;
                    grid-template-columns: repeat(<?php echo esc_attr( $cols_per_page ); ?>, <?php echo esc_attr( $label_width_mm ); ?>mm);
                    grid-template-rows: repeat(<?php echo esc_attr( $rows_per_page ); ?>, <?php echo esc_attr( $label_height_mm ); ?>mm);
                    gap: <?php echo esc_attr( $v_gap_mm ); ?>mm <?php echo esc_attr( $h_gap_mm ); ?>mm;
                    page-break-after: always;
                    break-after: always;
                }
                .label-page:last-child {
                    page-break-after: avoid;
                    break-after: avoid;
                }
                .shipping-label {
                    border: 1px dashed #ccc; /* Optional: for visual separation during design */
                    padding: 5mm;
                    box-sizing: border-box;
                    overflow: hidden; /* Prevent content overflow */
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                .label-header {
                    font-size: 0.8em;
                    margin-bottom: 5px;
                }
                .customer-name {
                    font-weight: bold;
                    font-size: 1.1em;
                    margin-bottom: 3px;
                }
                .shipping-address {
                    font-size: 0.9em;
                    white-space: pre-wrap; /* Preserve newlines */
                }
                .label-footer {
                    font-size: 0.8em;
                    margin-top: auto; /* Pushes to bottom */
                    text-align: right;
                }
            </style>
        </head>
        <body>
            <?php
            $labels_per_page = $rows_per_page * $cols_per_page;
            $current_label_on_page = 0;

            echo '<div class="label-page">';
            foreach ( $orders_data as $index => $data ) {
                if ( $current_label_on_page >= $labels_per_page ) {
                    echo '</div>'; // Close previous page
                    echo '<div class="label-page">'; // Start new page
                    $current_label_on_page = 0;
                }
                ?>
                <div class="shipping-label">
                    <div class="label-header">
                        <?php echo nl2br( esc_html( $data['return_address'] ) ); ?>
                    </div>
                    <div>
                        <div class="customer-name">
                            <?php echo esc_html( $data['customer_name'] ); ?>
                        </div>
                        <div class="shipping-address">
                            <?php echo nl2br( esc_html( $data['shipping_address'] ) ); ?>
                        </div>
                    </div>
                    <div class="label-footer">
                        <?php
                        if ( ! empty( $data['phone_number'] ) ) {
                            echo 'Phone: ' . esc_html( $data['phone_number'] ) . '<br/>';
                        }
                        echo 'Order ID: #' . esc_html( $data['order_id'] );
                        ?>
                    </div>
                </div>
                <?php
                $current_label_on_page++;
            }
            echo '</div>'; // Close last page
            ?>
        </body>
        </html>
        <?php
        $html_output = ob_get_clean();

        // Output the HTML
        echo $html_output;
        exit; // Important to exit after HTML output
    }
}
