<?php

namespace Pro_Links_Maintainer;

final class Pro_Links_Maintainer_Scanner
{
    private  $settings ;
    private  $api_caller ;
    private  $scanner_yt ;
    private  $entity_url_schema ;
    private  $invalid_url_schema ;
    private  $wp_post_alter ;
    private  $system_logger ;
    public function __construct(
        Pro_Links_Maintainer_Settings $settings,
        Pro_Links_Maintainer_Url_Checker $api_caller,
        Pro_Links_Maintainer_Scanner_YouTube $scanner_yt,
        Schemas\Pro_Links_Maintainer_Entity_Url $entity_url_schema,
        Schemas\Pro_Links_Maintainer_Invalid_Url $invalid_url_schema,
        Schemas\Pro_Links_Maintainer_Wp_Post_Alter $wp_post_alter,
        Pro_Links_Maintainer_System_Logger $system_logger
    )
    {
        $this->settings = $settings;
        $this->api_caller = $api_caller;
        $this->scanner_yt = $scanner_yt;
        $this->entity_url_schema = $entity_url_schema;
        $this->invalid_url_schema = $invalid_url_schema;
        $this->wp_post_alter = $wp_post_alter;
        $this->system_logger = $system_logger;
        require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-post-commons.php';
        delete_option( BG_STATUS_OPTION_NAME );
    }
    
    public function bg_status_option_name()
    {
        return BG_STATUS_OPTION_NAME;
    }
    
    public function bg_scan_force_quit_option_name()
    {
        return BG_SCAN_FORCE_QUIT;
    }
    
    function save_invalid_urls( $urls )
    {
        // Check saved, if exists update time only
        foreach ( $urls as $url ) {
            $this->invalid_url_schema->insertRecord( $url );
        }
    }
    
    function save_last_scan_date_for_posts( $ids )
    {
        $this->wp_post_alter->set_last_scan_date_multi( $ids );
    }
    
    function process_scanning_urls( $all_urls, $use_google_api )
    {
        $api_caller = $this->api_caller;
        $yt_scanner = $this->scanner_yt;
        $entity_url_schema = $this->entity_url_schema;
        $urls = array();
        $yt_urls = array();
        $yt_channels_urls = array();
        $yt_users_urls = array();
        $yt_playlist_urls = array();
        $entity_ids = array();
        $urls_hashes = array();
        foreach ( $all_urls as $url ) {
            array_push( $entity_ids, $url['entity_id'] );
            array_push( $urls_hashes, $url['url_hash'] );
            $url_info = $this->get_url_info( $url['url'] );
            $url_type = $url_info['url_type'];
            $this->system_logger->debug( 'Will check url: ' . print_r( $url, true ) . ' url type: ' . $url_type );
            switch ( $url_type ) {
                case "normal":
                    array_push( $urls, $url );
                    break;
                case "youtube":
                    $url['yt_id'] = $url_info['yt_id'];
                    array_push( $yt_urls, $url );
                    break;
                case "youtube_channel":
                    $url['yt_id'] = $url_info['yt_id'];
                    array_push( $yt_channels_urls, $url );
                    break;
                case "youtube_user":
                    $url['yt_id'] = $url_info['yt_id'];
                    array_push( $yt_users_urls, $url );
                    break;
                case "youtube_playlist":
                    $url['yt_id'] = $url_info['yt_id'];
                    array_push( $yt_playlist_urls, $url );
                    break;
                default:
                    $this->system_logger->error( 'Invalid url_type: ' . $url_type . ' url: ' . print_r( $url, true ) );
            }
        }
        foreach ( $urls as $url ) {
            $error_msg = $api_caller->checkIfError( $url['url'] );
            $this->system_logger->debug( "Checking url: " . $url['url'] . ' ' . print_r( $error_msg, true ) );
            
            if ( strlen( $error_msg ) ) {
                $url['error'] = $error_msg;
                $this->save_invalid_urls( array( $url ) );
            }
        
        }
        $global_keys = $this->settings->getGlobalSettings();
        $yt_api_activated = $use_google_api && $global_keys['google_api_key'];
        $this->system_logger->debug( print_r( $bg_settings, true ) );
        $this->system_logger->debug( '$yt_urls: ' . print_r( $yt_urls, true ) . ' $yt_api_activated: ' . $yt_api_activated );
        //YT links
        
        if ( sizeof( $yt_urls ) > 0 ) {
            
            if ( $yt_api_activated ) {
                $yt_urls_invalid = $yt_scanner->scan_invalid_yt_urls_using_google_api( $yt_urls, $global_keys['google_api_key'] );
            } else {
                $yt_urls_invalid = $yt_scanner->scan_invalid_yt_urls_oembed( $yt_urls );
            }
            
            $this->save_invalid_urls( $yt_urls_invalid );
        }
        
        
        if ( $yt_api_activated ) {
            //YT channels
            
            if ( sizeof( $yt_channels_urls ) > 0 ) {
                $invalid_channels = array();
                $invalid_channels = $yt_scanner->scan_invalid_yt_channels_using_google_api( $yt_channels_urls, $global_keys['google_api_key'] );
                $this->save_invalid_urls( $invalid_channels );
            }
            
            //YT users
            
            if ( sizeof( $yt_users_urls ) > 0 ) {
                $invalid_users = array();
                $invalid_users = $yt_scanner->scan_invalid_yt_channels_by_user_using_google_api( $yt_users_urls, $global_keys['google_api_key'] );
                $this->save_invalid_urls( $invalid_users );
            }
            
            //YT playlist
            
            if ( sizeof( $yt_playlist_urls ) > 0 ) {
                $invalid_playlist = array();
                $invalid_playlist = $yt_scanner->scan_invalid_yt_playlist_using_google_api( $yt_playlist_urls, $global_keys['google_api_key'] );
                $this->save_invalid_urls( $invalid_playlist );
            }
        
        }
        
        $entity_url_schema->set_last_scan_date( array_unique( $urls_hashes ) );
        $this->save_last_scan_date_for_posts( $entity_ids );
    }
    
