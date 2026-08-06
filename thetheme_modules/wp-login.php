<?php
/**
 * Stylised Login & Theme Logo
 *
 * @since 1.0.0
 */

/**
 * Theme-relative path of the login stylesheet, inlined into the login <head>.
 *
 * Filterable because the build layout belongs to the theme, not to this package: a
 * theme whose pipeline builds to css/ points the filter at css/wp-login.css instead
 * of moving its build output. Return an empty string to emit nothing.
 *
 * Deliberately NOT derived from the resolved subsite: the section and template
 * branches of thetheme_resolve_current_subsite_id() both go through is_page(), and
 * the login screen has no queried page — only a project-supplied 'conditions'
 * callable could ever fire there, so it would resolve to null in practice.
 */
function thetheme_login_css_rel(): string {
    return ltrim((string) apply_filters('thetheme_login_stylesheet', 'assets/css/wp-login.css'), '/');
}

function thetheme_login_css() {

    $rel = thetheme_login_css_rel();

    if ($rel === '') return;

    $login_css = get_stylesheet_directory()."/".$rel;

    if (file_exists($login_css)):

        echo '<style type="text/css">';
        echo file_get_contents($login_css);
        echo '</style>';

    endif;

}
add_action( 'login_enqueue_scripts', 'thetheme_login_css' );
