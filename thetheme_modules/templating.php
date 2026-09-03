<?php
/**
 * Template helpers — used by markup, not by developers.
 *
 * Split out of developing.php on 2026-09-02. fgc() has 14 call sites in live markup
 * across the estate — subsite headers, block templates, the social-icons component,
 * the [the_map] shortcode — so filing it under "developing" was actively misleading.
 * It inlines a file from the theme directory, which is how inline SVG gets onto the
 * page without an HTTP request.
 */

if ( ! function_exists( 'fgc' ) ) {

    function fgc($fgc , $echo = true)
    {
        // Path-traversal guard: only read files that resolve inside the theme dir.
        $theme_dir = realpath(get_stylesheet_directory());
        $resolved  = realpath($theme_dir . $fgc);

        if (!$theme_dir || !$resolved) return;
        if (strpos($resolved, $theme_dir . DIRECTORY_SEPARATOR) !== 0) return;

        $contents = file_get_contents($resolved);

        if (!$echo) {
            return $contents;
        }

        echo $contents;
    }

}