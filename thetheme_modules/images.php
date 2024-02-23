<?php
/**
 * The Theme Image
 * 
 * Image & Thumbnail definitions
 * 
 * @package thetheme
 * @version 1.0.0
 *
 */

/**
 * Make WP use GD
 * 
 * @since 1.0.0
 */

 function thetheme_image_editor_to_gd( $editors ) {

	$gd_editor = 'WP_Image_Editor_GD';
	$editors = array_diff( $editors, array( $gd_editor ) );
	array_unshift( $editors, $gd_editor );
	return $editors;

}
add_filter( 'wp_image_editors', 'thetheme_image_editor_to_gd' );

/**
 * Removes useless default image sizes
 * 
 * @since 1.0.0
 * 
 */
function thetheme_remove_default_img_sizes( $sizes ) {

    $targets = [
        //'thumbnail',
        'medium',
        'medium_large',
        'large',
        '1536x1536',
        '2048x2048'
    ];

    foreach ($sizes as $size_index=>$size) {

        if ( in_array($size, $targets) ) {

            unset($sizes[$size_index]);

        }

    }

    return $sizes;
}

add_filter( 'intermediate_image_sizes', 'thetheme_remove_default_img_sizes', 10, 1);

/**
 * The Theme Images
 * 
 * @since 1.0.0
 * 
 * Takes an existing array of image_sizes and produced the necessary formats.
 */
class The_Theme_Image_Sizes {

    private $predefined_image_sizes;
    private $custom_image_sizes;
    private $selected_image_sizes;

    public function __construct($selected_image_sizes = [], $custom_image_sizes = []) {
        $this->selected_image_sizes = $selected_image_sizes;
        $this->custom_image_sizes = $custom_image_sizes;
        $this->init_predefined_image_sizes();

        add_action('after_setup_theme', [$this, 'register_image_sizes']);
        add_filter('image_size_names_choose', [$this, 'add_sizes_to_media_library']);
    }

    private function init_predefined_image_sizes() {
        $this->predefined_image_sizes = [
            
            ["square-xxs", 160, 160, true, "Square Extra Extra Small"],
            ["square-xs", 320, 320, true, "Square Extra Small"],
            ["square-sm", 640, 640, true, "Square Small"],
            ["square-md", 960, 960, true, "Square Medium"],
            ["square-lg", 1024, 1024, true, "Square Large"],
            ["square-xl", 1280, 1280, true, "Square XL"],
            
            ["square-xs-left-center", 321, 321, ['left', 'center'], "Square Extra Small (From Left)"],
            ["square-sm-left-center", 641, 641, ['left', 'center'], "Square Small (From Left)"],
            ["square-md-left-center", 961, 961, ['left', 'center'], "Square Medium (From Left)"],
            

            ["square-xs-right-center", 322, 322, ['right', 'center'], "Square Extra Small (From Right)"],
            ["square-sm-right-center", 642, 642, ['right', 'center'], "Square Small (From Right)"],
            ["square-md-right-center", 962, 962, ['right', 'center'], "Square Medium (From Right)"],

            ["thumb-xs", 320, 9999, false, "Thumb Extra Small"],
            ["thumb-sm", 640, 9999, false, "Thumb Small"],
            ["thumb-md", 960, 9999, false, "Thumb Medium"],
            ["thumb-lg", 1024, 9999, false, "Thumb Large"],
            ["thumb-xl", 1280, 9999, false, "Thumb XL"],
            ["thumb-xxl", 1600, 9999, false, "Thumb 2XL"],
            ["thumb-xxxl", 2400, 9999, false, "Thumb 3XL"],
            
            ["43-xs", 320, 240, true, "4:3 Extra Small"],
            ["43-sm", 640, 480, true, "4:3 Small"],
            ["43-md", 960, 720, true, "4:3 Medium"],
            ["43-lg", 1024, 768, true, "4:3 Large"],
            ["43-xl", 1280, 960, true, "4:3 XL"],
            
            ["169-xs", 320, 180, true, "16:9 Extra Small"],
            ["169-sm", 640, 360, true, "16:9 Small"],
            ["169-md", 960, 540, true, "16:9 Medium"],
            ["169-lg", 1024, 764, true, "16:9 Large"],
            ["169-xl", 1280, 720, true, "16:9 XL"],
            ["169-xxl", 1600, 900, true, "16:9 2XL"],
            ["169-xxxl", 2400, 1350, true, "16:9 3XL"],
            
            ["916-xs", 180, 320, true, "9:16 Extra Small"],
            ["916-sm", 360, 640, true, "9:16 Small"],
            ["916-md", 540, 960, true, "9:16 Medium"],
            ["916-xl", 576, 1024, true, "9:16 Large"],
            ["916-xl", 720, 1280, true, "9:16 XL"],
            ["916-xxl", 900, 1600, true, "9:16 2XL"],
            
            ["34-xs", 240, 320, true, "3:4 Extra Small"],
            ["34-sm", 480, 640, true, "3:4 Small"],
            ["34-md", 720, 960, true, "3:4 Medium"],
            ["34-lg", 768, 1024, true, "3:4 Large"],
            ["34-xl", 960, 1280, true, "3:4 XL"],
                        
            ["12-sm", 320, 640, true, "1:2 Small"],
            ["12-md", 480, 960, true, "1:2 Medium"],

            ["21-sm", 640, 320, true, "2:1 Small"],
            ["21-md", 960, 480, true, "2:1 Medium"],
        ];
    }

    public function register_image_sizes() {
        // Combine predefined and custom sizes
        $all_sizes = array_merge($this->predefined_image_sizes, $this->custom_image_sizes);

        foreach ($all_sizes as $size) {
            list($name, $width, $height, $crop) = $size;
            // Register if specifically selected or if it's a custom size.
            if (in_array($name, $this->selected_image_sizes) || $this->is_custom_size($name)) {
                add_image_size($name, $width, $height, $crop);
            }
        }
    }

    private function is_custom_size($name) {
        foreach ($this->custom_image_sizes as $size) {
            if ($size[0] === $name) {
                return true;
            }
        }
        return false;
    }

    public function add_sizes_to_media_library($sizes) {
        // Combine predefined and custom sizes
        $all_sizes = array_merge($this->predefined_image_sizes, $this->custom_image_sizes);

        foreach ($all_sizes as $size) {
            $name = $size[0];
            // Add to media library if specifically selected or if it's a custom size.
            if (in_array($name, $this->selected_image_sizes) || $this->is_custom_size($name)) {
                $sizes[$name] = $size[4]; // Descriptive name for media library
            }
        }

        return $sizes;
    }
}
