<?php
/**
 * The Theme Gutenberg / Editor Support
 * 
 * Resource Loader
 * 
 * @package thetheme
 * @version 1.0.0
 *
 */
 
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
