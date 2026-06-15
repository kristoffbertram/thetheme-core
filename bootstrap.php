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
 * loads its own app layer (thetheme_functions/, thetheme_app/) separately.
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

thetheme_core_boot();
