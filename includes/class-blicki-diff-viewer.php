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
		add_filter( 'content_edit_pre', array( $this, 'maybe_merge_content' ), 10, 2 );
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
			<p><?php _e( 'Select one of the following actions:', 'blicki' ); ?></p>
			<ul>
				<form method='POST'>
					<?php wp_nonce_field( $nonce_name ); ?>
					<input type='hidden' name='action' value='reject'>
					<li><button type='submit'><?php _e( 'Reject Suggestion', 'blicki' ); ?></button></li>
				</form>
				<form method='POST'>
					<?php wp_nonce_field( $nonce_name ); ?>
					<input type='hidden' name='action' value='approve'>
					<li><button type='submit'><?php _e( 'Approve Suggestion', 'blicki' ); ?></button></li>
				</form>
				<li><a href='<?php echo add_query_arg( array( 'post' => $source_id, 'action' => 'edit', 'merge_from' => $suggestion_id ), 'post.php' ); ?>'><?php _e( 'Edit merged version', 'blicki' ); ?></a></li>
			</ul>
			<?php
		}
	}

	public static function show_diffs( $source_id, $suggestion_id ) {
		// get posts, call wp_text_diff
		$source = get_post( $source_id );
		$suggestion = get_post( $suggestion_id );

		$original_date = date_i18n( get_option( 'date_format' ), strtotime( $source->post_date ) );
		$suggestion_date = date_i18n( get_option( 'date_format' ), strtotime( $suggestion->post_date ) );

		$diff_html = wp_text_diff(
			$source->post_title . "\n" . $source->post_content,
			$suggestion->post_title . "\n" . $suggestion->post_content,
			array(
				'title' => __( 'Suggestion differences', 'blicki' ),
				'title_left' => sprintf( __( 'Original, %s', 'blicki' ), $original_date ),
				'title_right' => sprintf( __( 'Suggested, %s', 'blicki' ), $suggestion_date )
			)
		);

		echo $diff_html;
	}

	public function maybe_merge_content( $content, $post_id ) {
		if ( isset( $_GET['merge_from'] ) && 0 !== absint( $_GET['merge_from'] ) ) {
			$post = get_post( $post_id );
			$suggestion = get_post( absint( $_GET['merge_from'] ) );
			if ( ! empty( $post) && 'blicki' === $post->post_type
				&& ! empty( $suggestion ) && 'blicki-suggestion' === $suggestion->post_type ) {
				if ( ! class_exists( 'WP_Text_Diff_Renderer_Table', false ) ) {
					require( ABSPATH . WPINC . '/wp-diff.php' );
				}

				$text_diff = new Text_Diff( explode( "\n", $post->post_content ), explode( "\n", $suggestion->post_content ) );
				$merged_text = '';
				foreach ( $text_diff->_edits as $operation ) {
					if ( $operation instanceof Text_Diff_Op_copy ) {
						// copy just means use the final (or original, they're the same by definition )
						foreach ( $operation->final as $line ) {
							$merged_text .= $line . "\n";
						}
					} else {
						// all other operations have some difference between orig and final (might be false ie empty)
						$merged_text .= "ORIGINAL:\n\n";
						foreach ( $operation->orig as $line ) {
							$merged_text .= $line . "\n";
						}
						$merged_text .= "\nSUGGESTED:\n\n";
						foreach ( $operation->final as $line ) {
							$merged_text .= $line . "\n";
						}
					}
				}
				return $merged_text;
			}
		}

		return $content;
	}
}
new Blicki_Diff_Viewer();
