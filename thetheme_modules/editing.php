<?php
/**
 * WP Block Resetting
 */
add_filter('should_load_block_assets_on_demand', '__return_false', 1);
add_filter('should_load_separate_core_block_assets', '__return_false', 1);

add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_deregister_style('wp-block-library');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('global-styles');
}, 100);

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

    // Extract the @theme block
    if (!preg_match('/@theme\s*{([^}]+)}/s', $scss, $match)) {
        // error_log("@theme block not found in $path");
        return;
    }

    $theme_block = $match[1];

    // Match only color vars with hex values
    preg_match_all('/--color-([\w\-]+):\s*(#[0-9a-fA-F]{3,8})\s*;/', $theme_block, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        // error_log("No color variables found in @theme block");
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