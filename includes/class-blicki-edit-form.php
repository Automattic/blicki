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
	 * Produces the HTML for our frontend editor component.
	 */
	public static function get_edit_form( $content, $id ) {
		// use an output buffer here because of wp_editor
		// and it lets us construct the additional fields normally
		ob_start();
		$settings = array( 'media_buttons' => false, 'quicktags' => false, 'editor_height' => 400 );
		?>
		<form class='blicki__edit' method='post'>
			<div class='blicki__edit-details'>
				<div class="blicki__edit-details-editor">
					<?php wp_editor( $content, 'blicki-editor-' . $id, $settings ); ?>
				</div>
				
				<?php if ( ! is_user_logged_in() ) : ?>
					<div class="blicki__edit-details-field">
						<label for='name<?php echo esc_attr( $id ); ?>'><?php _e( 'Enter your name:', 'blicki' ); ?></label><br>
						<input type='text' name='blicki-name-<?php echo esc_attr( $id ); ?>' placeholder='Your Name' id='name<?php echo esc_attr( $id ); ?>' />
					</div>
					<div class="blicki__edit-details-field">
						<label for='email<?php echo esc_attr( $id ); ?>'><?php _e( 'Enter your email address:', 'blicki' ); ?></label><br>
						<input type='email' name='blicki-email-<?php echo esc_attr( $id ); ?>' placeholder='email@example.com' id='email<?php echo esc_attr( $id ); ?>' />
					</div>
				<?php endif; ?>

				<div class="blicki__edit-details-field">
					<button type='submit' class='blicki__edit-submit' name='blicki-edit-form'><?php _e( 'Suggest Changes', 'blicki' ); ?></button>
					<input type='hidden' name='blicki-edit-entry' value="<?php echo esc_attr( $id ); ?>" />
					<a href="javascript:;" class='blicki__edit-cancel'><?php _e( 'Cancel', 'blicki' ); ?></a>
				</div>
			</div>
		</form>
		<?php
		$editor = ob_get_clean();
		return $editor;
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
	 * See if the content has not already been submitted..
	 * @param  int $entry_id
	 * @param  string $content
	 * @return boolean
	 */
	private function is_content_unique( $entry_id, $content ) {
		global $wpdb;

		return is_null( $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM $wpdb->posts WHERE post_type='blicki-suggestion' AND post_status='pending' AND post_parent=%d AND post_name=%s;",
				$entry_id,
				md5( $content )
			)
		) );
	}

    /**
     * Handle the posted edit form.
     * @param int $entry_id
     */
    private function handle_edit_form( $entry_id ) {
        try {
            $entry = get_post( $entry_id );

			if ( is_user_logged_in() ) {
				$email       = '';
				$name        = '';
				$post_author = get_current_user_id();
			} else {
				$email       = sanitize_text_field( $_POST[ 'blicki-email-' . $entry_id ] );
				$name        = sanitize_text_field( $_POST[ 'blicki-name-' . $entry_id ] );
				$post_author = 0;
			}

            $content = wp_kses_post( $_POST[  'blicki-editor-' . $entry_id ] );

			// Check name is valid.
            if ( ! $post_author && empty( $name ) ) {
                throw new Exception( __( 'Please enter your name.', 'blicki' ) );
            }

			// Check email is valid.
            if ( ! $post_author && ! is_email( $email ) ) {
                throw new Exception( __( 'Please enter a valid email address.', 'blicki' ) );
            }

			// Check content is not duplicated
			if ( ! $this->is_content_unique( $entry_id, $content ) ) {
				throw new Exception( __( 'This change has already been submitted.', 'blicki' ) );
			}

			$suggestion_id = Blicki_Suggestion::create_suggestion( $entry_id, array(
				'post_title'   => $entry->post_title,
				'post_content' => $content,
				'post_author'  => $post_author,
				'author_email' => $email,
				'author_name'  => $name,
            ) );

            Blicki_Notices::add( __( 'Thanks for submitting your suggestion. A moderator will approve your changes as soon as possible.', 'blicki' ), 'success' );
        } catch ( Exception $e ) {
            Blicki_Notices::add( $e->getMessage(), 'error' );
        }
    }
}
new Blicki_Edit_Form();
