<?php
namespace Pro_Links_Maintainer;

class Pro_Links_Maintainer_Assets {

    function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'register' ], 5 );
        }
    }

    public function register() {
        $this->register_scripts( $this->get_scripts() );
    }

    private function register_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : false;
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
            $version   = isset( $script['version'] ) ? $script['version'] : PRO_LINKS_MAINTAINER_VERSION;

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }
    }

    public function get_scripts() {
        $prefix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.min' : '';

        $scripts = [
            'pro-links-maintainer-admin' => [
                'src'       => PRO_LINKS_MAINTAINER_ASSETS . '/dist/build.js',
                'deps'      => [ 'jquery' ],
                'version'   => filemtime( PRO_LINKS_MAINTAINER_PATH . '/assets/dist/build.js' ),
                'in_footer' => true
            ]
        ];

        return $scripts;
    }

}
