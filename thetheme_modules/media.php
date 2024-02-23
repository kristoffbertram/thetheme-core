<?php
/**
 * The Theme Media
 * 
 * Additional media library support
 * 
 * @package thetheme
 * @version 1.0.0
 *
 */

/**
 * Allows custom mimetype uploads
 * 
 * @since 1.0.0
 * 
 */

 class The_Theme_Allow_Custom_Mimes {
    
    private $mimes;

    /**
     * Constructor to add the filter hook.
     *
     * @param array $mimes MIME types to add or override.
     */
    public function __construct($mimes = []) {
        $this->mimes = $mimes;
        add_filter('upload_mimes', [$this, 'modify_upload_mimes']);
    }

    /**
     * Modify the list of allowed upload MIME types.
     *
     * @param array $existing_mimes Current list of MIME types.
     * @return array Modified list of MIME types.
     */
    public function modify_upload_mimes($existing_mimes) {
        return array_merge($existing_mimes, $this->mimes);
    }
}
