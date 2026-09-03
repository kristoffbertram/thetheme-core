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
