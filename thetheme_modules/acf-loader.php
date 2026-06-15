<?php
/**
 * Register blocks from /thetheme_blocks in both CHILD and PARENT themes.
 * - Registers block.json folders
 * - Includes optional block.php
 * - Includes fields.php on acf/init
 * - Logs missing/failed registrations
 * - Adds a safety fallback for legacy ACF block names so the editor stops complaining
 */

function thetheme_block_dirs(): array {

    $dirs = [];

    $child = trailingslashit( get_stylesheet_directory() ) . 'thetheme_blocks';
    $parent = trailingslashit( get_template_directory() ) . 'thetheme_blocks';

    if ( is_dir( $child ) )  $dirs[] = $child;
    if ( is_dir( $parent ) && $parent !== $child ) $dirs[] = $parent;

    if ( empty( $dirs ) ) {
        error_log('[thetheme] No thetheme_blocks directory found in child or parent theme.');
    }
    return $dirs;
}

add_action('init', function () {

    if ( ! function_exists('register_block_type') ) return;

    $registry = WP_Block_Type_Registry::get_instance();

    foreach ( thetheme_block_dirs() as $blocks_dir ) {
        try {
            foreach ( new DirectoryIterator( $blocks_dir ) as $fileinfo ) {
                if ( $fileinfo->isDot() || ! $fileinfo->isDir() ) continue;

                $block_folder = $fileinfo->getPathname();
                $block_json   = $block_folder . '/block.json';
                $block_php    = $block_folder . '/block.php';

                if ( file_exists( $block_json ) ) {
                    // Register via folder (auto-reads block.json)
                    register_block_type( $block_folder );
                } else {
                    error_log("[thetheme] Skipped (no block.json): {$block_folder}");
                }

                if ( file_exists( $block_php ) ) {
                    include_once $block_php;
                }
            }
        } catch ( Throwable $t ) {
            error_log('[thetheme] blocks register error: ' . $t->getMessage());
        }
    }

    // --- Safety net: if legacy names are referenced in content but not registered, add minimal fallbacks.
    // The list is project content data, so core ships none. Each project supplies its own
    // legacy block names via the 'thetheme_acf_legacy_blocks' filter (thetheme_app/acf-legacy.php):
    // e.g. the reference theme ['acf/styled-button','acf/assettable']; a consuming project adds 'acf/carouselitem'.
    $legacy = apply_filters('thetheme_acf_legacy_blocks', []);
    foreach ( $legacy as $name ) {
        if ( ! $registry->is_registered( $name ) ) {
            register_block_type( $name, [ 'render_callback' => '__return_empty_string' ] );
            error_log("[thetheme] Fallback registered for missing block: {$name}");
        }
    }
}, 5); // run early so the editor sees blocks

// Load fields.php for any block that has it (child + parent)
add_action('acf/init', function () {
    foreach ( thetheme_block_dirs() as $blocks_dir ) {
        try {
            foreach ( new DirectoryIterator( $blocks_dir ) as $fileinfo ) {
                if ( $fileinfo->isDot() || ! $fileinfo->isDir() ) continue;

                $fields_path = $fileinfo->getPathname() . '/fields.php';
                if ( file_exists( $fields_path ) ) {
                    include $fields_path;
                }
            }
        } catch ( Throwable $t ) {
            error_log('[thetheme] fields include error: ' . $t->getMessage());
        }
    }
});

// ACF innerBlocks wrapper: keep your behavior
add_filter('acf/blocks/wrap_frontend_innerblocks', function ($wrap, $name) {
    return false;
}, 10, 2);

// Debug helper: list registered blocks in logs (optional; comment out when done)
add_action('admin_init', function () {
    if ( ! current_user_can('manage_options') ) return;
    $names = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
    error_log('[thetheme] Registered blocks: ' . implode(', ', $names));
});