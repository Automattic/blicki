<?php
/**
 * Plugin Name: Blicki
 * Plugin URI: http://dev.wp-plugins.org/browser/blicki/
 * Author: Automattic
 * Version: ¯\_(ツ)_/¯
 * Author URI: https://automattic.com/
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
 * Main Blicki class.
 */
class Blicki {

    /**
     * Constructor.
     */
    public function __construct() {
        // Blicki constants.
        define( 'BLICKI_FILE', __FILE__ );
        define( 'BLICKI_DIR', trailingslashit( dirname( __FILE__ ) ) );
		define( 'BLICKI_VERSION', '1.0.0' );

        register_activation_hook( basename( BLICKI_DIR ) . '/' . basename( BLICKI_FILE ), array( $this, 'activate' ) );

        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'init', array( $this, 'maybe_update' ) );
    }

    /**
	 * Called on plugin activation
	 */
	public function activate() {
        $this->includes();
		$this->create_tables();
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
        include_once( BLICKI_DIR . 'includes/class-blicki-suggestion.php' );
        include_once( BLICKI_DIR . 'includes/class-blicki-diff-viewer.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-edit-form.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-submit-form.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-notices.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-content.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-shortcodes.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-history.php' );
    }

	/**
	 * Create tables.
	 */
	public function create_tables() {
		global $wpdb;
		$wpdb->hide_errors();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $this->get_schema() );
	}

	/**
	 * Get table schema.
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		} else {
			$collate = '';
		}
		$tables = "
CREATE TABLE {$wpdb->prefix}blicky_history (
id bigint(20) NOT NULL AUTO_INCREMENT,
entry_id bigint(20) NOT NULL,
user_id bigint(20) NOT NULL default 0,
revision_id bigint(20) NOT NULL default 0,
user_name longtext NOT NULL,
user_email longtext NOT NULL,
event varchar(20) NOT NULL,
PRIMARY KEY  (id)
) $collate;
		";

		return $tables;
	}

	/**
	 * Maybe update blicki.
	 */
	public function maybe_update() {
		$version = get_option( 'blicki_version', 0 );

		if ( version_compare( $version, BLICKI_VERSION, '<' ) ) {
			$this->create_tables();
			update_option( 'blicki_version', BLICKI_VERSION );
		}
	}
}
new Blicki();
