<?php
/**
 * The Theme ACF
 * 
 * Extend ACF.
 * 
 * @package thetheme
 * @version 1.0.0
 *
 */

/**
 * Dynamically register blocks using ACF
 * 
 * @since 1.0.0
 */
function thetheme_acf_blocks() {
    
    $blocks_dir = get_stylesheet_directory() . '/blocks';
    
    if (file_exists($blocks_dir)) {
        
        $dir = new DirectoryIterator($blocks_dir);

        foreach ($dir as $fileinfo) {
            
            if (!$fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }
            
            $block_path = $blocks_dir . '/' . $fileinfo->getFilename();
            
            register_block_type($block_path);
        }

    } else {

        error_log("Blocks directory not found: " . $blocks_dir);

    }

}
add_action('init', 'thetheme_acf_blocks');

/**
 * Add your own Google API key to ACF
 * 
 * @since 1.0.0
 * 
function my_acf_google_map_api($api)
    {

        global $the_config;

        $api['key'] = $the_config['google']['apikey'];

        return $api;

    }

    add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

    function my_acf_init()
    {

        global $the_config;

        acf_update_setting('google_api_key', $the_config['google']['apikey']);
    }

    add_action('acf/init', 'my_acf_init');
*/
