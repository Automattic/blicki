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

		// TODO title etc here

		// this does the diff viewer, also used on front end
		$this->show_diffs( $_GET['source'], $_GET['revision'] );

		// TODO approve/reject buttons
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
