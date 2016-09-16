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

/**
 * Main Bliki class.
 */
class Blicki {

    /**
     * Constructor.
     */
    public function __construct() {
        // Blicki constants.
        define( 'BLICKI_FILE', __FILE__ );
        define( 'BLICKI_DIR', trailingslashit( dirname( __FILE__ ) ) );

        register_activation_hook( basename( BLICKI_DIR ) . '/' . basename( BLICKI_FILE ), array( $this, 'activate' ) );

        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'includes' ) );

		add_filter( 'the_content', array( $this, 'wrap_wiki' ) );
    }

    /**
	 * Called on plugin activation
	 */
	public function activate() {
        $this->includes();
		Blicki_CPT::register_post_types();
		flush_rewrite_rules();
	}

    /**
     * Textdomain.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'blicki', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Includes.
     */
    public function includes() {
        include_once( BLICKI_DIR . 'includes/class-blicki-cpt.php' );
    }

	public function wrap_wiki( $content ) {
		global $post;

		if ( 'blicki' === $post->post_type ) {
			// add TOC
			$new_content = "<h1>TOC goes here</h1>" . $content;

			// add editor
			$new_content .= "<div id='editor'" . $post->ID . "'/>";
			wp_editor( $content, 'editor' . $post->ID );
		}

		return $new_content;
	}
}
new Blicki();
