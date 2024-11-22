<?php

class WC_Viva_Auto_Update {

    private $update_info_url;
    private $plugin_basename;

    public function __construct( $update_info_url, $plugin_basename ) {
        $this->update_info_url = $update_info_url;
        $this->plugin_basename = $plugin_basename;

        add_filter( 'plugins_api', array( $this, 'set_plugin_info' ), 10, 3 );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
    }

    public function set_plugin_info( $res, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $res;
        }

        if ( $this->plugin_basename !== $args->slug ) {
            return $res;
        }

        $remote = wp_remote_get( $this->update_info_url );
        if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) ) {
            return $res;
        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );
        if ( ! $remote ) {
            return $res;
        }

        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_basename );

        $res = new stdClass();
        $res->name           = $plugin_data['Name'];
        $res->slug           = $this->plugin_basename;
        $res->version        = $remote->version;
        $res->tested         = isset( $plugin_data['Tested up to'] ) ? $plugin_data['Tested up to'] : '6.7.1'; // Default if not set
        $res->requires       = isset( $plugin_data['Requires at least'] ) ? $plugin_data['Requires at least'] : '5.0.0'; // Default if not set
        $res->author         = $plugin_data['Author'];
        $res->author_profile = $plugin_data['AuthorURI']; 
        $res->download_link  = $remote->download_url;
        $res->trunk          = $remote->download_url;
        $res->last_updated   = date( 'Y-m-d h:i:s' );
        $res->sections       = array(
            'description' => $remote->sections->description,
            'changelog' => $remote->sections->changelog
        );

        return $res;
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = wp_remote_get( $this->update_info_url );
        if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) ) {
            return $transient;
        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );
        if ( ! $remote ) {
            return $transient;
        }

        if ( version_compare( $remote->version, $transient->checked[ $this->plugin_basename ], '>' ) ) {
            $obj = new stdClass();
            $obj->slug        = $this->plugin_basename;
            $obj->new_version = $remote->version;
            $obj->url         = 'https://github.com/ProgrammerNomad/WooCommerce-Gateway-Viva-Wallet'; 
            $obj->package     = $remote->download_url;
            $transient->response[ $this->plugin_basename ] = $obj;
        }

        return $transient;
    }
}