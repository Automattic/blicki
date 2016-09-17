<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the frontend edit forms.
 */
class Blicki_Edit_Form {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'template_redirect', array( $this, 'maybe_handle_edit_form' ) );
    }

    /**
     * The edit form is on the frontend.
     */
    public function maybe_handle_edit_form() {
        if ( isset( $_POST['blicki-edit-form'], $_POST['blicki-edit-entry'] ) ) {
            $entry_id = absint( $_POST['blicki-edit-entry'] );
            if ( 'blicki' === get_post_type( $entry_id ) ) {
                $this->handle_edit_form( $entry_id );
            }
        }
    }

    /**
     * Handle the posted edit form.
     * @param int $entry_id
     */
    private function handle_edit_form( $entry_id ) {
        try {
            $entry   = get_post( $entry_id );
            $email   = sanitize_text_field( $_POST[ 'blicki-email-' . $entry_id ] );
            $content = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST[  'blicki-editor-' . $entry_id ] ) ) );

            if ( ! is_email( $email ) ) {
                throw new Exception( __( 'Please enter a valid email address.', 'blicki' ) );
            }

            $revision_id = Blicki_Revision::create_revision( $entry_id, array(
                'post_title'   => $entry->post_title,
                'post_content' => $content,
                'post_author'  => 0,
            ) );

            add_post_meta( $revision_id, '_blicki_author_email', $email );

            Blicki_Notices::add( __( 'Thanks for submitting your revision. A moderator will approve your changes as soon as possible.', 'blicki' ), 'success' );
        } catch ( Exception $e ) {
            Blicki_Notices::add( $e->getMessage(), 'error' );
        }
    }
}
new Blicki_Edit_Form();
