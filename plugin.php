<?php

namespace Pro_Links_Maintainer;

/*
 * Plugin Name: Pro Broken Links Maintainer
 * Description: Easily find and fix broken links on your wordpress site.
 * Version: 1.1.7.5
 * Author: Maciej Bąk
 * License: GPL2
*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'Pro_Links_Maintainer\\pro_links_maintainer_fs' ) ) {
    pro_links_maintainer_fs()->set_basename( false, __FILE__ );
} else {
    
    if ( !function_exists( 'Pro_Links_Maintainer\\pro_links_maintainer_fs' ) ) {
        function pro_links_maintainer_fs()
        {
            global  $pro_links_maintainer_fs ;
            
            if ( !isset( $pro_links_maintainer_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $pro_links_maintainer_fs = fs_dynamic_init( array(
                    'id'             => '4230',
                    'slug'           => 'pro-links-maintainer',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_cea66c6076da5aa81bebb75817be9',
                    'is_premium'     => false,
                    'premium_suffix' => 'Premium',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                    'menu'           => array(
                    'slug'       => 'pro-links-maintainer-app',
                    'first-path' => 'admin.php?page=pro-links-maintainer-app#/urls_listing',
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $pro_links_maintainer_fs;
        }
        
        // Init Freemius.
        pro_links_maintainer_fs();
        // Signal that SDK was initiated.
        do_action( 'pro_links_maintainer_fs_loaded' );
    }
    
    final class Pro_Links_Maintainer
    {
        public  $version = '1.1.7.4' ;
        private  $container = array() ;
        public function __construct()
        {
            $this->define_constants();
            add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
            register_activation_hook( __FILE__, array( $this, 'activate' ) );
            register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        }
        
        public static function init()
        {
            static  $instance = false ;
            if ( !$instance ) {
                $instance = new Pro_Links_Maintainer();
            }
            return $instance;
        }
        
        /**
         * Define the constants
         *
         * @return void
         */
        public function define_constants()
        {
            define( 'PRO_LINKS_MAINTAINER_VERSION', $this->version );
            define( 'PRO_LINKS_MAINTAINER_FILE', __FILE__ );
            define( 'PRO_LINKS_MAINTAINER_PATH', dirname( PRO_LINKS_MAINTAINER_FILE ) );
            define( 'PRO_LINKS_MAINTAINER_INCLUDES', PRO_LINKS_MAINTAINER_PATH . '/includes' );
            define( 'PRO_LINKS_MAINTAINER_URL', plugins_url( '', PRO_LINKS_MAINTAINER_FILE ) );
            define( 'PRO_LINKS_MAINTAINER_ASSETS', PRO_LINKS_MAINTAINER_URL . '/assets' );
            define( 'BG_STATUS_OPTION_NAME', 'scan_urls_background_status' );
            define( 'BG_SCAN_PROGRESS', 'scan_urls_background_progress' );
            define( 'BG_SCAN_FORCE_QUIT', 'scan_urls_background_force_quit' );
            define( 'BG_SETTINGS', 'pro_links_maintainer_bg_settings' );
            define( 'GLOBAL_SETTINGS', 'pro_links_maintainer_global_settings' );
            define( 'GLOBAL_SETTINGS_GOOGLE_API_KEY', '' );
            define( 'GLOBAL_SETTINGS_MAILGUN_API_KEY', '' );
            define( 'GLOBAL_SETTINGS_MAILGUN_DOMAIN_NAME', '' );
            define( 'GLOBAL_SETTINGS_AUTO_CHECK_POSTS', false );
            define( 'GLOBAL_SETTINGS_LAST_SCAN_DATE_DIFFERENCE', 24 );
            define( 'GLOBAL_SETTINGS_USE_GOOGLE_API', false );
            define( 'BG_SETTINGS_RESPECT_LAST_SCAN_DATE', false );
            define( 'BG_SETTINGS_LAST_SCAN_DATE_DIFFERENCE', 24 );
            define( 'BG_SETTINGS_CONNECTION_TIMEOUT', 30 );
            define( 'BG_SETTINGS_POST_PAGINATION_LIMIT', 100 );
            define( 'BG_SETTINGS_MAX_REDIRECTS', 10 );
            define( 'BG_SETTINGS_FILTER_OUT', '' );
            define( 'BG_SETTINGS_FILTER', '' );
            define( 'BG_SETTINGS_SEND_EMAIL', false );
            define( 'BG_SETTINGS_EMAIL', '' );
            define( 'PARSER_STATUS', 'pro_links_maintainer_parser_status' );
        }
        
        /**
         * Load the plugin after all plugis are loaded
         *
         * @return void
         */
        public function init_plugin()
        {
            $this->includes();
            $this->init_classes();
            $this->init_hooks();
            wp_unschedule_event( time(), 'scan_links_hook' );
        }
        
        /**
         * Placeholder for activation function
         *
         * Nothing being called here yet.
         */
        public function activate()
        {
            //Maybe move this to __construct ??
            $this->includes();
            $this->init_classes();
            $installed = get_option( 'pro_links_maintainer_installed' );
            if ( !$installed ) {
                update_option( 'pro_links_maintainer_installed', time() );
            }
            update_option( 'pro_links_maintainer_version', PRO_LINKS_MAINTAINER_VERSION );
            $this->container['invalid_url']->createSchema();
            $this->container['entity_url']->createSchema();
            $this->container['entity_url']->deleteAllRecords();
            if ( !wp_next_scheduled( 'fill_urls_hook' ) ) {
                wp_schedule_single_event( time(), 'fill_urls_hook', array() );
            }
            //posts last scan date
            $this->container['wp_post_alter']->addColumns();
        }
        
        /**
         * Placeholder for deactivation function
         *
         * Nothing being called here yet.
         */
        public function deactivate()
        {
            $this->container['invalid_url']->deleteTable();
            $this->container['entity_url']->deleteTable();
            $this->container['wp_post_alter']->dropColumns();
        }
        
        /**
         * Include the required files
         *
         * @return void
         */
        public function includes()
        {
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-assets.php';
            if ( $this->is_request( 'admin' ) ) {
                require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-admin.php';
            }
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-rest-api.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-api-caller.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-scanner.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-scanner-yt.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-urls.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-settings.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-invalid-urls.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/class-system-logger.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/schemas/class-urls.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/schemas/class-invalid-url.php';
            require_once PRO_LINKS_MAINTAINER_INCLUDES . '/schemas/class-post-alter.php';
        }
        
        /**
         * Initialize the hooks
         *
         * @return void
         */
        public function init_hooks()
        {
            // Localize our plugin
            add_action( 'init', array( $this, 'localization_setup' ) );
            add_action( 'the_post', array( $this, 'on_the_post_init' ) );
            add_action(
                'save_post',
                array( $this, 'parse_and_save_post_urls' ),
                10,
                10
            );
            add_action(
                'on_the_post_hook',
                array( $this, 'check_post_links' ),
                10,
                1
            );
            add_action( 'fill_urls_hook', array( $this->container['urls'], 'fill_urls' ) );
        }
        
        function parse_and_save_post_urls( $id, $post )
        {
            $is_trash = 'trash' == get_post_status( $id );
            $urlsTable = new Schemas\Pro_Links_Maintainer_Entity_Url();
            
            if ( $post->post_parent == 0 && !$is_trash ) {
                $urlsTable->deletePostRecord( $id );
                //Needed for update
                $scanner = $this->container['scanner'];
                $parsed_urls = $scanner->parse_urls_from_post( $post );
                foreach ( $parsed_urls as $url ) {
                    $entity_url = array(
                        'url'         => $url['url'],
                        'entity_id'   => $id,
                        'entity_type' => 'POST',
                    );
                    $urlsTable->insertRecord( $entity_url );
                }
            } elseif ( $post->post_parent == 0 && $is_trash ) {
                $urlsTable->deletePostRecord( $id );
            }
        
        }
        
        function check_post_links( $post )
        {
            $settingsInstance = $this->container['settings'];
            $scanner = $this->container['scanner'];
            $auto_check_posts = $settingsInstance->getGlobalSettings()['auto_check_posts'];
            if ( $auto_check_posts ) {
                $scanner->scan_single_post_on_view( $post );
            }
        }
        
        function on_the_post_init( $post )
        {
            if ( !wp_next_scheduled( 'on_the_post_hook' ) ) {
                wp_schedule_single_event( time(), 'on_the_post_hook', array( $post ) );
            }
        }
        
        /**
         * Instantiate the required classes
         *
         * @return void
         */
        public function init_classes()
        {
            if ( $this->is_request( 'admin' ) ) {
                $this->container['admin'] = new Pro_Links_Maintainer_Admin();
            }
            $this->container['system_logger'] = new Pro_Links_Maintainer_System_Logger();
            $this->container['entity_url'] = new Schemas\Pro_Links_Maintainer_Entity_Url();
            $this->container['invalid_url'] = new Schemas\Pro_Links_Maintainer_Invalid_Url();
            $this->container['wp_post_alter'] = new Schemas\Pro_Links_Maintainer_Wp_Post_Alter();
            $this->container['settings'] = new Pro_Links_Maintainer_Settings();
            $this->container['api_caller'] = new Pro_Links_Maintainer_Url_Checker( $this->container['settings'], $this->container['system_logger'] );
            $this->container['scanner_yt'] = new Pro_Links_Maintainer_Scanner_YouTube( $this->container['api_caller'], $this->container['system_logger'] );
            $this->container['scanner'] = new Pro_Links_Maintainer_Scanner(
                $this->container['settings'],
                $this->container['api_caller'],
                $this->container['scanner_yt'],
                $this->container['entity_url'],
                $this->container['invalid_url'],
                $this->container['wp_post_alter'],
                $this->container['system_logger']
            );
            $this->container['urls'] = new Pro_Links_Maintainer_Urls( $this->container['scanner'], $this->container['entity_url'], $this->container['system_logger'] );
            $this->container['invalid_urls'] = new Pro_Links_Maintainer_Invalid_Urls();
            $this->container['rest'] = new Pro_Links_Maintainer_Rest_Api(
                $this->container['scanner'],
                $this->container['settings'],
                $this->container['urls'],
                $this->container['invalid_urls'],
                $this->container['system_logger']
            );
            $this->container['assets'] = new Pro_Links_Maintainer_Assets();
        }
        
        /**
         * Initialize plugin for localization
         *
         * @uses load_plugin_textdomain()
         */
        public function localization_setup()
        {
            load_plugin_textdomain( 'pro_links_maintainer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }
        
        /**
         * What type of request is this?
         *
         * @param  string $type admin, ajax, cron or frontend.
         *
         * @return bool
         */
        private function is_request( $type )
        {
            switch ( $type ) {
                case 'admin':
                    return is_admin();
                case 'rest':
                    return defined( 'REST_REQUEST' );
            }
        }
    
    }
    // Pro_Links_Maintainer
    $pro_links_maintainer = Pro_Links_Maintainer::init();
}
