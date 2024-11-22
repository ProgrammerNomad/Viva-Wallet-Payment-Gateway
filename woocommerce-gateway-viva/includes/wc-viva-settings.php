<?php

function wc_viva_get_settings() {
	return array(
		'enabled' => array(
			'title'   => __( 'Enable/Disable', 'woocommerce-gateway-viva' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Viva Wallet', 'woocommerce-gateway-viva' ),
			'default' => 'yes',
		),
		'title' => array(
			'title'       => __( 'Title', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-viva' ),
			'default'     => __( 'Viva Wallet', 'woocommerce-gateway-viva' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'woocommerce-gateway-viva' ),
			'type'        => 'textarea',
			'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-viva' ),
			'default'     => __( 'Pay with your Viva Wallet account.', 'woocommerce-gateway-viva' ),
			'desc_tip'    => true,
		),
		'client_id' => array(
			'title'       => __( 'Client ID (Merchant ID)', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'Enter your Viva Wallet Client ID (Merchant ID).', 'woocommerce-gateway-viva' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'client_secret' => array(
			'title'       => __( 'Client Secret (API Key)', 'woocommerce-gateway-viva' ),
			'type'        => 'password', 
			'description' => __( 'Enter your Viva Wallet Client Secret (API Key).', 'woocommerce-gateway-viva' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'live_source_code_list' => array(
			'title'       => __( 'Live Source Code List', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'Provides a list with all source codes that are set in your Viva Wallet banking app.', 'woocommerce-gateway-viva' ),
			'default'     => '',
			'desc_tip'    => true,
		),

		'webhook_info' => array(
			'title'       => __( 'Webhook Information', 'woocommerce-gateway-viva' ),
			'type'        => 'title',
			'description' => '',
		),
		'webhook_url' => array(
			'title'       => __( 'Webhook URL', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'This is the URL where Viva Wallet will send payment notifications.', 'woocommerce-gateway-viva' ),
			'default'     => home_url( '/?wc-api=wc_viva&vivawallet=webhook' ), // Full URL
			'custom_attributes' => array( 'readonly' => 'readonly' ),
		),
		'success_url' => array(
			'title'       => __( 'Success URL', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'This is the URL where customers will be redirected after a successful payment.', 'woocommerce-gateway-viva' ),
			'default'     => home_url( '/wc-api/wc_vivawallet_native_success' ), // Full URL
			'custom_attributes' => array( 'readonly' => 'readonly' ),
		),
		'fail_url' => array(
			'title'       => __( 'Fail/Cancel URL', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'This is the URL where customers will be redirected after a failed or canceled payment.', 'woocommerce-gateway-viva' ),
			'default'     => home_url( '/wc-api/wc_vivawallet_native_fail' ), // Full URL
			'custom_attributes' => array( 'readonly' => 'readonly' ),
		),

		'demo_mode' => array(
			'title'       => __( 'Demo Mode', 'woocommerce-gateway-viva' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable Demo Mode', 'woocommerce-gateway-viva' ),
			'description' => __( 'Use the Viva Wallet demo environment for testing.', 'woocommerce-gateway-viva' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
		'demo_client_id' => array(
			'title'       => __( 'Demo Client ID', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'Enter your Viva Wallet Demo Client ID.', 'woocommerce-gateway-viva' ),
			'default'     => '',
			'desc_tip'    => true,
			'dependency'  => array(
				'field'    => 'demo_mode',
				'operator' => '==',
				'value'    => 'yes',
			),
		),
		'demo_client_secret' => array(
			'title'       => __( 'Demo Client Secret', 'woocommerce-gateway-viva' ),
			'type'        => 'password',
			'description' => __( 'Enter your Viva Wallet Demo Client Secret.', 'woocommerce-gateway-viva' ),
			'default'     => '',
			'desc_tip'    => true,
			'dependency'  => array(
				'field'    => 'demo_mode',
				'operator' => '==',
				'value'    => 'yes',
			),
		),
		'demo_source_code_list' => array(
			'title'       => __( 'Demo Source Code List', 'woocommerce-gateway-viva' ),
			'type'        => 'text',
			'description' => __( 'Enter your Viva Wallet Demo Source Code List.', 'woocommerce-gateway-viva' ),
			'default'     => '',
			'desc_tip'    => true,
			'dependency'  => array(
				'field'    => 'demo_mode',
				'operator' => '==',
				'value'    => 'yes',
			),
		),
	);
}