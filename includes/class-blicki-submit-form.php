<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the frontend edit forms.
 */
class Blicki_Submit_Form {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'blicki_submission_form', array( $this, 'submission_form' ) );
        add_action( 'template_redirect', array( $this, 'maybe_handle_submission_form' ) );
    }

	/**
	 * Produces the HTML for our frontend editor component.
	 */
	public static function submission_form() {
        Blicki_Notices::display();
		$settings = array( 'media_buttons' => false, 'quicktags' => false, 'editor_height' => 400 );
		?>
		<form class='blicki__edit' method='post'>
			<div class='blicki__edit-details'>
				<div class="blicki__edit-details-editor">
					<?php wp_editor( '', 'blicki-editor', $settings ); ?>
				</div>

                <div class="blicki__edit-details-field">
                    <label for='title'><?php _e( 'Enter an entry title:', 'blicki' ); ?></label>
                    <input type='text' name='blicki-title' required placeholder='<?php _e( 'Entry title', 'blicki' ); ?>' id='title' />
                </div>

				<?php if ( ! is_user_logged_in() ) : ?>
					<div class="blicki__edit-details-field">
						<label for='name'><?php _e( 'Enter your name:', 'blicki' ); ?></label>
						<input type='text' name='blicki-name' required placeholder='Your Name' id='name' />
					</div>
					<div class="blicki__edit-details-field">
						<label for='email'><?php _e( 'Enter your email address:', 'blicki' ); ?></label>
						<input type='email' name='blicki-email' required placeholder='email@example.com' id='email' />
					</div>
				<?php endif; ?>

				<div class="blicki__edit-details-submit">
					<button type='submit' class='blicki__edit-submit' name='blicki-submission-form'><?php _e( 'Suggest New Entry', 'blicki' ); ?></button>
					<?php wp_nonce_field( 'blicki-submit', 'blicki-nonce' ) ?>
				</div>
			</div>
		</form>
		<?php
	}

    /**
     * The edit form is on the frontend.
     */
    public function maybe_handle_submission_form() {
        if ( isset( $_POST['blicki-submission-form'] ) && wp_verify_nonce( $_POST['blicki-nonce'], 'blicki-submit' ) ) {
            $this->handle_submission_form();
        }
    }

    /**
     * Handle the posted edit form.
     */
    private function handle_submission_form() {
        try {
			if ( is_user_logged_in() ) {
				$email       = '';
				$name        = '';
				$post_author = get_current_user_id();
			} else {
				$email       = sanitize_text_field( $_POST[ 'blicki-email' ] );
				$name        = sanitize_text_field( $_POST[ 'blicki-name' ] );
				$post_author = 0;
			}

            $title   = sanitize_text_field( $_POST['blicki-title'] );
            $content = wp_kses_post( $_POST['blicki-editor'] );

			// Check name is valid.
            if ( ! $post_author && empty( $name ) ) {
                throw new Exception( __( 'Please enter your name.', 'blicki' ) );
            }

			// Check email is valid.
            if ( ! $post_author && ! is_email( $email ) ) {
                throw new Exception( __( 'Please enter a valid email address.', 'blicki' ) );
            }

			// Check content is not duplicated
			if ( empty( $content ) ) {
				throw new Exception( __( 'Please enter some content...', 'blicki' ) );
			}

            // Create pending entry
            $suggestion_id = wp_insert_post( array(
                'post_title'   => $title,
                'post_content' => $content,
                'post_author'  => $post_author,
                'post_status'  => 'pending',
                'post_type'    => 'blicki',
            ) );

            update_post_meta( $suggestion_id, '_blicki_author_email', $email );
    		update_post_meta( $suggestion_id, '_blicki_author_name', $name );

            Blicki_Notices::add( __( 'Thanks for submitting a new entry. A moderator will approve your entry as soon as possible.', 'blicki' ), 'success' );
        } catch ( Exception $e ) {
            Blicki_Notices::add( $e->getMessage(), 'error' );
        }
    }
}
new Blicki_Submit_Form();
