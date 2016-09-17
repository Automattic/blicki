<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diff viewing functionality and admin review page
 */
class Blicki_Diff_Viewer {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
    }


	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=blicki',
			__( 'Revision Review', 'blicki' ),
			__( 'Revision Review', 'blicki' ),
			'edit_others_posts',
			'blicki-show-diff',
			array( $this, 'admin_revision_viewer' )
		);

		remove_submenu_page( 'edit.php?post_type=blicki', 'blicki-show-diff' );
	}

	public function admin_revision_viewer() {
		if ( ! isset( $_GET['source'] ) || ! isset( $_GET['revision'] ) ) {
			wp_die( "Source and revision are required", "Incorrect Usage" );
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( "edit_others_posts capability required", "No Capabilities" );
		}

		$source_id = absint( $_GET['source'] );
		$revision_id = absint( $_GET['revision'] );

		$nonce_name = 'moderate-post-' . $source_id . '-' . $revision_id;

		if ( isset( $_POST['action'] ) ) {
			// handle our actions here; approve, reject, maybe edit in the future
			check_admin_referer( $nonce_name );

			$source = get_post( $source_id );
			$revision = get_post( $revision_id );

			if ( 'approve' == $_POST['action'] ) {
				$id = wp_update_post( array(
					'ID' => $source_id,
					'post_title' => $revision->post_title,
					'post_content' => $revision->post_content
				), true );
				if ( is_wp_error( $id ) ) {
					$errors = $id->get_error_messages();
					foreach ( $errors as $error ) {
						echo $error;
					}
					wp_die( "Failed to update post", "Update Failed" );
				}
				wp_delete_post( $revision_id );
				echo "<h2>Suggestion Approved</h2>";
			} else if ( 'reject' == $_POST['action'] ) {
				wp_delete_post( $revision_id );
				echo "<h2>Suggestion Rejected</h2>";
			}
			echo "<a href='post.php?post=" . $source_id . "&action=edit'>Back to post</a>";
		} else {
			// this does the diff viewer, also used on front end
			$this->show_diffs( $source_id, $revision_id );

			// TODO approve/reject buttons need some styling perhaps?
			?>
			<form method='POST'>
				<?php wp_nonce_field( $nonce_name ); ?>
				<input type='hidden' name='action' value='reject'>
				<button type='submit'>Reject Edit</button>
			</form>
			<form method='POST'>
				<?php wp_nonce_field( $nonce_name ); ?>
				<input type='hidden' name='action' value='approve'>
				<button type='submit'>Approve Edit</button>
			</form>
			<?php
		}
	}

	public static function show_diffs( $source_id, $revision_id ) {
		// get posts, call wp_text_diff
		$source = get_post( $source_id );
		$revision = get_post( $revision_id );

		$diff_html = wp_text_diff( $source->post_title . "\n" . $source->post_content, $revision->post_title . "\n" . $revision->post_content, array( 'title' => 'Revision differences', 'title_left' => 'Original', 'title_right' => 'Revised' ) );

		echo $diff_html;
	}
}
new Blicki_Diff_Viewer();
