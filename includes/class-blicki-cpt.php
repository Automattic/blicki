<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers CPT and taxonomies for WIKI entries.
 */
class Blicki_CPT {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
		add_filter( 'wp_revisions_to_keep', array( $this, 'revisions_to_keep' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_index' ) );
		add_action( 'post_updated', array( $this, 'create_revision_and_log' ), 5 );
		add_filter( 'manage_blicki_posts_columns', array( $this, 'columns_to_show' ) );
		add_filter( 'manage_edit-blicki_sortable_columns', array( $this, 'columns_to_sort' ) );
		add_action( 'manage_blicki_posts_custom_column', array( $this, 'data_for_column' ), 10, 2 );
    }

    /**
     * Register the WIKI custom post type.
     */
    public static function register_post_types() {
        if ( post_type_exists( 'blicki' ) ) {
            return;
        }
        $singular = __( 'Wiki Entry', 'blicki' );
        $plural   = __( 'Wiki Entries', 'blicki' );
        register_post_type( 'blicki',
            apply_filters( 'register_post_type_blicki', array(
                'labels' => array(
                    'name'               => $plural,
                    'singular_name'      => $singular,
                    'menu_name'          => __( 'Blicki', 'blicki' ),
                    'all_items'          => sprintf( __( 'All %s', 'blicki' ), $plural ),
                    'add_new'            => __( 'Add New', 'blicki' ),
                    'add_new_item'       => sprintf( __( 'Add %s', 'blicki' ), $singular ),
                    'edit'               => __( 'Edit', 'blicki' ),
                    'edit_item'          => sprintf( __( 'Edit %s', 'blicki' ), $singular ),
                    'new_item'           => sprintf( __( 'New %s', 'blicki' ), $singular ),
                    'view'               => sprintf( __( 'View %s', 'blicki' ), $singular ),
                    'view_item'          => sprintf( __( 'View %s', 'blicki' ), $singular ),
                    'search_items'       => sprintf( __( 'Search %s', 'blicki' ), $plural ),
                    'not_found'          => sprintf( __( 'No %s found', 'blicki' ), $plural ),
                    'not_found_in_trash' => sprintf( __( 'No %s found in trash', 'blicki' ), $plural ),
                    'parent'             => sprintf( __( 'Parent %s', 'blicki' ), $singular ),
                ),
                'description'         => sprintf( __( 'This is where you can create and manage %s.', 'blicki' ), $plural ),
                'public'              => true,
                'show_ui'             => true,
                'capability_type'     => 'post',
                'publicly_queryable'  => true,
                'exclude_from_search' => false,
                'hierarchical'        => false,
                'rewrite'             => array(
                    'slug'       => _x( 'wiki', 'Blicki permalink - resave permalinks after changing this', 'blicki' ),
                    'with_front' => false,
                    'feeds'      => true,
                    'pages'      => false,
                ),
                'query_var'         => true,
                'supports'          => array( 'title', 'editor', 'revisions' ),
                'has_archive'       => _x( 'wiki', 'Blicki post type archive slug - resave permalinks after changing this', 'blicki' ),
                'show_in_nav_menus' => false,
                'menu_icon'         => 'dashicons-carrot',
                'show_in_rest'      => true,
            ) )
        );
		register_post_type( 'blicki-suggestion', array(
			'public'   => false,
			'supports' => array(),
			'show_ui'  => false,
		) );
		register_post_status( 'approved', array(
			'label'                     => __( 'Approved', 'blicki' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		) );

        $singular = __( 'Topic', 'blicki' );
        $plural   = __( 'Topics', 'blicki' );
        register_taxonomy(
            'blicki_topics',
            'blicki',
            apply_filters( 'register_taxonomy_blicki_topics_args', array(
                'hierarchical' 			=> true,
                'update_count_callback' => '_update_post_term_count',
                'label' 				=> $plural,
                'labels' => array(
                    'name'              => $plural,
                    'singular_name'     => $singular,
                    'menu_name'         => ucwords( $plural ),
                    'search_items'      => sprintf( __( 'Search %s', 'blicki' ), $plural ),
                    'all_items'         => sprintf( __( 'All %s', 'blicki' ), $plural ),
                    'parent_item'       => sprintf( __( 'Parent %s', 'blicki' ), $singular ),
                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'blicki' ), $singular ),
                    'edit_item'         => sprintf( __( 'Edit %s', 'blicki' ), $singular ),
                    'update_item'       => sprintf( __( 'Update %s', 'blicki' ), $singular ),
                    'add_new_item'      => sprintf( __( 'Add New %s', 'blicki' ), $singular ),
                    'new_item_name'     => sprintf( __( 'New %s Name', 'blicki' ),  $singular ),
                ),
                'show_ui' 				=> true,
                'show_tagcloud'			=> false,
                'public' 	     		=> true,
                'rewrite' 				=> array(
					'slug'         => _x( 'wiki-topic', 'Wiki topic slug - resave permalinks after changing this', 'blicki' ),
					'with_front'   => false,
					'hierarchical' => false
				),
            ) )
        );
    }

	/**
	 * Make sure we always keep all revisions for our post type
	 * @param  int $num
	 * @param  object $post
	 * @return int
	 */
	public function revisions_to_keep ( $num, $post ) {
		if ( 'blicki' === $post->post_type ) {
			return -1;
		}
		return $num;
	}

	/**
	 * When a post is saved, and it's a blicki, update our index of titles/post ids.
	 * The index is used to add links to entries automagically.
	 */
	public function update_index( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        }
        if ( ! $post = get_post( $post_id ) ) {
            return false;
        }
        if ( 'blicki' !== $post->post_type ) {
            return false;
        }
        $blicki_index = get_option( '_blicki_index', array() );

		if ( 'publish' === $post->post_status ) {
			$blicki_index[ $post->ID ] = array(
				'post_title' => $post->post_title,
				'post_link'  => '<a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a>',
			);
		} else {
			unset( $blicki_index[ $post->ID ] );
		}

		update_option( '_blicki_index', $blicki_index );
	}

	public function columns_to_show( $columns ) {
		return array_merge( $columns, array(
			'pending_suggestions' => __( 'Pending Suggestions', 'blicki' ),
			'approved_suggestions' => __( 'Approved Suggestions', 'blicki' ),
			'topics' => __( 'Topics', 'blicki' )
		) );
	}

	public function columns_to_sort( $columns ) {
		// topics are not sortable yet, and might not need to be
		return array_merge( $columns, array(
			'pending_suggestions' => 'pending_suggestions',
			'approved_suggestions' => 'approved_suggestions'
		) );
	}

	public function data_for_column( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'pending_suggestions':
				$suggestions = Blicki_Suggestion::get_suggestions_for_entry( $post_id, 'pending' );
				echo count( $suggestions );
				break;
			case 'approved_suggestions':
				$suggestions = Blicki_Suggestion::get_suggestions_for_entry( $post_id, 'approved' );
				echo count( $suggestions );
				break;
			case 'topics':
				the_terms( $post_id, 'blicki_topics' );
				break;
		}
	}

	/**
	 * Log an edit by a user.
	 */
	public function create_revision_and_log( $post_id ) {
		if ( 'blicki' !== get_post_type( $post_id ) || ! get_current_user_id() ) {
			return false;
		}
		if ( ( $revision_id = wp_save_post_revision( $post_id ) ) && ! is_wp_error( $revision_id ) ) {
			Blicki_History::log_event( $post_id, 'updated', array(
				'user_id'     => get_current_user_id(),
				'revision_id' => $revision_id,
			) );
		}
	}
}
new Blicki_CPT();
