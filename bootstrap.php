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
     *
     * ORDER GUARANTEE: files load in ascending sorted order of their path
     * relative to thetheme_functions/ (byte-wise string comparison, so
     * subdirectories sort among the files beside them — app/menus.php before
     * layout/x.php before shortcodes/y.php). This is a contract a theme may rely
     * on: it is what makes a file-scope call to a function defined in a sibling
     * file work as long as the definition sorts first. The walk was unsorted
     * until 2026-09-04 and yielded raw filesystem order, which white-screened
     * five consumers whose app/theme-setup.php ran before app/sitepage.php.
     * Relying on the order is still the fragile half — prefer hook registrations
     * over file-scope work in the app layer — but the order is now defined.
     */
    function thetheme_load_app(string $theme_dir): void {
        $dir = rtrim($theme_dir, '/') . '/thetheme_functions/';
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $files = [];
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        // The iterator yields filesystem order, which differs per machine and per
        // deploy. Sort so the load order is the same everywhere.
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            require_once $file;
        }
    }
}

// Boot only inside WordPress — not during composer script runs (e.g. the installer),
// where WP functions like add_action() don't exist.
if (defined('ABSPATH')) {
    thetheme_core_boot();
}
