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

        $res = new stdClass();
        $res->name           = $remote->name;
        $res->slug           = $this->plugin_basename;
        $res->version        = $remote->version;
        $res->tested         = $remote->tested;
        $res->requires       = $remote->requires;
        $res->author         = $remote->author;
        $res->author_profile = $remote->author_profile;
        $res->download_link  = $remote->download_url;
        $res->trunk          = $remote->download_url;
        $res->last_updated   = $remote->last_updated;
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
            $obj->url         = $remote->homepage;
            $obj->package     = $remote->download_url;
            $transient->response[ $this->plugin_basename ] = $obj;
        }

        return $transient;
    }
}