<?php
/**
 * Body classes — CSS hooks on the document, front end and admin.
 *
 * The name is the scope: everything here puts class names on <body> (and on .post)
 * so a stylesheet can target a page without knowing its ID. It is deliberately NOT
 * called rendering.php or output.php — those names invite anything vaguely
 * presentational and become junk drawers. This module does one thing.
 *
 * Consolidated from four themes on 2026-09-02, where it lived under four different
 * filenames (body-classes.php, admin.php, extra.php, app/extra.php) in two naming
 * families. Functions are prefixed and guarded per the package's promotion rule: the
 * unprefixed originals (add_slug_body_class, category_id_class,
 * the_add_template_name_to_admin_body_class) still exist at file scope in themes that
 * have not been ported, and core loads on `setup_theme`, before functions.php.
 *
 * ON BY DEFAULT, and that is safe in a way the image-size stripper was not: this only
 * ADDS class names. It removes nothing and overrides nothing, so a theme that has
 * never seen these classes gains dormant hooks its CSS does not reference. Switch the
 * whole module off with:
 *
 *     add_filter('thetheme_body_classes', '__return_false');
 */

if (!function_exists('thetheme_body_classes_enabled')) {
    function thetheme_body_classes_enabled(): bool {
        return (bool) apply_filters('thetheme_body_classes', true);
    }
}

/**
 * Slug-form classes: the post type, the post's own slug, and the slug of the
 * section it sits in (its top-most ancestor).
 *
 * On a top-level page the section IS the page, which is the one behavioural
 * correction made while consolidating. Every original read the root ancestor and,
 * finding none, fell through with an empty string — emitting a bare `parent-` class
 * and an empty class name on every top-level page. Those were meaningless, so no
 * stylesheet can have depended on them; `parent-<slug>` now resolves consistently
 * whether or not the page has a parent.
 */
if (!function_exists('thetheme_add_slug_body_classes')) {
    function thetheme_add_slug_body_classes($classes) {

        if (!thetheme_body_classes_enabled()) {
            return $classes;
        }

        $post = get_post();

        if (!$post instanceof WP_Post) {
            return $classes;
        }

        $classes[] = $post->post_type . '-' . $post->post_name;
        $classes[] = $post->post_name;

        $ancestors = get_post_ancestors($post->ID);
        $section   = $ancestors ? end($ancestors) : $post->ID;
        $slug      = get_post_field('post_name', $section);

        if ($slug) {
            $classes[] = $slug;
            $classes[] = 'parent-' . $slug;
        }

        return array_values(array_unique(array_filter($classes)));
    }
}
add_filter('body_class', 'thetheme_add_slug_body_classes');

/**
 * Category id classes, on both the document and each post element.
 */
if (!function_exists('thetheme_add_category_body_post_classes')) {
    function thetheme_add_category_body_post_classes($classes) {

        if (!thetheme_body_classes_enabled()) {
            return $classes;
        }

        $post = get_post();

        if (!$post instanceof WP_Post) {
            return $classes;
        }

        foreach ((array) get_the_category($post->ID) as $category) {
            if ($category instanceof WP_Term) {
                $classes[] = 'cat-' . $category->term_id . '-id';
            }
        }

        return $classes;
    }
}
add_filter('post_class', 'thetheme_add_category_body_post_classes');
add_filter('body_class', 'thetheme_add_category_body_post_classes');

/**
 * The assigned page template, as an admin <body> class.
 *
 * This is what lets editor CSS target a template the same way front-end CSS does.
 * Both spellings are emitted — with and without the `-php` suffix — because content
 * and stylesheets across the estate reference each, and dropping either would be a
 * silent miss rather than an error.
 */
if (!function_exists('thetheme_admin_body_template_class')) {
    function thetheme_admin_body_template_class($classes) {

        if (!thetheme_body_classes_enabled()) {
            return $classes;
        }

        $post = get_post();

        if (!$post instanceof WP_Post) {
            return $classes;
        }

        $template = get_page_template_slug($post->ID);

        if (!$template) {
            return $classes;
        }

        $base = str_replace('.', '-', basename($template, '.php'));

        $classes .= ' ' . sanitize_html_class('page-template-' . $base)
                  . ' ' . sanitize_html_class('page-template-' . $base . '-php');

        return $classes;
    }
}
add_filter('admin_body_class', 'thetheme_admin_body_template_class');
