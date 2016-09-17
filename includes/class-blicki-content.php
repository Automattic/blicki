<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the frontend content for entries.
 */
class Blicki_Content {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
        add_filter( 'the_content', array( $this, 'wrap_wiki' ) );
    }

    /**
     * Scripts.
     */
    public function scripts() {
		wp_register_script( 'blicki_js', plugins_url( 'assets/js/blicki.js', BLICKI_FILE ), array( 'jquery' ), BLICKI_VERSION, true );
		wp_enqueue_style( 'blicki_css', plugins_url( 'assets/css/blicki.css', BLICKI_FILE ), array( 'revisions' ) );
    }

    /**
	 * Filter for 'the_content' to wrap a wiki entry in all our custom code.
	 */
	public function wrap_wiki( $content ) {
		if ( is_singular( 'blicki' ) ) {
            global $post;

            // Enqueue our script.
            wp_enqueue_script( 'blicki_js' );

			// Display notices.
			Blicki_Notices::display();

			// construct the wrapped output here as normal
			ob_start();

			if ( isset( $_GET['source'] ) && isset( $_GET['revision'] ) ) {
				// showing the revision diff for this pair
				Blicki_Diff_Viewer::show_diffs( $_GET['source'], $_GET['revision'] );

				// give a link back to the post
				echo '<a href=" ' . esc_url( get_permalink() ) . '">' . __( 'Return to entry', 'blicki' ) . '</a>';

            } else {
				// add editor
				$editor = Blicki_Edit_Form::get_edit_form( $content, $post->ID );

				// grab revision history
				$revisions = $this->get_revision_history( $post->ID );
				?>
				<div class='blicky-entry-content'>
					<?php echo $content; ?>
				</div>
                <div class='blicky-entry-contribute'>
                    CONTIBUTORS OUTPUT HERE

                    <a href="#" class="blicki-edit-link"><?php _e( 'Suggest an edit', 'blicki' ); ?></a>
                    <a href="#" class="blicki-show-revisions-link"><?php _e( 'Show revisions', 'blicki' ); ?></a>

                    <div id="blicky-edit-<?php echo absint( $post->ID ); ?>" class='blicky-entry-editor'>
                        <?php echo $editor; ?>
                    </div>

                    <div id="blicky-revisions-<?php echo absint( $post->ID ); ?>" class='blicky-entry-revisions'>
                        <?php echo $revisions; ?>
                        <a href="#" class="blicki-hide-revisions-link"><?php _e( 'Hide revisions', 'blicki' ); ?></a>
                    </div>
                </div>
				<?php
			}

			return ob_get_clean();
		}

		return $content;
	}

	/**
	 * Produces the HTML for the revision history
	 */
	private function get_revision_history( $id ) {
		ob_start();
		$revisions = Blicki_Revision::get_revisions_for_entry( $id );

        if ( $revisions ) {
            echo '<ul class="blicki-revision-list">';
			$prev_revision_id = $id;
            foreach ( array_reverse( $revisions ) as $revision_id ) {
                $revision = get_post( $revision_id );
                $date     = date_i18n( get_option( 'date_format' ), strtotime( $revision->post_date ) );

                if ( $revision->post_author ) {
                    $user     = get_user_by( 'id', $revision->post_author );
                    $username = $user->display_name;
                } else {
                    $username = '';
                }

				$revisions_url = add_query_arg( array( 'source' => $prev_revision_id, 'revision' => $revision_id ), get_permalink( $id ) );

				echo '<li class="blicki-revision-list-item">' . sprintf( esc_html_x( 'Revision by %s on %s', 'Revision by user on date', 'blicki' ), '<strong>' . $username . '</strong>', $date ) . '<br/><a href="' . esc_url( $revisions_url ) . '">' . esc_html__( 'Show diff', 'blicki' ) . '</a></li>';

				$prev_revision_id = $revision_id;
            }
            echo '</ul>';
        }
		return ob_get_clean();
	}
}
new Blicki_Content();
