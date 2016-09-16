<?php
/**
 * Plugin Name: Blicki
 * Plugin URI: http://dev.wp-plugins.org/browser/blicki/
 * Author: Automattic
 * Version: ¯\_(ツ)_/¯
 * Author URI: http://automattic.com/
 * Requires at least: 4.6
 * Tested up to: 4.6
 * Text Domain: blicki
 * Domain Path: /languages/
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blicki {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );
    }

    /**
     * Textdomain.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'blicki', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

}
new Blicki();
