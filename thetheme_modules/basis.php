<?php
/**
 * The Theme Basis
 * 
 * Extend ACF.
 * 
 * @package thetheme
 * @version 1.0.0
 *
 */

/*
 * Helper function for returning a formatted object or array. If the input contains an ID of any kind, this will get printed too.
 * 
 * @since 1.0.0
 */

if (!function_exists('dump')) {

    function dump($var) {
        
        // Initialize the id value
        $id = null;

        // If object
        if (is_object($var)) {
            if (isset($var->id)) {
                $id = $var->id;
            } elseif (isset($var->ID)) {
                $id = $var->ID;
            }
        }

        // If array
        elseif (is_array($var)) {
            if (isset($var['id'])) {
                $id = $var['id'];
            } elseif (isset($var['ID'])) {
                $id = $var['ID'];
            }
        }

        echo '<div style="color: #8D4A4A;background: rgba(255,210,193,0.20); border: 2px solid #FFD2C1; padding: 4rem; margin: 4rem;">';
        
        // If any ID
        if (!is_null($id)) {
            echo '<span style="font-size: 3rem; margin-bottom: 2rem">ID <strong>' . htmlspecialchars($id) . '</strong></span>';
        }

        // Output the variable
        echo '<pre style="font-family: monospace; font-size: .825rem;">';
        print_r(($var));
        echo '</pre>';

        echo '</div>';

    }
}

 
/*
 * Add Default Posts & Comments RSS Feed to Head
 * 
 * @since 1.0.0
 */
add_theme_support('automatic-feed-links');

/*
 * WP Manages the <title>
 * 
 * @since 1.0.0
 */
add_theme_support('title-tag');

/*
 * Allows Featured Images
 * 
 * @since 1.0.0
 */
add_theme_support('post-thumbnails');

/*
 * Output valid HTML5 for search form, comment form, and comments
 * 
 * @since 1.0.0
 */
add_theme_support('html5', array(
    'search-form',
    'comment-form',
    'comment-list',
    'gallery',
    'caption',
    'script',
    'style',
));

/*
 * Enable support for Post Formats.
 * 
 * @since 1.0.0
 */
add_theme_support( 'post-formats', array(
    'aside',
    'image',
    'video',
    'quote',
    'link',
));

/*
 * Responsive Embeds
 * 
 * @since 1.0.0
 */
add_theme_support( 'responsive-embeds' );

/*
 * Disable Emoji's
 * 
 * @since 1.0.0
 */

function thetheme_disable_emojis_tinymce( $plugins ) {

    if ( is_array( $plugins ) ) {
        return array_diff( $plugins, array( 'wpemoji' ) );
    } else {
        return array();
    }

}

function thetheme_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {

    if ( 'dns-prefetch' == $relation_type ) {

        $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
        $urls = array_diff( $urls, array( $emoji_svg_url ) );

    }

    return $urls;

}

function thetheme_disable_emojis() {

    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'thetheme_disable_emojis_tinymce' );
    add_filter( 'wp_resource_hints', 'thetheme_disable_emojis_remove_dns_prefetch', 10, 2 );

}
add_action( 'init', 'thetheme_disable_emojis' );

/*
 * Excerpt Support for Pages
 * 
 * @since 1.0.0
 */
add_post_type_support( 'page', 'excerpt' );

/*
 * Removes the ... from the excerpt read more link
 * 
 * @since 1.0.0
 */
if ( ! function_exists( 'custom_excerpt_more' ) ) {

    function custom_excerpt_more($more) {
        return '';
    }
    
}
add_filter( 'excerpt_more', 'custom_excerpt_more' );

/**
 * Stylised Login & Theme Logo
 * 
 * @since 1.0.0
 */

function thetheme_login_css() {

    $login_css = get_stylesheet_directory()."/css/wp-login.css";

    if (file_exists($login_css)):

        echo '<style type="text/css">';
        echo file_get_contents($login_css);
        echo '</style>';

    endif;

}
add_action( 'login_enqueue_scripts', 'thetheme_login_css' );

function thetheme_login_logo() {

    $logo_path = '/images/logo.svg';
    $logo_img = get_stylesheet_directory_uri() . $logo_path;
    $logo_src = get_stylesheet_directory() . $logo_path;

    if ($logo_src) {
    
        echo '<style type="text/css">';
        echo '#login h1 a, .login h1 a {';
        echo 'background-image: url(' . $logo_img . ');';
        echo '}';
        echo '</style>';

    }
}
add_action( 'login_enqueue_scripts', 'thetheme_login_logo' );