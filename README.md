# WooCommerce Gateway Viva Wallet

This plugin extends WooCommerce with the Viva Wallet payment gateway, allowing you to accept payments using Viva Wallet's secure payment processing platform.

## Features

* Accepts payments via Viva Wallet.
* Redirects customers to Viva Wallet for secure payment processing.
* Supports automatic order status updates via webhooks.
* Saves transaction IDs for easy reference.
* Customizable settings for gateway title and description.

## Installation

1. **Download the plugin:** Download the latest release from the GitHub repository.
2. **Install the plugin:**  In your WordPress dashboard, go to **Plugins > Add New**. Click **Upload Plugin** and select the downloaded zip file.
3. **Activate the plugin:** Go to **Plugins > Installed Plugins** and activate the "WooCommerce Gateway Viva Wallet" plugin.
4. **Configure the plugin:** Go to **WooCommerce > Settings > Payments** and click on "Viva Wallet" to configure the gateway settings.

## Configuration

1. **Enable the gateway:** Check the "Enable Viva Wallet" checkbox.
2. **Enter your credentials:**
   * **Client ID (Merchant ID):** Enter your Viva Wallet Merchant ID.
   * **Client Secret (API Key):** Enter your Viva Wallet API Key.
   * **Live Source Code List:** Enter the source code(s) from your Viva Wallet account.
3. **Set the title and description:** Customize the title and description that will be displayed to customers during checkout.
4. **Save changes:** Click "Save changes" to save your settings.

## Webhooks

To enable automatic order status updates, you need to set up a webhook in your Viva Wallet account:

1. **Log in to your Viva Wallet account.**
2. **Go to the "Webhooks" or "IPN" settings section.**
3. **Create a new webhook.**
4. **Set the URL to:** `yourwebsite.com/?wc-api=wc_viva&vivawallet=webhook`
5. **Choose the events you want to be notified about (e.g., successful payments, failed payments).**
6. **Save the webhook settings.**

## Support

If you encounter any issues or have questions, please open an issue on the GitHub repository.

## Contributing

Contributions are welcome! Feel free to submit pull requests or report issues.

## License

This plugin is released under the GNU General Public License v2.0.