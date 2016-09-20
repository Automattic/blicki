<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper for wp_editor with our own prefs.
 */
function blicki_editor( $content = '', $id = 'blicki-editor' ) {
    $settings = array(
        'media_buttons' => false,
        'quicktags'     => false,
        'editor_height' => 400,
        'tinymce'       => array(
            'toolbar1' => 'formatselect,bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator,',
            'toolbar2' => '',
        ),
    );

    wp_editor(
        htmlspecialchars_decode( $content ),
        $id,
        $settings
    );
}
