<?php
/**
 * Stylised Login & Theme Logo
 * 
 * @since 1.0.0
 */

function thetheme_login_css() {

    $login_css = get_stylesheet_directory()."/assets/css/wp-login.css";

    if (file_exists($login_css)):

        echo '<style type="text/css">';
        echo file_get_contents($login_css);
        echo '</style>';

    endif;

}
add_action( 'login_enqueue_scripts', 'thetheme_login_css' );
