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
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

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

    /**
     * Scripts.
     */
    public function scripts() {
		wp_enqueue_script( 'blicki_js', plugins_url( 'blicki.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
    }

	/**
	 * Filter for 'the_content' to wrap a wiki entry in all our custom code.
	 */
	public function wrap_wiki( $content ) {
		global $post;

		if ( 'blicki' === $post->post_type ) {
			// add TOC
			$toc = "<div class='toc'></div>";

			// add editor
			$editor = $this->get_editor( $content, $post->ID );

			// TODO redo this with an output buffer
			$new_content = sprintf( "<div id='post-wrapper-%d' class='post-wrapper' style='border: 1px solid black;'><span>Content tab</span>%s%s</div><div style='border: 1px solid black;'><span>Editor tab</span>%s</div>", esc_attr( $post->ID), $toc, $content, $editor );
			return $new_content;
		}

		return $content;
	}

	/**
	 * Produces the HTML for our frontend editor component.
	 */
	private function get_editor( $content, $id ) {
		ob_start();
		wp_editor( $content, 'editor' . $id );
		$editor = ob_get_clean();

		// TODO redo this with an output buffer and proper escaping
		$ret = sprintf( "<form><label for='email%d'>Email:</label><input style='text' name='email%d' placeholder='Enter your e-mail address' id='email%d' />%s<button type='submit'>Submit</button></form>", $id, $id, $id, $editor );
		return $ret;
	}
}
new Blicki();
