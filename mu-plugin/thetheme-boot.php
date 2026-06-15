<?php
/**
 * Plugin Name: thetheme boot
 * Description: Boots thetheme-core and the active theme's app layer, independent of the theme's functions.php.
 *
 * MUST-USE PLUGIN. Source of truth lives in the thetheme-core package
 * (mu-plugin/thetheme-boot.php); it is copied into wp-content/mu-plugins/ by the
 * package's composer installer (KristoffBertram\ThethemeCore\Installer). Do not
 * edit the copy — edit it in thetheme-core and re-run composer install.
 *
 * Why this exists: WordPress only auto-loads functions.php from the theme, and that
 * file is editable. Putting the loaders here means editing/gutting functions.php
 * can't remove core or app loading.
 */

// Core helpers available before functions.php runs (get_stylesheet_directory() is
// resolvable at 'setup_theme' — it reads the 'stylesheet' option, not constants).
add_action('setup_theme', function () {
    $autoload = get_stylesheet_directory() . '/vendor/autoload.php';
    if (is_readable($autoload)) {
        require_once $autoload; // runs bootstrap.php → thetheme_core_boot()
    }
}, 0);

// App layer loads after the theme is set up (and after functions.php), so app files
// can rely on core being present.
add_action('after_setup_theme', function () {
    if (function_exists('thetheme_load_app')) {
        thetheme_load_app(get_stylesheet_directory());
    }
}, 0);
