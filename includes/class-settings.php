<?php

namespace Pro_Links_Maintainer;

final class Pro_Links_Maintainer_Settings
{
    private  $bg_default_settings = array(
        'respect_last_scan_date'    => BG_SETTINGS_RESPECT_LAST_SCAN_DATE,
        'last_scan_date_difference' => BG_SETTINGS_LAST_SCAN_DATE_DIFFERENCE,
        'connection_timeout'        => BG_SETTINGS_CONNECTION_TIMEOUT,
        'post_pagination_limit'     => BG_SETTINGS_POST_PAGINATION_LIMIT,
        'max_redirects'             => BG_SETTINGS_MAX_REDIRECTS,
        'use_google_api'            => true,
        'filter_out'                => BG_SETTINGS_FILTER_OUT,
        'filter'                    => BG_SETTINGS_FILTER,
        'send_email'                => BG_SETTINGS_SEND_EMAIL,
        'email'                     => BG_SETTINGS_EMAIL,
    ) ;
    private  $global_default_settings = array(
        'google_api_key'            => GLOBAL_SETTINGS_GOOGLE_API_KEY,
        'mailgun_api_key'           => GLOBAL_SETTINGS_MAILGUN_API_KEY,
        'mailgun_domain_name'       => GLOBAL_SETTINGS_MAILGUN_DOMAIN_NAME,
        'auto_check_posts'          => GLOBAL_SETTINGS_AUTO_CHECK_POSTS,
        'last_scan_date_difference' => GLOBAL_SETTINGS_LAST_SCAN_DATE_DIFFERENCE,
        'use_google_api'            => GLOBAL_SETTINGS_USE_GOOGLE_API,
    ) ;
    public function __construct()
    {
    }
    
    public function getBGSettings()
    {
        $saved_settings = get_option( BG_SETTINGS, $this->bg_default_settings );
        
        if ( pro_links_maintainer_fs()->is_not_paying() ) {
            unset( $saved_settings['use_google_api'] );
            unset( $saved_settings['filter'] );
            unset( $saved_settings['last_scan_date_difference'] );
            unset( $saved_settings['respect_last_scan_date'] );
        }
        
        return $saved_settings;
    }
    
    public function saveBGSettings( $newSettings )
    {
        $settings = $this->getBGSettings();
        $bg_settings_keys = $this->bg_default_settings;
        $keys = array();
        foreach ( $bg_settings_keys as $key => $value ) {
            $value = $newSettings[$key];
            $keys[$key] = $value;
        }
        update_option( BG_SETTINGS, $keys );
    }
    
    public function getGlobalSettings()
    {
        $saved_settings = get_option( GLOBAL_SETTINGS, $this->global_default_settings );
        $response_settings = array();
        $common_settings = array( 'auto_check_posts', 'last_scan_date_difference' );
        foreach ( $common_settings as $common_setting_name ) {
            $response_settings[$common_setting_name] = $saved_settings[$common_setting_name];
        }
        return $response_settings;
    }
    
    public function saveGlobalSettings( $newSettings )
    {
        $settings = $this->getGlobalSettings();
        $bg_settings_keys = $this->global_default_settings;
        $keys = array();
        foreach ( $bg_settings_keys as $key => $value ) {
            $value = $newSettings[$key];
            $keys[$key] = $value;
        }
        update_option( GLOBAL_SETTINGS, $keys );
    }

}