    function send_scan_finished_email( $finished_at )
    {
        $settings_global = $this->settings->getGlobalSettings();
        $mailgun_api_key = "";
        
        if ( $mailgun_api_key ) {
            $api_caller = $this->api_caller;
            $api_key = $mailgun_api_key;
            $domain = $settings_global['mailgun_domain_name'];
            $settings = $this->settings->getBGSettings();
            $to = $settings['email'];
            $api_caller->send_mailgun_mail(
                $to,
                "Scanning finished",
                "Finished at: " . $finished_at,
                $api_key,
                $domain
            );
        } else {
            $subject = 'Scanning finished.';
            $body = 'Scanning finished at ' . $finished_at;
            $from = 'From: Background Job <\'' . $to . '\'>';
            $headers = array( 'Content-Type: text/html; charset=UTF-8', $from );
            wp_mail(
                $to,
                $subject,
                $body,
                $headers
            );
        }
    
    }
    
    function send_email_if_on( $finished_at )
    {
        $bg_settings = $this->settings->getBGSettings();
        if ( $bg_settings['send_email'] ) {
            $this->send_scan_finished_email( $finished_at );
        }
    }
    
    public function parse_urls_from_post( $post )
    {
        $urls = array();
        //For duplicate checks
        $results = array();
        $input_line = $post->post_content;
        $output_array = array();
        $reg_exUrl = "#\\bhttp?s?://[^,\\s()<>]+(?:\\([\\w\\d]+\\)|([^,[:punct:]\\s]|/))#";
        preg_match_all( $reg_exUrl, $input_line, $output_array );
        foreach ( $output_array[0] as $str ) {
            $data = [];
            $post_id = $post->ID;
            $not_exists_already_in_results = !in_array( $str, $urls );
            
            if ( $not_exists_already_in_results ) {
                $prepared_url = str_replace( '\\u0026', '&', $str );
                $post_url_data = [
                    "post_id" => $post_id,
                    "url"     => htmlspecialchars_decode( $prepared_url ),
                ];
                $data += $post_url_data;
                array_push( $urls, $str );
                array_push( $results, $data );
            }
        
        }
        return array_unique( $results );
    }
    
