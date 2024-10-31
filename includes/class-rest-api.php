<?php

namespace Pro_Links_Maintainer;

use  App\Schemas ;
/**
 * Pro_Links_Maintainer_Rest_Api Handler
 */
class Pro_Links_Maintainer_Rest_Api
{
    private  $scanner ;
    private  $settings ;
    private  $urls ;
    private  $invalid_urls ;
    private  $system_logger ;
    public function __construct(
        Pro_Links_Maintainer_Scanner $scanner,
        Pro_Links_Maintainer_Settings $settings,
        Pro_Links_Maintainer_Urls $urls,
        Pro_Links_Maintainer_Invalid_Urls $invalid_urls,
        Pro_Links_Maintainer_System_Logger $system_logger
    )
    {
        $this->scanner = $scanner;
        $this->settings = $settings;
        $this->urls = $urls;
        $this->invalid_urls = $invalid_urls;
        $this->system_logger = $system_logger;
        $this->do_hooks();
    }
    
    public function do_hooks()
    {
        add_action( 'rest_api_init', array( $this, 'add_routes' ) );
        add_action( 'scan_links_hook', array( $this->scanner, 'scan_urls_background' ) );
        add_action( 'reparse_urls_hook', array( $this->urls, 'fill_urls' ) );
    }
    
    public function add_routes()
    {
        register_rest_route( 'api', '/version', array(
            'methods'              => 'GET',
            'callback'             => array( $this, 'get_version' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/scan', array(
            'methods'              => 'POST',
            'callback'             => array( $this, 'scan_background' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/scan', array(
            'methods'              => 'DELETE',
            'callback'             => array( $this, 'scan_background_force_quit' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/settings/global', array(
            'methods'              => 'GET',
            'callback'             => array( $this, 'get_global_settings' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/settings/bg', array(
            'methods'              => 'GET',
            'callback'             => array( $this, 'get_bg_settings' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/settings/global', array(
            'methods'              => 'PUT',
            'callback'             => array( $this, 'save_global_settings' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/urls', array(
            'methods'              => 'GET',
            'callback'             => array( $this, 'get_urls' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/invalid_urls', array(
            'methods'              => 'GET',
            'callback'             => array( $this, 'get_invalid_urls' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/invalid_url', array(
            'methods'              => 'DELETE',
            'callback'             => array( $this, 'delete_invalid_url_report' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/invalid_urls', array(
            'methods'              => 'DELETE',
            'callback'             => array( $this, 'delete_all_invalid_urls_reports' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/scan_status', array(
            'methods'              => 'GET',
            'callback'             => array( $this, 'scan_background_status' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/parser_status', array(
            'methods'              => 'GET',
            'callback'             => array( $this, 'parser_status' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
        register_rest_route( 'api', '/reparse_urls', array(
            'methods'              => 'POST',
            'callback'             => array( $this, 'reparse_urls' ),
            'args'                 => array(),
            'permissions_callback' => array( $this, 'permissions' ),
        ) );
    }
    
    function delete_all_invalid_urls_reports( $request )
    {
        $response = new \WP_REST_Response( '' );
        $del_result = $this->invalid_urls->deleteAllRecords();
        $status = ( $del_result ? 204 : 404 );
        $response->set_status( $status );
        return $response;
    }
    
    function delete_invalid_url_report( $request )
    {
        $id = $request['id'];
        $del_result = $this->invalid_urls->deleteRecord( $id );
        $response = new \WP_REST_Response( '' );
        $status = ( $del_result ? 204 : 404 );
        $response->set_status( $status );
        return $response;
    }
    
    function scan_background_status()
    {
        $response = new \WP_REST_Response( $this->scanner->get_scanner_status() );
        $response->set_status( 200 );
        return $response;
    }
    
    function permissions()
    {
        return current_user_can( 'manage_options' );
    }
    
    function get_version()
    {
        $json = array(
            "version"    => "1.011",
            "is_premium" => pro_links_maintainer_fs()->is__premium_only(),
        );
        $response = new \WP_REST_Response( $json );
        $response->set_status( 200 );
        return $response;
    }
    
    function scan_background( $request )
    {
        $this->scanner->set_scan_background_status_defaults_is_running();
        $settings_updated = [];
        $settings_updated['connection_timeout'] = intval( $request['settings']['connection_timeout'] );
        $settings_updated['post_pagination_limit'] = intval( $request['settings']['post_pagination_limit'] );
        $settings_updated['max_redirects'] = intval( $request['settings']['max_redirects'] );
        $settings_updated['filter_out'] = $request['settings']['filter_out'];
        $settings_updated['send_email'] = $request['settings']['send_email'];
        $settings_updated['email'] = $request['settings']['email'];
        $this->settings->saveBGSettings( $settings_updated );
        $this->system_logger->clear_log_file();
        $this->system_logger->debug( 'Starting background scan with settings: ' . print_r( $settings_updated, true ) );
        if ( !wp_next_scheduled( 'scan_links_hook' ) ) {
            wp_schedule_single_event( time(), 'scan_links_hook' );
        }
        $response = new \WP_REST_Response();
        $response->set_status( 204 );
        return $response;
    }
    
    function scan_background_force_quit( $request )
    {
        $option_name = $this->scanner->bg_scan_force_quit_option_name();
        add_option( $option_name, true );
        $this->scanner->set_scan_background_status_defaults();
    }
    
    function save_global_settings( $request )
    {
        $this->settings->saveGlobalSettings( $request );
        $response = new \WP_REST_Response();
        $response->set_status( 204 );
        return $response;
    }
    
    function get_global_settings()
    {
        $keys = $this->settings->getGlobalSettings();
        $response = new \WP_REST_Response( $keys );
        $response->set_status( 200 );
        return $response;
    }
    
    function get_bg_settings()
    {
        $keys = $this->settings->getBGSettings();
        $response = new \WP_REST_Response( $keys );
        $response->set_status( 200 );
        return $response;
    }
    
    function get_invalid_urls( $request )
    {
        $offset = $request['offset'];
        $limit = $request['limit'];
        $scan_result = $this->invalid_urls->get_invalid_urls( $offset, $limit );
        $invalid_urls = $scan_result;
        $response = new \WP_REST_Response( $invalid_urls );
        $response->set_status( 200 );
        return $response;
    }
    
    function get_urls( $request )
    {
        $offset = $request['offset'];
        $limit = $request['limit'];
        $links = $this->urls->get_urls( $offset, $limit, 0 );
        $response_links = array();
        foreach ( $links as $link ) {
            $post_id = $link['entity_id'];
            $post_url = get_post_permalink( $post_id );
            $post_edit_url = Pro_Links_Maintainer_Post_Commons::get_edit_post_link( $post_id );
            $link['entity_url'] = $post_url;
            $link['entity_edit_url'] = $post_edit_url;
            array_push( $response_links, $link );
        }
        $response = array(
            "urls" => $response_links,
        );
        $response = new \WP_REST_Response( $response );
        $response->set_status( 200 );
        return $response;
    }
    
    function parser_status()
    {
        $status = $this->urls->get_parsing_status();
        $response = new \WP_REST_Response( $status );
        $response->set_status( 200 );
        return $response;
    }
    
    function reparse_urls()
    {
        if ( !wp_next_scheduled( 'reparse_urls_hook' ) ) {
            wp_schedule_single_event( time(), 'reparse_urls_hook' );
        }
        $response = new \WP_REST_Response();
        $response->set_status( 201 );
        return $response;
    }

}