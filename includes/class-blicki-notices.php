<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles notice output.
 */
class Blicki_Notices {

    /**
     * Stores notices.
     * @var array
     */
    private static $notices = array();

    public static function add( $notice_text, $type = 'error' ) {
        self::$notices[] = (object) array(
            'text' => $notice_text,
            'type' => $type,
        );
    }

    public static function display() {
        if ( ! empty( self::$notices ) ) {
            foreach ( self::$notices as $notice ) {
                echo '<div class="blicki-notice blicki-notice--' . esc_attr( $notice->type ) . '">' . wpautop( esc_html( $notice->text ) ) . '</div>';
            }
        }
    }

}
