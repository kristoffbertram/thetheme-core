<?php
/**
 * The Theme Extra's
 * 
 * Adds useful css classes etc
 *
 * @package thetheme
 * @version 1.0
 */

/**
 * Add practical body classes in slug form
 * 
 * @since 1.0.0
 */
function thetheme_add_slug_body_classes($classes) {

	global $post;
    $parent = '';
    
	if (isset($post)) {
	
		$classes[] = $post->post_type.'-'.$post->post_name.' '.$post->post_name;

        if (isset($post->post_parent))	{

            $ancestors=get_post_ancestors($post->ID);
            $root = count($ancestors)-1;
            
            if (isset($root) && $root >= 0) {

                $parent = $ancestors[$root];

            }
    
        } else {
    
            $parent = $post->ID;
        }
	
	}

    $classes[] = get_post_field( 'post_name', $parent );
    $classes[] = "parent-".get_post_field( 'post_name', $parent );

    return $classes;

}
add_filter( 'body_class', 'thetheme_add_slug_body_classes' );

/**
 * Add practical body classes in slug form for categories
 * 
 * @since 1.0.0
 */
function thetheme_add_category_body_post_classes($classes) {
	
	global $post;

    if (isset($post)) {
    
        foreach((get_the_category($post->ID)) as $category) {

            if (isset($category)) {

                $classes[] = 'cat-' . $category->cat_ID . '-id';

            }
        
        }

    }

	return $classes;
}
add_filter('post_class', 'thetheme_add_category_body_post_classes');
add_filter('body_class', 'thetheme_add_category_body_post_classes');

/**
 * Register taxonomy support
 * 
 * @since 1.0.0
 */
function tags_categories_support_all() {
    
    register_taxonomy_for_object_type('post_tag', 'page');
    register_taxonomy_for_object_type('category', 'page');  

}
  
/**
 * Make tags & categories work everywhere
 * 
 * @since 1.0.0
 */
function tags_categories_support_query($wp_query) {

    if ($wp_query->get('tag')) $wp_query->set('post_type', 'any');
    if ($wp_query->get('category_name')) $wp_query->set('post_type', 'any');

}
add_action('init', 'tags_categories_support_all');
add_action('pre_get_posts', 'tags_categories_support_query');


/**
 * A custom post type named SingleSnip 
 * 
 * @since 1.0.0
 */
if ( ! function_exists( 'singlesnip' ) ) {

    function singlesnipInitPostType() {

        register_post_type('singlesnip',
            array(
                'labels' => array(
                    'name' => 'Snips',
                    'singular_name' => 'Snip'
                ),

                'public' => false,
                'show_in_menu' => true,
                'show_ui' => true,
                'show_in_admin_bar' => true,
                'menu_position' => 5,
                'menu_icon' => 'dashicons-media-spreadsheet',
                'supports' => array('title', 'editor', 'revisions'),
                'show_in_rest' => true
            )
        );
    }
    add_action("init", "singlesnipInitPostType");

    function singlesnipTableColumns($columns) {
        $columns["shortcode"] 																				= "Shortcode";
        return $columns;
    }
    add_filter('manage_edit-singlesnip_columns', 'singlesnipTableColumns');

    function singlesnipTableOutput() {

        global $post;
        echo "[singlesnip id=\"".$post->ID."\"]";

    }
    add_action('manage_singlesnip_posts_custom_column', 'singlesnipTableOutput');

    function singlesnipGet($atts) {

        $id																									= $atts['id'];

        if ($id) {

            $post 																								= get_post($id);

            if ($post) {

                $post_content 																					= $post->post_content;

                if (is_array($atts)) {

                    foreach($atts as $k => $v) {

                        $post_content 																			= str_replace("{".$k."}" , $v, $post_content);
                        #$post_content																			= wpautop($post_content);

                    }
                }

                return apply_filters('the_content' , do_shortcode($post_content));

            } else {

                return '<!-- SingleSnip: ID (#'.$id.') was given but no Snip was found. -->';

            }

        } else {

            return '<!-- SingleSnip: no ID was given. -->';

        }

    }

    function singlesnipDo($atts) {

        return singlesnipGet($atts);

    }
    add_shortcode('singlesnip', 'singlesnipDo');

    function singlesnip($id) {

        echo do_shortcode('[singlesnip id="'.$id.'"]');

    }

}