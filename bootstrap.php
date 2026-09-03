<?php
/**
 * thetheme-core bootstrap.
 *
 * Entry point for the engine. Composer auto-includes this file (see composer.json
 * "autoload.files"), so a consuming theme only needs:
 *
 *     require_once __DIR__ . '/vendor/autoload.php';
 *
 * in its functions.php, after which all core modules are loaded. The theme then
 * loads its own app layer (thetheme_functions/) separately.
 *
 * Loads every PHP file in thetheme_modules/ in filename order. Modules are
 * project-agnostic; per-project data is injected via filters (see the carve-out
 * contract in README.md).
 */

if (!function_exists('thetheme_core_boot')) {
    function thetheme_core_boot(): void {
        $modules = __DIR__ . '/thetheme_modules';
        if (!is_dir($modules)) {
            return;
        }
        foreach (glob($modules . '/*.php') as $file) {
            require_once $file;
        }
    }
}

if (!function_exists('thetheme_load_app')) {
    /**
     * Load a theme's app layer (thetheme_functions/) recursively.
     * Called by the boot mu-plugin so loader code never lives in functions.php
     * (where it could be edited out). See mu-plugin/thetheme-boot.php.
     *
     * There is ONE app-layer directory: thetheme_functions/. A top-level
     * thetheme_app/ was also walked here until 2026-09-02, as a migration
     * back-compat for themes that had not yet moved their files. No theme uses
     * that directory any more, so the second pass is gone. A theme that still has
     * one must move its contents to thetheme_functions/app/ — they will not load.
     */
    function thetheme_load_app(string $theme_dir): void {
        $dir = rtrim($theme_dir, '/') . '/thetheme_functions/';
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getPathname();
            }
        }
    }
}

// Boot only inside WordPress — not during composer script runs (e.g. the installer),
// where WP functions like add_action() don't exist.
if (defined('ABSPATH')) {
    thetheme_core_boot();
}
