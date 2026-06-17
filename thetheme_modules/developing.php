<?php
if ( ! function_exists( 'fgc' ) ) {

    function fgc($fgc , $echo = true)
    {
        // Path-traversal guard: only read files that resolve inside the theme dir.
        $theme_dir = realpath(get_stylesheet_directory());
        $resolved  = realpath($theme_dir . $fgc);

        if (!$theme_dir || !$resolved) return;
        if (strpos($resolved, $theme_dir . DIRECTORY_SEPARATOR) !== 0) return;

        echo file_get_contents($resolved);
    }

}

if (!function_exists('dump')) {

    function dump($var , $title = null) {
        
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

        if ($title) {
            echo '<span style="font-size: 2rem; font-weight: bold; margin-bottom: 2rem"><strong>' . htmlspecialchars($title) . '</strong></span><br />>';
        }
        
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