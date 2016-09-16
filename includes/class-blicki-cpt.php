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
    }

    /**
     * Register the WIKI custom post type.
     */
    public static function register_post_types() {
        if ( post_type_exists( 'blicki' ) ) {
            return;
        }
        $singular = __( 'Entry', 'blicki' );
        $plural   = __( 'Entries', 'blicki' );
        register_post_type( 'blicki',
            apply_filters( 'register_post_type_blicki', array(
                'labels' => array(
                    'name'               => $plural,
                    'singular_name'      => $singular,
                    'menu_name'          => __( 'Wiki', 'blicki' ),
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
                'supports'          => array( 'title', 'editor' ),
                'has_archive'       => _x( 'wiki', 'Blicki post type archive slug - resave permalinks after changing this', 'blicki' ),
                'show_in_nav_menus' => false,
                'menu_icon'         => 'dashicons-media-text',
            ) )
        );
		register_post_type( 'blicki-revision', array(
			'public'   => true, // @todo
			'supports' => array(),
			'show_ui'  => true,
		) );

        /*
        @todo in the future
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
        );*/
    }
}
new Blicki_CPT();
