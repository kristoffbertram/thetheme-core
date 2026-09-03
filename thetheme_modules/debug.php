<?php
/**
 * Debug output. Not called by anything that ships.
 *
 * Split out of developing.php on 2026-09-02, away from fgc(), which templates depend
 * on. Nothing in the estate calls dump() — that is the point of it: it exists to be
 * reached for while working, and to be obvious in a diff if it is ever left behind.
 */

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
