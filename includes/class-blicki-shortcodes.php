<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles shortcodes.
 */
class Blicki_Shortcodes {
    /**
     * Constructor.
     */
    public function __construct() {
		add_action( 'init', array( $this, 'add_shortcodes' ) );
    }

    /**
     * Add shortcodes.
     */
    public function add_shortcodes() {
        add_shortcode( 'blicki_submission_form', array( $this, 'blicki_submission_form' ) );
    }

    /**
     * Submit form.
     */
    public function blicki_submission_form() {
        ob_start();
        do_action( 'blicki_submission_form' );
        return ob_get_clean();
    }
}
new Blicki_Shortcodes();