    public function parse_urls_from_posts( $offset, $limit = 100 )
    {
        $urls = array();
        $query_args = array(
            'numberposts' => $limit,
            'offset'      => $offset,
            'post_type'   => array( 'post' ),
        );
        $posts = get_posts( $query_args );
        foreach ( $posts as $p ) {
            $res = $this->parse_urls_from_post( $p );
            $res_with_additional = array();
            foreach ( $res as $url ) {
                $post_id = $url['post_id'];
                $post_url = get_post_permalink( $post_id );
                $post_edit_url = Pro_Links_Maintainer_Post_Commons::get_edit_post_link( $post_id );
                $url['post_url'] = $post_url;
                $url['post_edit_url'] = $post_edit_url;
                array_push( $res_with_additional, $url );
            }
            $urls = array_merge( $urls, $res_with_additional );
        }
        $response = array(
            'urls'           => $urls,
            'has_more_posts' => $limit == sizeof( $posts ),
        );
        return $response;
    }
    
    function get_url_info( $url_str )
    {
        $url_type = 'normal';
        $yt_arr = array();
        preg_match_all( '/^.*(youtu.be\\/|v\\/|embed\\/|watch\\?|youtube.com\\/user\\/[^#]*#([^\\/]*?\\/)*)\\??v?=?([^#\\&\\?]*).*/', $url_str, $yt_arr );
        $is_yt = sizeof( $yt_arr[0] ) === 1;
        $url = array(
            'url_type' => 'normal',
            'yt_id'    => null,
        );
        $yt_channels_arr = array();
        $is_yt_channel = preg_match_all( '/(?:https|http)\\:\\/\\/(?:[\\w]+\\.)?youtube\\.com\\/(?:c\\/|channel\\/|user\\/)(.{1,})/', $url_str, $yt_channels_arr );
        $yt_playlist_arr = array();
        $is_yt_playlist = preg_match_all( '/(?:https|http)\\:\\/\\/(?:[\\w]+\\.)?youtube\\.com\\/(playlist\\?list=)(.{1,})/', $url_str, $yt_playlist_arr );
        
        if ( $is_yt ) {
            $url_type = 'youtube';
            $yt_id = $yt_arr[3][0];
            $url["yt_id"] = $yt_id;
            $url["url_type"] = $url_type;
        } elseif ( $is_yt_channel ) {
            $url_type = ( strpos( $url_str, 'user' ) ? 'youtube_user' : 'youtube_channel' );
            $yt_id = $yt_channels_arr[1][0];
            $url["yt_id"] = $yt_id;
            $url["url_type"] = $url_type;
        } elseif ( $is_yt_playlist ) {
            $url_type = 'youtube_playlist';
            $yt_id = $yt_playlist_arr[2][0];
            $url["yt_id"] = $yt_id;
            $url["url_type"] = $url_type;
        }
        
        return $url;
    }
    
    function get_urls_for_scan( $offset, $limit )
    {
        $bg_settings = $this->settings->getBGSettings();
        $urls_schema = $this->entity_url_schema;
        $last_scan_date_difference_hours = ( $bg_settings['respect_last_scan_date'] ? $bg_settings['last_scan_date_difference'] : null );
        $filter = '';
        $filter_out = ( $bg_settings['filter_out'] ? $bg_settings['filter_out'] : '' );
        $urls_to_scan = $urls_schema->get_urls(
            $offset,
            $limit,
            $last_scan_date_difference_hours,
            $filter_out,
            $filter
        );
        $response = array();
        foreach ( $urls_to_scan as $url ) {
            $url_str = $url['url'];
            $entity_id = $url['entity_id'];
            $entity_url = get_post_permalink( $entity_id );
            $entity_edit_url = Pro_Links_Maintainer_Post_Commons::get_edit_post_link( $entity_id );
            $url['entity_url'] = $entity_url;
            $url['entity_edit_url'] = $entity_edit_url;
            array_push( $response, $url );
        }
        return $response;
    }
    
    function set_last_scan_date( $post_id )
    {
        $this->wp_post_alter->set_last_scan_date( $post_id );
    }
    
    function scan_single_post( $post, $use_google_api )
    {
        $urlsTable = $this->entity_url_schema;
        $post_urls = $urlsTable->get_urls_for_single_post( $post->ID );
        $this->process_scanning_urls( $post_urls, $use_google_api );
        $this->set_last_scan_date( $post->ID );
    }
    
