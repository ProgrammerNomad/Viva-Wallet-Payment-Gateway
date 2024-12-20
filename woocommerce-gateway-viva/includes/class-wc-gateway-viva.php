<?php

class WC_Gateway_Viva extends WC_Payment_Gateway {

	/**
	 * @var string
	 */
	public $client_id;

	/**
	 * @var string
	 */
	public $client_secret;

	/**
	 * @var string
	 */
	public $live_source_code_list;


	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'viva';
		$this->icon               = apply_filters( 'woocommerce_viva_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Viva Wallet', 'woocommerce-gateway-viva' );
		$this->method_description = __( 'Pay with your Viva Wallet account.', 'woocommerce-gateway-viva' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->client_id     = $this->get_option( 'client_id' );
		$this->client_secret = $this->get_option( 'client_secret' );
		$this->live_source_code_list = $this->get_option( 'live_source_code_list' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize gateway settings form fields.
	 */
	public function init_form_fields() {
		// Include the settings file
		require_once plugin_dir_path( __FILE__ ) . 'wc-viva-settings.php'; 
		$this->form_fields = wc_viva_get_settings(); 
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// 1. Get an Access Token
		$access_token = $this->get_access_token(); 
		if ( is_wp_error( $access_token ) ) {
			wc_add_notice( $access_token->get_error_message(), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		// 2. Create a Payment Order with Viva Wallet API
		$payment_order_response = $this->create_viva_wallet_payment_order( $order, $access_token );
		if ( is_wp_error( $payment_order_response ) ) {
			wc_add_notice( $payment_order_response->get_error_message(), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		// 3. Set Order Status to Pending
		$order->update_status( 'pending', __( 'Awaiting Viva Wallet payment.', 'woocommerce-gateway-viva' ) );

		// 4. Redirect to Viva Wallet Payment Page
		return array(
			'result'   => 'success',
			'redirect' => $payment_order_response, 
		);
	}

	/**
	 * Get Viva Wallet access token using OAuth 2.0.
	 *
	 * @return string|WP_Error Access token or WP_Error on failure.
	 */
	public function get_access_token() { 
		// Get credentials based on demo mode
		if ( $this->get_option( 'demo_mode' ) === 'yes' ) {
			$client_id = $this->get_option( 'demo_client_id' );
			$client_secret = $this->get_option( 'demo_client_secret' );
		} else {
			$client_id = $this->client_id;
			$client_secret = $this->client_secret;
		}

		// Base64 encode the credentials
		$credentials = base64_encode( $client_id . ':' . $client_secret );

		// Set API URL based on demo mode
		$api_url = $this->get_option( 'demo_mode' ) === 'yes' 
			? 'https://demo-accounts.vivapayments.com/connect/token' 
			: 'https://accounts.vivapayments.com/connect/token';

		$response = wp_remote_post( $api_url, array( 
			'headers' => array(
				'Authorization' => 'Basic ' . $credentials,
				'Content-Type'  => 'application/x-www-form-urlencoded', 
			),
			'body' => array(
				'grant_type' => 'client_credentials',
			),
		) );

		// For debugging, you can temporarily add this to inspect the response:
		// echo '<pre>'; print_r( $response ); echo '</pre>'; exit;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'viva_invalid_json', 'Invalid JSON response from Viva Wallet API.' );
		}

		if ( isset( $body->access_token ) ) {
			return $body->access_token;
		} else {
			// Handle errors (check for error messages in the response)
			error_log( 'Viva Wallet Access Token Error: ' . print_r( $body, true ) );
			return new WP_Error( 'viva_no_access_token', 'Could not get access token from Viva Wallet API.' );
		}
	}

	/**
	 * Create a payment order with Viva Wallet API.
	 *
	 * @param WC_Order $order Order object.
	 * @param string $access_token Viva Wallet access token.
	 * @return string|WP_Error Redirect URL or WP_Error on failure.
	 */
	private function create_viva_wallet_payment_order( $order, $access_token ) {
		$amount = $order->get_total(); // Amount in standard format
		$currency = $order->get_currency();
		$customer = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_id = $order->get_id();
        $billing_email = $order->get_billing_email();
        $billing_country = $order->get_billing_country();
        $billing_phone = $order->get_billing_phone();

		// Construct the success and cancel URLs
		$success_url = home_url( '/wc-api/wc_vivawallet_native_success' ).'?order_id=' . $order->get_id();
		$cancel_url = home_url( '/wc-api/wc_vivawallet_native_fail' ).'?order_id=' . $order->get_id();

		// Set API URL based on demo mode
		$api_url = $this->get_option( 'demo_mode' ) === 'yes'
			? 'https://demo-api.vivapayments.com/checkout/v2/orders'
			: 'https://api.vivapayments.com/checkout/v2/orders';

		// Get source code list based on demo mode
		$source_code_list = $this->get_option( 'demo_mode' ) === 'yes'
			? $this->get_option( 'demo_source_code_list' )
			: $this->live_source_code_list;

		$request_body = [
			'amount'           => $amount * 100, // Amount in cents
			'customerTrns'     => $order_id,
			'currencyCode'     => $this->getCurrencyCode( $currency ), // Using getCurrencyCode()
			'customerName'     => $customer,
			'sourceCode'       => $source_code_list,
			'paymentTimeOut'   => 600, 
			'merchantTrns'     => $order_id,
			'successUrl'       => $success_url,
			'failUrl'          => $cancel_url,
            'customer' => [
                'email' => $billing_email,
                'fullName' => $customer,
                'phone' => $billing_phone,
                'countryCode' => $billing_country,
                'requestLang' => $this->getRequestLanguage(),
            ],
            'disableCash' => true,
            'disableWallet' => true,
		];

		$response = wp_remote_post( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token, 
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $request_body ),
		) );

		// For debugging, you can temporarily add this to inspect the response:
		// echo '<pre>'; print_r( $response ); echo '</pre>'; exit;

		if ( is_wp_error( $response ) ) {
			return $response; 
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'viva_invalid_json', 'Invalid JSON response from Viva Wallet API.' );
		}

		// Construct the redirect URL using the orderCode
		if ( isset( $body->orderCode ) ) { 
			// Set redirect URL based on demo mode
			$redirect_base_url = $this->get_option( 'demo_mode' ) === 'yes'
				? 'https://demo.vivapayments.com/web/checkout'
				: 'https://www.vivapayments.com/web/checkout';

			$redirect_url = $redirect_base_url . '?ref=' . $body->orderCode;
			return $redirect_url;
		} else {
			// Handle the error 
			error_log( 'Viva Wallet API Error: ' . print_r( $body, true ) ); 
			return new WP_Error( 'viva_no_order_code', 'Could not get order code from Viva Wallet API.' );
		}
	}


	/**
	 * Get the maximum installments for the order based on the settings.
	 *
	 * @param WC_Order $order Order object.
	 * @return int
	 */
	private function getMaxInstallments( $order ) {
		$max_installments = 1; // Default to 1
		$installogic      = $this->vivawallet_instal; // You might need to define this property and related settings
		$total            = $order->get_total();

		if ( isset( $installogic ) && $installogic != '' ) {
			$installment_options = explode( ',', $installogic );
			$allowed_installments = [];

			foreach ( $installment_options as $option ) {
				list( $installment_amount, $installment_term ) = explode( ':', $option );
				if ( $total >= $installment_amount ) {
					$allowed_installments[] = trim( $installment_term );
				}
			}

			if ( count( $allowed_installments ) > 0 ) {
				$max_installments = max( $allowed_installments );
			}
		}

		return $max_installments;
	}

	/**
	 * Get the currency code for the API request.
	 *
	 * @param string $currency_code WooCommerce currency code.
	 * @return int
	 */
	private function getCurrencyCode( $currency_code ) {
		switch ( $currency_code ) {
			case 'HRK':
				$currency_symbol = 191; // CROATIAN KUNA.
				break;
			case 'CZK':
				$currency_symbol = 203; // CZECH KORUNA.
				break;
			case 'DKK':
				$currency_symbol = 208; // DANISH KRONE.
				break;
			case 'HUF':
				$currency_symbol = 348; // HUNGARIAN FORINT.
				break;
			case 'SEK':
				$currency_symbol = 752; // SWEDISH KRONA.
				break;
			case 'GBP':
				$currency_symbol = 826; // POUND STERLING.
				break;
			case 'RON':
				$currency_symbol = 946; // ROMANIAN LEU.
				break;
			case 'BGN':
				$currency_symbol = 975; // BULGARIAN LEV.
				break;
			case 'EUR':
				$currency_symbol = 978; // EURO.
				break;
			case 'PLN':
				$currency_symbol = 985; // POLISH ZLOTY.
				break;
			default:
				$currency_symbol = 978;
		}

		return $currency_symbol;
	}

	/**
	 * Get default language for smart checkout.
	 *
	 * @return string
	 */
	private function getRequestLanguage() {
		$supportedLanguages = [
			'bg' => 'bg-BG',
			'hr' => 'hr-HR',
			'cs' => 'cs-CZ',
			'da' => 'da-DK',
			'nl' => 'nl-NL',
			'en' => 'en-GB',
			'fi' => 'fi-FI',
			'fr' => 'fr-FR',
			'de' => 'de-DE',
			'el' => 'el-GR',
			'hu' => 'hu-HU',
			'it' => 'it-IT',
			'pl' => 'pl-PL',
			'pt' => 'pt-PT',
			'ro' => 'ro-RO',
			'es' => 'es-ES'
		];
		$locale               = get_locale();
		if ( ! in_array( $locale, $supportedLanguages ) ) {
			if ( isset( $supportedLanguages[ $locale ] ) ) {
				$locale = $supportedLanguages[ $locale ];
			} else {
				foreach ( [ '_', '-' ] as $separator ) {
					$localeParts = explode( $separator, $locale );
					if ( isset( $supportedLanguages[ $localeParts[0] ] ) ) {
						$locale = $supportedLanguages[ $localeParts[0] ];
						break;
					}
				}
				if ( ! in_array( $locale, $supportedLanguages ) ) {
					$locale = 'en-GB';
				}
			}
		}

		return $locale;
	}
}