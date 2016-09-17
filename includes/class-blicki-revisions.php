<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blicki revision handling.
 */
class Blicki_Revision {

    /**
     * Revision ID.
     * @var integer
     */
    private $entry_id = 0;

    /**
     * Revision ID.
     * @var integer
     */
    private $revision_id = 0;

    /**
     * Construct.
     */
    public function __construct() {
        add_action( 'post_submitbox_start', array( $this, 'pending_revision_button' ) );
        add_filter( 'wp_insert_post_data' , array( $this, 'maybe_create_revision' ), 20, 2 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
    }

    /**
     * True if we're submitting changes, not an update.
     * @param int $post_id
     * @return boolean
     */
    public function is_suggesting_changes( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        }
        if ( ! $post = get_post( $post_id ) ) {
            return false;
        }
        if ( 'blicki' !== $post->post_type ) {
            return false;
        }
        if ( 'publish' !== $post->post_status ) {
            return false;
        }
        if ( empty( $_POST['blicki-suggest-changes'] ) ) {
            return false;
        }
        return true;
    }

    /**
     * Allow a post to be saved as a revision rather than updated right away.
     */
    public function pending_revision_button() {
        global $post;

        if ( 'blicki' !== $post->post_type ) {
            return;
        }

        echo '<div style="margin-bottom: 12px;"><input style="display:block; width:100%;" name="blicki-suggest-changes" type="submit" class="button button-large" value="' . esc_html__( 'Suggest changes', 'blicki' ) . '"></div>';
    }

    /**
     * Create a revision for this wiki entry if needed.
     * @param array $data
     * @param array $postarr
     */
    public function maybe_create_revision( $data, $postarr ) {
        $post_id = ! empty( $postarr['ID'] ) ? $postarr['ID'] : 0;

        if ( $post_id && $this->is_suggesting_changes( $post_id ) ) {
            $old_post_data = get_post( $post_id );

            // If content has changed, create a revision and prevent content from updating.
            if ( $old_post_data->post_content !== $data['post_content'] || $old_post_data->post_title !== $data['post_title'] ) {
                // Create revision.
                $this->entry_id    = $post_id;
                $this->revision_id = $this->create_revision( $post_id, $data );

                // Remove content update.
                $data['post_content'] = $old_post_data->post_content;
                $data['post_title']   = $old_post_data->post_title;

                // Make sure we redirect to the diff view after save.
                add_filter( 'redirect_post_location', array( $this, 'redirect_to_diff' ) );
            }
        }

        return $data;
    }

    /**
     * Create a revision for this wiki entry.
     * @param int $post_id
     * @param array $data
     */
    private function create_revision( $post_id, $data ) {
        return (int) wp_insert_post( array(
            'post_type'    => 'blicki-revision',
            'post_title'   => $data['post_title'],
            'post_content' => $data['post_content'],
            'post_parent'  => $post_id,
            'post_status'  => 'pending',
            'post_author'  => get_current_user_id(),
        ) );
    }

    /**
     * Redirect to diff.
     * @param  string $url
     * @return string
     */
    public function redirect_to_diff( $url ) {
        return $this->get_diff_viewer_url( $this->entry_id, $this->revision_id );
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box( 'blick-revisions', __( 'Blicki Revisions', 'blicki' ), array( $this, 'blick_revisions_content' ), 'blicki', 'side', 'high' );
    }

    /**
     * Show revisions when editing a wiki entry.
     */
    public function blick_revisions_content() {
        global $post;

        $post_id   = $post->ID;
        $revisions = $this->get_revisions_for_entry( $post_id );

        if ( $revisions ) {
            echo '<ul class="blicki-revision-list">';
            foreach ( $revisions as $revision_id ) {
                $revision = get_post( $revision_id );
                $date     = date_i18n( get_option( 'date_format' ), strtotime( $revision->post_date ) );

                if ( $revision->post_author ) {
                    $user     = get_user_by( 'id', $revision->post_author );
                    $username = $user->display_name;
                } else {
                    $username = '';
                }

                echo '<li class="blicki-revision-list-item">' . sprintf( esc_html_x( 'Revision by %s on %s', 'Revision by user on date', 'blicki' ), '<strong>' . $username . '</strong>', $date ) . '<br/><a href="' . esc_url( $this->get_diff_viewer_url( $post_id, $revision_id ) ) . '">' . esc_html__( 'Show diff', 'blicki' ) . '</a></li>';
            }
            echo '</ul>';
        }
    }

    /**
     * Get URL to diff view.
     * @param  int $source_id
     * @param  int $revision_id
     * @return string
     */
    public static function get_diff_viewer_url( $source_id, $revision_id ) {
        return add_query_arg( array( 'page' => 'blicki-show-diff', 'revision' => absint( $revision_id ), 'source' => absint( $source_id ) ), admin_url( 'edit.php?post_type=blicki' ) );
    }

    /**
     * Get IDs of revisions for an entry.
     * @param  int $id
     * @return int[]
     */
    public static function get_revisions_for_entry( $id ) {
        return get_posts( array(
            'fields'         => 'ids',
            'post_type'      => 'blicki-revision',
            'post_parent'    => $id,
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ) );
    }
}
new Blicki_Revision();