    public function scan_single_post_on_view( $post )
    {
        $global_settings = $this->settings->getGlobalSettings();
        $last_scan_date_difference = $global_settings['last_scan_date_difference'];
        $use_google_api = $global_settings['use_google_api'];
        $datetime = strtotime( $post->last_scan_date );
        // Convert to + seconds since unix epoch
        $yesterday = strtotime( "-" . $last_scan_date_difference . " hours" );
        // Convert today -1 day to seconds since unix epoch
        if ( $datetime <= $yesterday || is_null( $datetime ) ) {
            // if date value pulled is today or later, we're overdue
            $this->scan_single_post( $post, $use_google_api );
        }
    }
    
    function get_current_progress_defaults()
    {
        $bg_settings = $this->settings->getBGSettings();
        $last_scan_date_difference_hours = ( $bg_settings['respect_last_scan_date'] ? $bg_settings['last_scan_date_difference'] : null );
        $filter_out = $bg_settings['filter_out'];
        $filter = $bg_settings['filter'];
        $urls_to_scan = $this->entity_url_schema->get_urls_count( $last_scan_date_difference, $filter_out, $filter );
        return array(
            'scanned'    => 0,
            'to_scan'    => $urls_to_scan,
            'is_running' => false,
        );
    }
    
    public function set_scan_background_status_defaults()
    {
        $current_progress = get_option( BG_SCAN_PROGRESS );
        $current_progress_defaults = $this->get_current_progress_defaults();
        
        if ( $current_progress ) {
            update_option( BG_SCAN_PROGRESS, $current_progress_defaults );
        } else {
            add_option( BG_SCAN_PROGRESS, $current_progress_defaults );
        }
    
    }
    
    public function set_scan_background_status_defaults_is_running()
    {
        $current_progress = get_option( BG_SCAN_PROGRESS );
        $current_progress_defaults = $this->get_current_progress_defaults();
        $current_progress_defaults['is_running'] = true;
        
        if ( $current_progress ) {
            update_option( BG_SCAN_PROGRESS, $current_progress_defaults );
        } else {
            add_option( BG_SCAN_PROGRESS, $current_progress_defaults );
        }
    
    }
    
    public function scan_urls_background()
    {
        $bg_settings = $this->settings->getBGSettings();
        //Defaults sets in API , before running the hook
        $limit = $bg_settings['post_pagination_limit'];
        $offset = 0;
        $loop = true;
        $current_progress = get_option( BG_SCAN_PROGRESS );
        while ( $loop ) {
            $all_urls = $this->get_urls_for_scan( $offset, $limit, $bg_settings );
            $this->system_logger->debug( 'Scanning page offset: ' . $offset . ' limit: ' . $limit . ' urls size: ' . sizeof( $all_urls ) );
            $has_more_posts = sizeof( $all_urls ) === $limit;
            $use_google_api = $this->settings->getBGSettings()['use_google_api'];
            $this->process_scanning_urls( $all_urls, $bg_settings, $use_google_api );
            $offset += $limit;
            $has_more_posts = sizeof( $all_urls ) == $limit;
            $updated_progress = $current_progress;
            $updated_progress['scanned'] = $offset;
            update_option( BG_SCAN_PROGRESS, $updated_progress );
            $force_quit_opt_name = $this->bg_scan_force_quit_option_name();
            global  $wpdb ;
            $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}options WHERE option_name = 'scan_urls_background_force_quit'", OBJECT )[0];
            $should_force_quit = $results->option_value;
            
            if ( $should_force_quit ) {
                $loop = false;
                $max = 0;
                delete_option( $force_quit_opt_name );
            } else {
                $loop = $has_more_posts;
            }
        
        }
        $finished_at = current_time( 'mysql' );
        $updated_progress = $current_progress_defaults;
        $updated_progress['scanned'] = $current_progress_defaults['to_scan'];
        $updated_progress['finished_at'] = $finished_at;
        $updated_progress['is_running'] = false;
        update_option( BG_SCAN_PROGRESS, $updated_progress );
        $this->send_email_if_on( $finished_at );
    }
    
    public function get_scanner_status()
    {
        $current_progress = get_option( BG_SCAN_PROGRESS, $this->get_current_progress_defaults() );
        return $current_progress;
    }

}