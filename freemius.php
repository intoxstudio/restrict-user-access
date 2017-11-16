<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
	exit;
}

//<wp4.5 compatibility
if(!function_exists('wp_get_raw_referer')) {
	function wp_get_raw_referer() {
		if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			return wp_unslash( $_REQUEST['_wp_http_referer'] );
		} else if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			return wp_unslash( $_SERVER['HTTP_REFERER'] );
		}
	 
		return false;
	}
}

// Create a helper function for easy SDK access.
function rua_fs() {
    global $rua_fs;

    if ( ! isset( $rua_fs ) ) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/lib/freemius/start.php';

        $rua_fs = fs_dynamic_init( array(
            'id'                  => '1538',
            'slug'                => 'restrict-user-access',
            'type'                => 'plugin',
            'public_key'          => 'pk_606dec7b339c246a1bad6a6a04c52',
            'is_premium'          => false,
            'has_addons'          => true,
            'has_paid_plans'      => false,
            'menu'                => array(
                'slug'           => 'wprua',
                'contact'        => false,
                'support'        => false,
            ),
        ) );
    }

    return $rua_fs;
}

// Init Freemius.
rua_fs();
// Signal that SDK was initiated.
do_action( 'rua_fs_loaded' );

//eol