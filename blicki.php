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
				$toc = "<div class='blicki__toc-container'><strong>Table of Contents</strong><ol class='blicki__toc'></ol></div>";

				// add editor
				$editor = Blicki_Edit_Form::get_edit_form( $content, $post->ID );

				// grab revision history
				$revisions = $this->get_revision_history( $post->ID );

				?>
				<div class='blicki__header'>
					<div class='blicki__latest'>
						Latest update: <span>2016-09-17 at 11:37</span>
					</div>
					<div class="blicki__actions">
						<a href="#" class="blicki__actions-history">
							<svg width="15px" height="15px" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <path d="M0.09,8.6445 C0.6465,12.23025 3.7665,14.997 7.50075,14.997 C11.625,14.997 14.99925,11.62275 14.99925,7.4985 C14.99925,3.37425 11.62575,0 7.5015,0 C5.028,0 2.8515,1.20075 1.5,3 L1.5,3 L1.5,0.00075 L0.00225,0 L0,5.25 L5.25,5.25 L5.25,3.75 L2.85075,3.74925 L2.85075,3.74925 C3.9,2.40075 5.6265,1.5 7.5015,1.5 C10.8,1.5 13.5,4.2 13.5,7.4985 C13.5,10.797 10.8,13.49775 7.50075,13.49775 C4.59225,13.49775 2.15175,11.40225 1.614,8.6445 L0.09,8.6445 L0.09,8.6445 Z"></path>
                <polygon points="6.7515 3.74925 6.7515 7.72575 9.15225 10.9485 10.35225 10.05075 8.2515 7.275 8.2515 3.74925"></polygon>
							</svg>
							View History
						</a>
						<a href="#" class="blicki__actions-edit">
							<svg width="14px" height="14px" viewBox="0 0 14 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <path d="M7.36842105,2.94736842 L11.0526316,6.63157895 L4.04747368,13.6367368 C3.542,13.1312632 3.53978947,12.3148421 4.03863158,11.8056842 L4.03642105,11.8034737 C3.52726316,12.3023158 2.71084211,12.3001053 2.20536842,11.7946316 C1.70652632,11.2957895 1.69989474,10.4963158 2.17884211,9.98568421 L2.17294737,9.97978947 C1.66231579,10.458 0.862105263,10.4513684 0.364,9.95326316 L7.36842105,2.94736842 L7.36842105,2.94736842 Z M12.9581053,2.64231579 L11.3576842,1.04189474 C10.7822105,0.466421053 9.84936842,0.466421053 9.27389474,1.04189474 L8.10526316,2.21052632 L11.7894737,5.89473684 L12.9581053,4.72610526 C13.5335789,4.15063158 13.5335789,3.21778947 12.9581053,2.64231579 L12.9581053,2.64231579 Z M0,11.7894737 L0,14 L2.21052632,14 C2.21052632,12.7790526 1.22094737,11.7894737 0,11.7894737 L0,11.7894737 Z"></path>
							</svg>
							Edit
						</a>
					</div>
				</div>
				<hr>
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
