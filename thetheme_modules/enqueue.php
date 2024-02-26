<?php
/**
 * The Theme Enqueue Loader
 * 
 * Resource Loader
 * 
 * @package thetheme
 * @version 1.0.0
 *
 */

/**
 * Removes default WP Embed
 * 
 * @since 1.0.0
 * 
 */
function thetheme_remove_wp_embed() {

	wp_deregister_script( 'wp-embed' );

}
add_action('wp_footer', 'thetheme_remove_wp_embed');

/**
 * Enqueue The Theme Resources
 * 
 * @since 1.0.0
 * 
 */
function thetheme_enqueue() {
		
	$theme_version = wp_get_theme()->get('Version');
	
	$css_version_no = filemtime(get_stylesheet_directory() . '/css/app.css');
	$js_version_no  = filemtime(get_stylesheet_directory() . '/js/app.js');

	wp_enqueue_style('thetheme', get_stylesheet_directory_uri() . '/css/app.css', array(), $css_version_no);
	wp_enqueue_script('thetheme', get_stylesheet_directory_uri() . '/js/app.js', array(), $js_version_no, true);

}
add_action('wp_enqueue_scripts', 'thetheme_enqueue');
