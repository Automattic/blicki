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
			__( 'Suggestion Review', 'blicki' ),
			__( 'Suggestion Review', 'blicki' ),
			'edit_others_posts',
			'blicki-show-diff',
			array( $this, 'admin_suggestion_viewer' )
		);

		remove_submenu_page( 'edit.php?post_type=blicki', 'blicki-show-diff' );
	}

	public function admin_suggestion_viewer() {
		if ( ! isset( $_GET['source'] ) || ! isset( $_GET['suggestion'] ) ) {
			wp_die( "Source and suggestion are required", "Incorrect Usage" );
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( "edit_others_posts capability required", "No Capabilities" );
		}

		$source_id = absint( $_GET['source'] );
		$suggestion_id = absint( $_GET['suggestion'] );

		$nonce_name = 'moderate-post-' . $source_id . '-' . $suggestion_id;

		if ( isset( $_POST['action'] ) ) {
			// handle our actions here; approve, reject, maybe edit in the future
			check_admin_referer( $nonce_name );

			$source = get_post( $source_id );
			$suggestion = get_post( $suggestion_id );

			if ( 'approve' == $_POST['action'] ) {
				$id = wp_update_post( array(
					'ID' => $source_id,
					'post_title' => $suggestion->post_title,
					'post_content' => $suggestion->post_content
				), true );
				if ( is_wp_error( $id ) ) {
					$errors = $id->get_error_messages();
					foreach ( $errors as $error ) {
						echo $error;
					}
					wp_die( "Failed to update post", "Update Failed" );
				}
				
				wp_update_post( array(
					'ID'          => $suggestion_id,
					'post_status' => 'approved'
				) );

				echo "<h2>" . __( 'Suggestion Approved', 'blicki' ) . "</h2>";
			} else if ( 'reject' == $_POST['action'] ) {
				wp_delete_post( $suggestion_id );
				echo "<h2>" . __( 'Suggestion Rejected', 'blicki' ) . "</h2>";
			}
			echo "<a href='post.php?post=" . $source_id . "&action=edit'>Back to post</a>";
		} else {
			// this does the diff viewer, also used on front end
			$this->show_diffs( $source_id, $suggestion_id );

			// TODO approve/reject buttons need some styling perhaps?
			?>
			<form method='POST'>
				<?php wp_nonce_field( $nonce_name ); ?>
				<input type='hidden' name='action' value='reject'>
				<button type='submit'><?php _e( 'Reject Suggestion', 'blicki' ); ?></button>
			</form>
			<form method='POST'>
				<?php wp_nonce_field( $nonce_name ); ?>
				<input type='hidden' name='action' value='approve'>
				<button type='submit'><?php _e( 'Approve Suggestion', 'blicki' ); ?></button>
			</form>
			<?php
		}
	}

	public static function show_diffs( $source_id, $suggestion_id ) {
		// get posts, call wp_text_diff
		$source = get_post( $source_id );
		$suggestion = get_post( $suggestion_id );

		$diff_html = wp_text_diff( $source->post_title . "\n" . $source->post_content, $suggestion->post_title . "\n" . $suggestion->post_content, array( 'title' => __( 'Suggestion differences', 'blicki' ), 'title_left' => __( 'Original', 'blicki' ), 'title_right' => __( 'Suggested', 'blicki' ) ) );

		echo $diff_html;
	}
}
new Blicki_Diff_Viewer();
