<?php
/**
 * The Theme Gutenberg / Editor Support
 * 
 * Resource Loader
 * 
 * @package thetheme
 * @version 1.0.3
 *
 */

 /**
 * Duplicate Post Object
 * 
 * @since 1.0.3
 */

 function the_duplicate_post_as_draft() {
    
    global $wpdb;
    
    if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'the_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
        wp_die('Nothing to duplicate.');
    }
    
    
    if ( !isset( $_GET['duplicate_nonce'] ) || !wp_verify_nonce( $_GET['duplicate_nonce'], basename( __FILE__ ) ) ) {
        return;
    }

    // Get the post ID
    $post_id = (isset($_GET['post']) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );

    // Get the post
    $post = get_post( $post_id );

    // New author
    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;
    
    // Setup the post
    if (isset( $post ) && $post != null) {
        
        $args = array(
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
            'post_author'    => $new_post_author,
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_name'      => '',
            'post_parent'    => $post->post_parent,
            'post_password'  => $post->post_password,
            'post_status'    => 'draft',
            'post_title'     => $post->post_title,
            'post_type'      => $post->post_type,
            'to_ping'        => $post->to_ping,
            'menu_order'     => $post->menu_order
        );
        
        // save
        $new_post_id = wp_insert_post( $args );
        
        // terms
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }
        
        // meta
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");

        if (count($post_meta_infos)!=0) {

            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";

            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                if( $meta_key == '_wp_old_slug' ) continue;
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }

            $sql_query.= implode(" UNION ALL ", $sql_query_sel);
            $wpdb->query($sql_query);
        }
        
        // done
        wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
        exit;

    } else {

        wp_die('ID:' . $post_id . ' not found.');
    }
}
add_action( 'admin_action_the_duplicate_post_as_draft', 'the_duplicate_post_as_draft' );

// Add to WP-admin
function the_duplicate_post_link( $actions, $post ) {
    
    if (current_user_can('edit_posts')) {
        $actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=the_duplicate_post_as_draft&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce' ) . '" rel="permalink">Duplicate</a>';
    }
    return $actions;
}
add_filter( 'post_row_actions', 'the_duplicate_post_link', 10, 2 );
 
/**
 * Add support for Editor inline styles
 * 
 * @since 1.0.0
 * 
 */
add_theme_support('editor-styles');
add_editor_style('css/wp-editor-style.css');

/**
 * Disable Default Font Sizes
 * 
 * @since 1.0.0
 */
add_theme_support('disable-custom-font-sizes');

/**
 * Disable Default Colours
 * 
 * @since 1.0.0
 */
add_theme_support('disable-custom-colors');

/**
 * Disable Default Gradients
 * 
 * @since 1.0.0
 */
add_theme_support( 'disable-custom-gradients' );

/**
 * TODO
 * The Theme Editor Support
 * 
 * @since 1.0.0
function mytheme_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'mytheme-custom-gutenberg-js',
        get_template_directory_uri() . '/thetheme-editor/editor.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'lodash'), // Include 'lodash' here
        filemtime(get_template_directory() . '/thetheme-editor/editor.js'),
        true
    );
}
add_action('enqueue_block_editor_assets', 'mytheme_enqueue_block_editor_assets');
*/
