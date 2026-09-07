<?php
add_theme_support( 'title-tag' );

/**
 * WordPress defaults this package adjusts on every site.
 *
 * Consolidated from four themes on 2026-09-02. Each group has its own filter: they
 * are independent decisions, not one posture, and a site that wants HTML5 markup but
 * keeps emoji should not have to choose.
 *
 * NOT carried over, and worth recording so nobody re-adds them:
 *
 *   add_theme_support('automatic-feed-links')
 *       All four originals declared it and then removed feed_links from wp_head in
 *       their security file. The two cancelled out: the net effect was no feed links,
 *       reached by doing two contradictory things. Feed links are now one decision,
 *       in security.php (`thetheme_remove_feed_links`).
 *
 *   add_theme_support('post-formats', […])
 *       Puts a Format box in the editor for aside/image/video/quote/link. Nothing in
 *       the estate was shown to render a post format, and adding editor UI nobody
 *       uses is the opposite of the point. A site that wants formats declares them.
 */

/**
 * HTML5 markup for the bits of WordPress that still emit XHTML by default.
 */
if (apply_filters('thetheme_html5_markup', true)) {
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'script',
        'style',
    ]);
}

/**
 * Let embeds scale with their container.
 */
if (apply_filters('thetheme_responsive_embeds', true)) {
    add_theme_support('responsive-embeds');
}

/**
 * Excerpts on pages, and no trailing ellipsis on any excerpt.
 *
 * The ellipsis half is a content decision as much as a technical one — WordPress
 * appends " […]" and every theme in the estate stripped it — so it has its own
 * filter. Return the string you want appended, or leave it empty.
 */
if (apply_filters('thetheme_page_excerpts', true)) {
    add_post_type_support('page', 'excerpt');
}

if (!function_exists('thetheme_excerpt_more')) {
    function thetheme_excerpt_more($more) {
        return (string) apply_filters('thetheme_excerpt_more_text', '');
    }
}
add_filter('excerpt_more', 'thetheme_excerpt_more');

/**
 * Drop WordPress's emoji machinery.
 *
 * It costs a script, a stylesheet, a DNS prefetch and a content filter on every
 * request, to convert characters that every current browser and OS renders natively.
 */
if (!function_exists('thetheme_remove_emoji_support')) {

    function thetheme_disable_emojis_tinymce($plugins) {
        return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
    }

    function thetheme_disable_emojis_dns_prefetch($urls, $relation_type) {
        if ($relation_type === 'dns-prefetch') {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
            $urls = array_diff($urls, [$emoji_svg_url]);
        }
        return $urls;
    }

    function thetheme_remove_emoji_support() {

        if (!apply_filters('thetheme_remove_emoji_support', true)) {
            return;
        }

        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        add_filter('tiny_mce_plugins', 'thetheme_disable_emojis_tinymce');
        add_filter('wp_resource_hints', 'thetheme_disable_emojis_dns_prefetch', 10, 2);
    }
}
add_action('init', 'thetheme_remove_emoji_support');

/**
 * Drop the oEmbed host script — the one that lets OTHER sites embed this one.
 */
if (!function_exists('thetheme_remove_wp_embed')) {
    function thetheme_remove_wp_embed() {
        if (apply_filters('thetheme_remove_wp_embed', true)) {
            wp_deregister_script('wp-embed');
        }
    }
}
add_action('wp_footer', 'thetheme_remove_wp_embed');

/**
 * Stop WordPress injecting an extra div inside every group block.
 *
 * Core registers `wp_restore_group_inner_container()` on `render_block_core/group`
 * (`wp-includes/block-supports/layout.php` — find it by string, the line number
 * differs between core versions). When it runs, every non-flex, non-grid
 * `core/group` comes out of the renderer wrapped in an extra
 * `div.wp-block-group__inner-container`, and the `is-layout-*` classes are MOVED
 * off the group element onto that inner div.
 *
 * The function's first bail is `wp_theme_has_theme_json()`, so it does nothing on a
 * site that has a `theme.json`. A site running this package has none — that file is
 * deleted as part of the conversion — so the conversion switches this filter on by
 * construction, and a rendered document gains a hop that no theme in the estate was
 * written against. Nine consumers took it untreated; on one of them it left the
 * header unstyled, because 25 direct-child rules could no longer match.
 *
 * A bare `file_exists()` on `theme.json` silently changing rendered markup is exactly
 * the class of implicit core behaviour this package exists to take control of. Where
 * core decides output from the presence of a file rather than from a declaration, the
 * package states its position explicitly. So: the wrapper goes, and the layout classes
 * stay on the group element itself.
 *
 * `thetheme_restore_group_inner_container` is a CONVERSION RUNWAY, not a setting —
 * the same shape as `thetheme_reset_core_block_styles` (see README.md § *Switching
 * the reset off*). A theme whose stylesheet still reaches through
 * `> .wp-block-group__inner-container >` can return true while it rewrites those
 * selectors, and then delete the filter. Markup that depends on when a site was
 * converted is the drift this package exists to remove: a site sitting on `true` is
 * mid-revert, not configured.
 *
 * Register it no later than `after_setup_theme` priority 0 — the gate is read on
 * `init`, after the app layer loads.
 */
if (!function_exists('thetheme_remove_group_inner_container')) {
    function thetheme_remove_group_inner_container() {

        if (apply_filters('thetheme_restore_group_inner_container', false)) {
            return;
        }

        remove_filter('render_block_core/group', 'wp_restore_group_inner_container', 10);
    }
}
add_action('init', 'thetheme_remove_group_inner_container');
