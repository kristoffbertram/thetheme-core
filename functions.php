<?php
/**
 * The Theme Includes Loader
 * 
 * @since 1.0.0
 * 
 * Useful functions and overrides to WordPress. 
 * While you can modify this file to your own preference, 
 * it is advised to use thetheme-functions.php
 */
function thetheme_includes() {

    $thetheme_includes_dir = __DIR__."/thetheme_modules/";

    array_map(function($file) { 

        include $file;

    }, glob($thetheme_includes_dir."*.php"));

}
thetheme_includes();

/**
 * Custom Functions
 * 
 * @since 1.0.0
 * 
 * Bespoke functions to the theme
 */
$thetheme_functions = get_stylesheet_directory()."/thetheme-functions.php";

if (file_exists($thetheme_functions)) {

    include $thetheme_functions;
    
}