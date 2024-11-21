<?php

class WC_Viva_IPN_Handler {

	public function __construct() {
		add_action( 'woocommerce_api_wc_gateway_viva', array( $this, 'handle_ipn_response' ) );
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

	private function update_order_status( $order_id, $order_code, $status_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			switch ( $status_id ) {
				case 'F': // Assuming 'F' represents a successful payment
					$order->payment_complete( $order_code );
					$order->add_order_note( __( 'Viva Wallet payment completed. Transaction ID: ', 'woocommerce-gateway-viva' ) . $order_code );
					break;
				case 'X': // Assuming 'X' represents a failed payment
					$order->update_status( 'failed', __( 'Viva Wallet payment failed.', 'woocommerce-gateway-viva' ) );
					break;
				// Add more cases for other status IDs as needed
				default:
					// Handle other statuses or log them for investigation
					$this->log_error( 'Unhandled Viva Wallet payment status: ' . $status_id . ' for order ' . $order_id ); 
					break;
			}
		}
	}

	private function log_error( $message ) {
		// Implement your error logging logic here
		error_log( 'Viva Wallet Webhook Error: ' . $message ); 
	}
}