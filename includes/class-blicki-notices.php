<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles notice output.
 */
class Blicki_Notices {
    /**
     * Stores notices.
     * @var array
     */
    private static $notices = array();

    /**
     * Constructor.
     */
    public function __construct() {
		add_action( 'admin_notices', array( $this, 'add_pending_suggestions_notice' ) );
    }

    public static function add( $notice_text, $type = 'error' ) {
        self::$notices[] = (object) array(
            'text' => $notice_text,
            'type' => $type,
        );
    }

    public static function display() {
        if ( ! empty( self::$notices ) ) {
            foreach ( self::$notices as $notice ) {
                echo '<div class="blicki-notice blicki-notice--' . esc_attr( $notice->type ) . '">' . wpautop( esc_html( $notice->text ) ) . '</div>';
            }
        }
    }

	public function add_pending_suggestions_notice() {
		$suggestions = get_posts( array(
            'fields'         => 'ids',
			'post_type'      => 'blicki-suggestion',
            'posts_per_page' => -1,
            'post_status'    => 'pending',
        ) );

		$num = count( $suggestions );
		if ( $num > 0 ) {
			?>
			<div class="notice notice-info"><p>
				<?php
				printf( _n(
					'Blicki - There is %d pending suggestion to approve',
					'Blicki - There are %d pending suggestions to approve',
					$num,
					'blicki' ),
				$num );
				echo " - <a href='" . admin_url( 'edit.php?post_type=blicki&orderby=pending_suggestions&order=desc' ) . "'>" . __( 'Entries', 'blicki' ) . "</a>";
				?>
			</p></div>
			<?php
		}
	}
}
new Blicki_Notices();
