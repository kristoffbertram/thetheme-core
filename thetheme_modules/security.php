<?php
/**
 * The Theme Security
 * 
 * Adds basic security enhancements to WP's default output
 * 
 * @package thetheme
 * @version 1.0.0
 *
 */


/**
 * Removes WP Version number
 * 
 * @since 1.0.0
 * 
 */
remove_action('wp_head', 'wp_generator');

/**
 * Removes the generator tag with WP version numbers. Hackers will use this to find weak and old WP installs
 * 
 * @since 1.0.0
 * 
 */
function thetheme_remove_generator_version() {

	return '';

}
add_filter( 'wp_generator', 'thetheme_remove_generator_version' );

/**
 * Clean up wp_head().
 * 
 * @since 1.0.0
 * 
 */
remove_action('wp_head', 'wp_generator' );
remove_action('wp_head', 'rsd_link' );
remove_action('wp_head', 'wlwmanifest_link' );
remove_action('wp_head', 'index_rel_link' );
remove_action('wp_head', 'feed_links', 2 );
remove_action('wp_head', 'feed_links_extra', 3 );
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'parent_post_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
remove_action('wp_head', 'wp_shortlink_wp_head', 10);

/**
 * Show less info to users on failed login for security.
 * 
 * @since 1.0.0
 */
function thetheme_show_less_login_info() {
	
    return '';

}
add_filter( 'login_errors', 'thetheme_show_less_login_info' );

/**
 * Always hide the bar, excerpt for Admins.
 * 
 * @since 1.0.0
 */

function thetheme_remove_admin_bar() {

    if (!current_user_can('administrator') && !is_admin()) {

        show_admin_bar(false);

    }

}
add_action('after_setup_theme', 'thetheme_remove_admin_bar');

/**
 * Disallow the Theme Editor
 * 
 * @since 1.0.0
 */
define( 'DISALLOW_FILE_EDIT', true );

/**
 * Disable XMLRPC
 * 
 * @since 1.0.0
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Disable REST access to authed users only.
 * 
 * @since 1.0.0
 */
add_filter('rest_authentication_errors', function ($access) {

    if (!is_user_logged_in()) {
    
        return new WP_Error('rest_cannot_access', 'Only authenticated users can access the REST API.', array('status' => rest_authorization_required_code()));
    
    }
    
    return $access;

});

/**
 * Disable author pages (unless admin)
 * 
 * @since 1.0.0
 */
if (!is_admin()) {
    
    function thetheme_redirect_author_archive_to_homepage() {
    
        if (is_author()) {

            wp_redirect(home_url(), 301);
            exit;
            
        }
    }

    add_filter('template_redirect', 'thetheme_redirect_author_archive_to_homepage');

}