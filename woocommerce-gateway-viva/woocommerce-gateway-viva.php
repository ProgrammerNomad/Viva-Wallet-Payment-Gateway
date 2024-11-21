<?php
/**
 * Plugin Name: WooCommerce Gateway Viva Wallet
 * Plugin URI:  https://yourwebsite.com/
 * Description: Viva Wallet payment gateway for WooCommerce.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://yourwebsite.com/
 * Text Domain: woocommerce-gateway-viva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include the gateway class.
add_action( 'plugins_loaded', 'init_wc_gateway_viva' );
function init_wc_gateway_viva() {
	// The check for WC_Payment_Gateway is in class-wc-gateway-viva.php
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-gateway-viva.php';

	// Include the IPN response handler
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-viva-ipn-handler.php'; 

	// Initialize the IPN handler
	$viva_ipn_handler = new WC_Viva_IPN_Handler(); 

	// Add the gateway to WooCommerce.
	add_filter( 'woocommerce_payment_gateways', 'add_viva_gateway' );
	function add_viva_gateway( $gateways ) {
		$gateways[] = 'WC_Gateway_Viva';
		return $gateways;
	}
}