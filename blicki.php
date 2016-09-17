<?php
/**
 * Plugin Name: Blicki
 * Plugin URI: http://dev.wp-plugins.org/browser/blicki/
 * Author: Automattic
 * Version: ¯\_(ツ)_/¯
 * Author URI: https://automattic.com/
 * Requires at least: 4.6
 * Tested up to: 4.6
 * Text Domain: blicki
 * Domain Path: /languages/
 * License: GPL2+
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Bliki class.
 */
class Blicki {

    /**
     * Constructor.
     */
    public function __construct() {
        // Blicki constants.
        define( 'BLICKI_FILE', __FILE__ );
        define( 'BLICKI_DIR', trailingslashit( dirname( __FILE__ ) ) );

        register_activation_hook( basename( BLICKI_DIR ) . '/' . basename( BLICKI_FILE ), array( $this, 'activate' ) );

        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		add_filter( 'the_content', array( $this, 'wrap_wiki' ) );
    }

    /**
	 * Called on plugin activation
	 */
	public function activate() {
        $this->includes();
		Blicki_CPT::register_post_types();
		flush_rewrite_rules();
	}

    /**
     * Textdomain.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'blicki', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Includes.
     */
    public function includes() {
        include_once( BLICKI_DIR . 'includes/class-blicki-cpt.php' );
        include_once( BLICKI_DIR . 'includes/class-blicki-revision.php' );
        include_once( BLICKI_DIR . 'includes/class-blicki-diff-viewer.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-edit-form.php' );
		include_once( BLICKI_DIR . 'includes/class-blicki-notices.php' );
    }

    /**
     * Scripts.
     */
    public function scripts() {
		wp_enqueue_script( 'blicki_js', plugins_url( 'assets/js/blicki.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
		wp_enqueue_style( 'blicki_css', plugins_url( 'assets/css/blicki.css', __FILE__ ), array( 'revisions' ) );
    }

	/**
	 * Filter for 'the_content' to wrap a wiki entry in all our custom code.
	 */
	public function wrap_wiki( $content ) {
		global $post;

		if ( 'blicki' === $post->post_type ) {

			// Add notices
			Blicki_Notices::display();

			// construct the wrapped output here as normal
			ob_start();

			if ( isset( $_GET['source'] ) && isset( $_GET['revision'] ) ) {
				// showing the revision diff for this pair
				Blicki_Diff_Viewer::show_diffs( $_GET['source'], $_GET['revision'] );

				// give a link back to the post
				?>
				<a href="<?php echo get_permalink(); ?>">Return to post</a>
				<?php
			} else {
				// showing the post with editor, etc
				$toc = "<ol class='blicki__toc'></ol>";

				// add editor
				$editor = Blicki_Edit_Form::get_edit_form( $content, $post->ID );

				// grab revision history
				$revisions = $this->get_revision_history( $post->ID );

				?>
				<div id='post-wrapper-<?= esc_attr( $post->ID ); ?>' class='post-wrapper'>
					<span>Content tab</span>
					<?php echo $toc; ?>
					<?php echo $content; ?>
				</div>
				<div>
					<span>Editor tab</span>
					<?php echo $editor; ?>
				</div>
				<div>
					<span>Revision History tab</span>
					<?php echo $revisions; ?>
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
new Blicki();
