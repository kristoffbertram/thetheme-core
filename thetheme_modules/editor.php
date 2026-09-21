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
    ['name' => '3xs', 'slug' => '3xs', 'size' => '0.5rem'],
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

/**
 * Line-height control.
 *
 * 'custom-line-height' is the name core reads — wp-includes/block-editor.php:815,
 * 'enableCustomLineHeight' => get_theme_support('custom-line-height') — and the only
 * one it reads. This line said 'editor-line-height' from the June 2026 extraction until
 * 2026-09-21; nothing in wp-includes or wp-admin has ever looked for that string, so the
 * control the module README claimed for the whole estate was on no consumer.
 *
 * It sits among the switches above without contradicting them: this is one numeric
 * control on the typography panel, not a free-form picker, and published content on the
 * estate already carries `line-height:N` values that the editor cannot show or edit
 * without it. The two supports that DO open pickers — 'custom-units' and
 * 'appearance-tools' — stay per site (see the align-wide docblock below).
 */
add_theme_support('custom-line-height');

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
/**
 * No custom gradient picker.
 *
 * Sits here beside disable-custom-colors and disable-custom-font-sizes because it is
 * the same decision: the theme owns its palette, and a free-form picker writes values
 * into content that the stylesheet cannot style. Three themes declared this
 * separately before it moved here (2026-09-02).
 */
add_theme_support('disable-custom-gradients');

/**
 * No default gradient presets.
 *
 * Declared EMPTY rather than as a theme palette, and the emptiness is the mechanism:
 * for a classic theme WP_Theme_JSON_Resolver::get_theme_data() sets
 * settings.color.defaultGradients to false as soon as 'editor-gradient-presets' is
 * present at all (the !wp_theme_has_theme_json() branch in
 * wp-includes/class-wp-theme-json-resolver.php). There is no separate "disable"
 * support to call, so declaring an empty set is how core's own defaults (twelve on
 * the WordPress the estate runs) go away.
 *
 * Empty rather than a set of our own for the same reason as the line above: the
 * stylesheet owns what a block may look like, and a preset offered in the picker is a
 * value written into content that the stylesheet then has to style. The empty set is
 * the package's position, not a claim that no site needs presets — content on the
 * estate does carry `has-*-gradient-background` classes. A theme with presets of its
 * own declares 'editor-gradient-presets' itself, with its set — theme code runs after
 * this module, and the later declaration replaces this one. Core's defaults stay off
 * either way, since the same branch only tests that the support is present.
 *
 * An empty array survives the round trip — get_classic_theme_supports_block_editor_settings()
 * tests `false !== $gradient_presets`, not truthiness — so this is not silently a no-op.
 */
add_theme_support('editor-gradient-presets', []);

/**
 * Spacing scale.
 *
 * Declaring it is what switches core's own scale off: same branch as the gradients
 * above sets settings.spacing.defaultSpacingSizes to false the moment
 * 'editor-spacing-sizes' is present. Again there is no separate "disable" support.
 *
 * The slugs are numeric and are WordPress's own on purpose. Published content stores
 * the reference, not the value — `var:preset|spacing|50` in a block's attributes — and
 * consuming stylesheets define `--wp--preset--spacing--50`. A prettier slug would
 * orphan both, silently, on content already written.
 *
 * The values are the estate's rather than an invention: they are the ramp that the two
 * themes which declare spacing at all arrived at independently, read at their widest
 * breakpoint. The narrower steps below 50 come from one of the two; the rest agree
 * exactly. A theme that wants a different scale declares 'editor-spacing-sizes' itself
 * — theme code runs after this module, and the later declaration replaces this one.
 *
 * These sizes are also only a fallback in practice. Core emits them as
 * --wp--preset--spacing--* through global-styles, which the reset above dequeues on the
 * front end, so on a finished site the theme's own stylesheet is what defines them.
 */
add_theme_support('editor-spacing-sizes', [
    ['name' => '2X-Small', 'slug' => '20', 'size' => '0.25rem'],
    ['name' => 'X-Small',  'slug' => '30', 'size' => '0.5rem'],
    ['name' => 'Small',    'slug' => '40', 'size' => '1rem'],
    ['name' => 'Medium',   'slug' => '50', 'size' => '2rem'],
    ['name' => 'Large',    'slug' => '60', 'size' => '4rem'],
    ['name' => 'X-Large',  'slug' => '70', 'size' => '6rem'],
    ['name' => '2X-Large', 'slug' => '80', 'size' => '8rem'],
    ['name' => '3X-Large', 'slug' => '90', 'size' => '12rem'],
]);

/**
 * Wide and full alignment.
 *
 * Core decides whether the editor offers Wide/Full from the presence of a file.
 * wp-admin/edit-form-blocks.php:279 sets 'supportsLayout' => wp_theme_has_theme_json(),
 * and with that false the only route left is wp-includes/block-editor.php:215,
 * 'alignWide' => get_theme_support('align-wide'). A site on this package has no
 * theme.json — the convention deletes it as part of the conversion — so without this
 * line no consumer's editor offers either position, while the estate's stylesheets
 * target .alignwide / .alignfull and published content already carries both.
 *
 * Sits beside the switches above without contradicting them: it opens no free-form
 * picker. Wide and Full are two named positions the stylesheet already styles, not a
 * value written into content that the stylesheet cannot. Same class of decision as the
 * group inner-container removal in defaults.php — where core decides behaviour from a
 * bare file_exists(), the package states its position explicitly. The two supports
 * that DO open pickers — 'custom-units' and 'appearance-tools' — are deliberately not
 * here; a site whose content needs them declares them in its own app layer (see
 * README.md § What a deleted theme.json hands back).
 */
add_theme_support('align-wide');

/**
 * No drop cap.
 *
 * The odd one out: WordPress gives a classic theme no add_theme_support() for this.
 * Core's own wp-includes/theme.json sets typography.dropCap true and nothing in the
 * theme-support bridge touches it, so the only non-theme.json route is to edit the
 * settings tree on its way to the editor.
 *
 * That route is real and was read, not assumed, on the WordPress the estate runs
 * (6.9.x–7.1): wp-includes/js/dist/block-library.js's DropCapControl returns null when
 * useSettings('typography.dropCap') is falsy, and useSettings resolves a path against
 * settings.__experimentalFeatures — falling back to true only when the path is
 * undefined. Setting it explicitly false is therefore what removes the control, and
 * this filter is the last thing to touch that array before it is handed to the editor.
 *
 * Note the deliberate absence of a filter of our own: per TODO.md D2 a module gets one
 * opt-out, and editor.php's is `thetheme_reset_core_block_styles`. A theme that wants
 * the drop cap back removes this callback.
 */
if (!function_exists('thetheme_disable_drop_cap')) {
    function thetheme_disable_drop_cap($settings) {
        if (!is_array($settings) || !isset($settings['__experimentalFeatures'])) {
            return $settings;
        }

        $settings['__experimentalFeatures']['typography']['dropCap'] = false;

        return $settings;
    }
}

add_filter('block_editor_settings_all', 'thetheme_disable_drop_cap');
