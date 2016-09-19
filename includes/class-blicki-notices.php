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
        add_action( 'admin_menu', array( $this, 'add_number_to_menu' ) );
    }

    public static function add( $notice_text, $type = 'error' ) {
        self::$notices[] = (object) array(
            'text' => $notice_text,
            'type' => $type,
        );
    }

	public static function has_error() {
		foreach ( self::$notices as $notice ) {
			if ( 'error' === $notice->type ) {
				return true;
			}
		}
		return false;
	}

    public static function display() {
        if ( ! empty( self::$notices ) ) {
            foreach ( self::$notices as $notice ) {
                echo '<div class="blicki-notice blicki-notice--' . esc_attr( $notice->type ) . '">' . wpautop( esc_html( $notice->text ) ) . '</div>';
            }
        }
    }

	public function add_pending_suggestions_notice() {
		if ( isset( $_GET['page'] ) && 'blicki-show-diff' === $_GET['page'] ) {
			// don't show this notice on the approval pages, that's silly
			return;
		}

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

	public function add_number_to_menu() {
		global $menu;

		$suggestions = get_posts( array(
            'fields'         => 'ids',
			'post_type'      => 'blicki-suggestion',
            'posts_per_page' => -1,
            'post_status'    => 'pending',
        ) );

		$num = count( $suggestions );

		if ( $num > 0 ) {
			foreach ( $menu as $prio => $menu_item ) {
				if ( 'Wiki' === $menu_item[0] ) {
					$menu[$prio][0] .= " <span class='update-plugins count-" . $num . "'><span class='plugin-count'>" . $num . "</span></span>";
					break;
				}
			}
		}
	}
}
new Blicki_Notices();
