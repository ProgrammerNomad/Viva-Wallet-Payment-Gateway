<?php
/**
 * Plugin Name: WooCommerce Gateway Viva Wallet
 * Plugin URI:  https://github.com/ProgrammerNomad/WooCommerce-Gateway-Viva-Wallet
 * Description: Viva Wallet payment gateway for WooCommerce.
 * Version:     1.0.4
 * Author:      Nomad Programmer
 * Author URI:  https://github.com/ProgrammerNomad
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

// Add settings link on the plugins page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_viva_add_settings_link' );
function wc_viva_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=viva' ) . '">' . __( 'Settings', 'woocommerce-gateway-viva' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

// Include the auto-update class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-viva-auto-update.php';

// Initialize the auto-update class
$viva_auto_update = new WC_Viva_Auto_Update( 
    'https://raw.githubusercontent.com/ProgrammerNomad/WooCommerce-Gateway-Viva-Wallet/main/update-info.json', 
    plugin_basename( __FILE__ ) 
);