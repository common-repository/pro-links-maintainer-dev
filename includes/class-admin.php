<?php
namespace Pro_Links_Maintainer;

/**
 * Pro_Links_Maintainer_Admin Pages Handler
 */
class Pro_Links_Maintainer_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    /**
     * Register our menu page
     *
     * @return void
     */
    public function admin_menu() {
        global $submenu;

        $capability = 'manage_options';
        $slug       = 'pro-links-maintainer-app';

        $hook = add_menu_page( __( 'Pro Links Maintainer', 'textdomain' ), __( 'Pro Links Maintainer', 'textdomain' ), $capability, $slug, [ $this, 'plugin_page' ], 'dashicons-text' );

        if ( current_user_can( $capability ) ) {
            //$submenu[ $slug ][] = array( __( 'Scan Live', 'textdomain' ), $capability, 'admin.php?page=' . $slug . '#/scan_live' );
            $submenu[ $slug ][] = array( __( 'Urls listing', 'textdomain' ), $capability, 'admin.php?page=' . $slug . '#/urls_listing' );
            $submenu[ $slug ][] = array( __( 'Scan BG', 'textdomain' ), $capability, 'admin.php?page=' . $slug . '#/scan_bg' );
            $submenu[ $slug ][] = array( __( 'Invalid urls', 'textdomain' ), $capability, 'admin.php?page=' . $slug . '#/invalid_urls' );
            $submenu[ $slug ][] = array( __( 'Settings', 'textdomain' ), $capability, 'admin.php?page=' . $slug . '#/settings' );
        }

        add_action( 'load-' . $hook, [ $this, 'init_hooks'] );
    }

    /**
     * Initialize our hooks for the admin page
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Load scripts and styles for the app
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 'pro-links-maintainer-admin' );
        wp_enqueue_script( 'pro-links-maintainer-admin' );
    }

    /**
     * Render our admin page
     *
     * @return void
     */
    public function plugin_page() {
        echo '<div class="wrap"><div id="pro-links-maintainer-app"></div></div>';
    }
}
