<?php
/**
 * WP Block Resetting
 *
 * On by default: the engine assumes the theme owns its core-block CSS. A theme that
 * does not yet — a conversion in progress — switches the whole reset off with
 * add_filter('thetheme_reset_core_block_styles', '__return_false'), registered no
 * later than 'after_setup_theme' priority 0. Nothing is hooked when it returns false.
 */
function thetheme_dequeue_core_block_styles(): void {
    wp_dequeue_style('wp-block-library');
    wp_deregister_style('wp-block-library');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('global-styles');
}

function thetheme_register_core_block_style_reset(): void {
    if (!apply_filters('thetheme_reset_core_block_styles', true)) {
        return;
    }

    add_filter('should_load_block_assets_on_demand', '__return_false', 1);
    add_filter('should_load_separate_core_block_assets', '__return_false', 1);
    add_action('wp_enqueue_scripts', 'thetheme_dequeue_core_block_styles', 100);
}

// Late enough that the app layer ('after_setup_theme' 0) can have set the filter,
// early enough that nothing has applied the two should_load_* filters yet (init).
add_action('after_setup_theme', 'thetheme_register_core_block_style_reset', 1);

/**
 * Images
 */
add_theme_support('post-thumbnails');

/**
 * Typography
 */
add_theme_support('disable-custom-font-sizes');

add_theme_support('editor-font-sizes', [
    ['name' => '2xs', 'slug' => '2xs', 'size' => '0.625rem'],
    ['name' => 'xs',  'slug' => 'xs',  'size' => '0.75rem'],
    ['name' => 'sm',  'slug' => 'sm',  'size' => '0.875rem'],
    ['name' => 'base','slug' => 'base','size' => '1rem'],
    ['name' => 'lg',  'slug' => 'lg',  'size' => '1.125rem'],
    ['name' => 'xl',  'slug' => 'xl',  'size' => '1.25rem'],
    ['name' => '2xl', 'slug' => '2xl', 'size' => '1.5rem'],
    ['name' => '3xl', 'slug' => '3xl', 'size' => '1.875rem'],
    ['name' => '4xl', 'slug' => '4xl', 'size' => '2.25rem'],
    ['name' => '5xl', 'slug' => '5xl', 'size' => '3rem'],
    ['name' => '6xl', 'slug' => '6xl', 'size' => '3.75rem'],
    ['name' => '7xl', 'slug' => '7xl', 'size' => '4.5rem'],
    ['name' => '8xl', 'slug' => '8xl', 'size' => '6rem'],
    ['name' => '9xl', 'slug' => '9xl', 'size' => '8rem'],
]);

add_theme_support('editor-line-height');

/**
 * Colours
 */
add_action('after_setup_theme', function () {
    $path = get_stylesheet_directory() . '/thetheme_src/css/theme.scss';

    if (!file_exists($path)) {
        return;
    }

    $scss = file_get_contents($path);

    // Strip comments before matching. The natural way to document this mechanism is to
    // write the at-rule name followed by an opening brace, and an unstripped docblock
    // that does so is matched as the block itself — yielding an empty (or, if the
    // comment carries an example, a wrong) palette with no error.
    $scss = preg_replace('#/\*.*?\*/#s', '', $scss);      // /* ... */
    $scss = preg_replace('#(^|\s)//.*$#m', '$1', $scss);   // // ...  (leaves https:// alone)

    // Extract the @theme block. The LAST one wins, mirroring the CSS cascade: a stale
    // or example block earlier in the file must not shadow the real one below it.
    if (!preg_match_all('/@theme\s*{([^}]+)}/s', $scss, $match)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("@theme block not found in $path");
        }
        return;
    }

    $theme_block = end($match[1]);

    // Match only color vars with hex values
    preg_match_all('/--color-([\w\-]+):\s*(#[0-9a-fA-F]{3,8})\s*;/', $theme_block, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("No color variables found in the @theme block in $path");
        }
        return;
    }

    $colors = array_map(function ($m) {
        $slug = trim($m[1]);
        $name = ucwords(str_replace('-', ' ', $slug));
        return [
            'name'  => $name,
            'slug'  => $slug,
            'color' => $m[2],
        ];
    }, $matches);

    add_theme_support('editor-color-palette', $colors);
    add_theme_support('disable-custom-colors');
});