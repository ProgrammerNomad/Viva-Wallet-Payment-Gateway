<?php

class WC_Viva_IPN_Handler {

    public function __construct() {
        add_action( 'woocommerce_api_wc_gateway_viva', array( $this, 'handle_ipn_response' ) );
        add_action( 'woocommerce_api_wc_vivawallet_native_success', array( $this, 'handle_vivawallet_native_success' ) );
        add_action( 'woocommerce_api_wc_vivawallet_native_fail', array( $this, 'handle_vivawallet_native_fail' ) );
    }

    public function handle_vivawallet_native_success() {
        // 1. Get the transaction ID
        $transaction_id = isset( $_GET['t'] ) ? $_GET['t'] : '';

        if ( empty( $transaction_id ) ) {
            wp_die( 'Transaction ID missing.' );
        }

        // 2. Get the order details from Viva Wallet using the transaction ID
        $order_details = $this->get_viva_order_details_by_transaction_id( $transaction_id );
        if ( is_wp_error( $order_details ) ) {
            wp_die( $order_details->get_error_message() );
        }

        // 3. Extract the WooCommerce order ID from the response
        $order_id = $this->get_order_id_from_order_details( $order_details );
        if ( ! $order_id ) {
            wp_die( 'Order ID not found in Viva Wallet response.' );
        }

        // 4. Extract the payment status from the response
        $payment_status = $this->get_payment_status_from_order_details( $order_details );

        // 5. Update the order status
        $this->update_order_status( $order_id, $transaction_id, $payment_status );

        // 6. Redirect to the thank you page
        $order = wc_get_order( $order_id );
        if ( $order ) {
            wp_redirect( $order->get_checkout_order_received_url() ); 
        } else {
            wp_redirect( wc_get_checkout_url() ); 
        }
        exit;
    }

    public function handle_vivawallet_native_fail() {
        // Handle payment failure
        $order_id = $_GET['order_id'];
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $order->update_status( 'failed', __( 'Viva Wallet payment failed.', 'woocommerce-gateway-viva' ) );
        }
        wp_redirect( wc_get_checkout_url() ); 
        exit;
    }

    public function handle_ipn_response() {
        // 1. Get the IPN data
        $ipn_data = file_get_contents( 'php://input' ); 

        // 2. Log the IPN data (optional, but recommended for debugging)
        $this->log_ipn_data( $ipn_data ); 

        // 3. Verify the IPN message (ensure it's from Viva Wallet)
        // ... (Implement verification logic if needed) ... 

        // 4. Check if the webhook is for an order update
        if ( isset( $_GET['vivawallet'] ) && $_GET['vivawallet'] == 'webhook' ) { 
            // 5. Extract the order code and status from the JSON response
            $order_code = $this->get_order_code_from_webhook( $ipn_data );
            $status_id  = $this->get_status_id_from_webhook( $ipn_data ); 

            if ( $order_code && $status_id ) {
                // 6. Get the WooCommerce order ID
                $order_id = $this->get_order_id_from_order_code( $order_code ); 

                if ( $order_id ) {
                    // 7. Update the order status
                    $this->update_order_status( $order_id, $order_code, $status_id ); 
                } else {
                    // Handle the case where the order ID is not found
                    $this->log_error( 'Order ID not found for order code: ' . $order_code ); 
                }
            } else {
                // Handle invalid webhook data
                $this->log_error( 'Invalid webhook data.' ); 
            }
        } else {
            // Handle invalid IPN requests
            wp_die( 'Invalid IPN request.' ); 
        }
    }


    private function log_ipn_data( $ipn_data ) {
        // Implement your logging logic here (e.g., write to a file or use a logging plugin)
        error_log( 'Viva Wallet IPN: ' . $ipn_data ); 
    }


    private function get_order_code_from_webhook( $ipn_data ) {
        $data = json_decode( $ipn_data, true );
        return isset( $data['EventData']['OrderCode'] ) ? $data['EventData']['OrderCode'] : false;
    }

    private function get_status_id_from_webhook( $ipn_data ) {
        $data = json_decode( $ipn_data, true );
        return isset( $data['EventData']['StatusId'] ) ? $data['EventData']['StatusId'] : false;
    }

    private function get_order_id_from_order_code( $order_code ) {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_transaction_id' AND meta_value = %s",
            $order_code
        );
        $order_id = $wpdb->get_var( $query );
        return $order_id ? $order_id : false;
    }

    private function update_order_status( $order_id, $transaction_id, $payment_status ) {
        $order = wc_get_order( $order_id );
        if ( $order ) {
            switch ( $payment_status ) {
                case 'completed':
                    // Save the transaction ID
                    $order->set_transaction_id( $transaction_id ); 
                    $order->save(); 

                    // Set the order status to processing
                    $order->update_status( 'processing', __( 'Viva Wallet payment successful.', 'woocommerce-gateway-viva' ) ); 
                    break;
                case 'failed':
                    $order->update_status( 'failed', __( 'Viva Wallet payment failed.', 'woocommerce-gateway-viva' ) );
                    break;
                // Add more cases for other status IDs as needed
                default:
                    // Handle other statuses or log them for investigation
                    $this->log_error( 'Unhandled Viva Wallet payment status: ' . $payment_status . ' for order ' . $order_id ); 
                    break;
            }
        }
    }

    private function log_error( $message ) {
        // Implement your error logging logic here
        error_log( 'Viva Wallet Webhook Error: ' . $message ); 
    }

    /**
     * Get Viva Wallet order details by transaction ID.
     *
     * @param string $transaction_id Viva Wallet transaction ID.
     * @return array|WP_Error Order details or WP_Error on failure.
     */
    private function get_viva_order_details_by_transaction_id( $transaction_id ) {
        // 1. Get the API credentials
        $viva_gateway = new WC_Gateway_Viva(); // You might need to adjust this
        
        // 2. Get the access token (call the public method)
        $access_token = $viva_gateway->get_access_token(); 

        // 3. Set the API URL based on demo mode
        $api_url = $viva_gateway->get_option( 'demo_mode' ) === 'yes'
            ? 'https://demo-api.vivapayments.com/checkout/v2/transactions/' . $transaction_id
            : 'https://api.vivapayments.com/checkout/v2/transactions/' . $transaction_id;

        $response = wp_remote_get( $api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token, 
            ),
        ) );

        // 4. Handle the API response
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'viva_invalid_json', 'Invalid JSON response from Viva Wallet API.' );
        }

        // 5. Return the order details
        return $body;
    }

    /**
     * Get order ID from Viva Wallet order details.
     *
     * @param array $order_details Viva Wallet order details.
     * @return int|false Order ID or false if not found.
     */
    private function get_order_id_from_order_details( $order_details ) {
        if ( isset( $order_details->merchantTrns ) ) {
            return $order_details->merchantTrns;
        }
        return false;
    }

    /**
     * Get payment status from Viva Wallet order details.
     *
     * @param array $order_details Viva Wallet order details.
     * @return string Payment status.
     */
    private function get_payment_status_from_order_details( $order_details ) {
        if ( isset( $order_details->statusId ) && $order_details->statusId === 'F' ) {
            return 'completed';
        }
        // Add more status checks as needed
        return 'pending'; // Default to pending if status is unclear
    }
}