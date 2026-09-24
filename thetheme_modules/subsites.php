<?php
$GLOBALS['thetheme_subsites'] = [];

function thetheme_register_subsites(array $subsites): void {
    $GLOBALS['thetheme_subsites'] = array_merge($GLOBALS['thetheme_subsites'], $subsites);
}

function thetheme_get_registered_subsites(): array {
    return $GLOBALS['thetheme_subsites'] ?? [];
}

/**
 * Internal: checks whether $post_id is the section root ($slug) OR a descendant.
 */
function thetheme_post_is_in_section(int $post_id, string $slug): bool {
    if (!$post_id) return false;

    if (get_post_field('post_name', $post_id) === $slug) return true;

    $anc = get_post_ancestors($post_id);
    if (empty($anc)) return false;

    foreach ($anc as $aid) {
        if (get_post_field('post_name', $aid) === $slug) {
            return true;
        }
    }

    return false;
}

/**
 * Front-end: are we in a section?
 */
function thetheme_is_section(string $slug): bool {
    if (!is_page()) return false;
    $p = get_queried_object();
    if (!$p || empty($p->ID)) return false;

    return thetheme_post_is_in_section((int) $p->ID, $slug);
}

/**
 * Resolve current subsite id once per request.
 * Supports any of:
 *  - 'section'          => 'slug'
 *  - 'template'         => 'thetheme_templates/foo.php'
 *  - 'templates'        => ['thetheme_templates/foo.php', ...] (back-compat)
 *  - 'conditions'       => [callable, ...]
 */
function thetheme_resolve_current_subsite_id(): ?string {
    static $cached = null;
    if ($cached !== null) return $cached;

    // No subsite in admin by default (editor handled separately)
    if (is_admin()) {
        return $cached = null;
    }

    $subsites = thetheme_get_registered_subsites();

    foreach ($subsites as $id => $subsite) {
        // A) Section-based
        if (!empty($subsite['section']) && thetheme_is_section($subsite['section'])) {
            return $cached = $id;
        }

        // B) Template-based (explicit page template chosen in editor)
        if (!empty($subsite['template'])) {
            $tpl = $subsite['template'];
            if (is_page_template($tpl) || is_page_template(basename($tpl))) {
                return $cached = $id;
            }
        }

        // B2) Back-compat: templates array
        if (!empty($subsite['templates']) && is_array($subsite['templates'])) {
            foreach ($subsite['templates'] as $tpl) {
                if (is_page_template($tpl) || is_page_template(basename($tpl))) {
                    return $cached = $id;
                }
            }
        }

        // C) Condition-based
        if (!empty($subsite['conditions']) && is_array($subsite['conditions'])) {
            foreach ($subsite['conditions'] as $fn) {
                if (is_callable($fn) && call_user_func($fn) === true) {
                    return $cached = $id;
                }
            }
        }
    }

    return $cached = null;
}

function thetheme_get_template_part(string $type = 'header' , $args = []): string {
    $subsite_id = thetheme_resolve_current_subsite_id();
    if ($subsite_id) {
        $subsites = thetheme_get_registered_subsites();
        $subsite  = $subsites[$subsite_id] ?? [];
        return $subsite[$type] ?? "thetheme_template-parts/{$subsite_id}/{$type}";
    }
    return "thetheme_template-parts/www/{$type}";
}

function thetheme_enqueue_subsite_assets(): void {
    $ver        = wp_get_theme()->get('Version');
    $subsite_id = thetheme_resolve_current_subsite_id();
    $subsites   = thetheme_get_registered_subsites();

    if ($subsite_id && !empty($subsites[$subsite_id])) {
        $subsite = $subsites[$subsite_id];

        if (!empty($subsite['style'])) {
            $style_rel  = ltrim($subsite['style'], '/');
            $style_path = get_stylesheet_directory() . '/' . $style_rel;

            wp_dequeue_style('thetheme'); // Optional
            wp_enqueue_style(
                "subsite-style-{$subsite_id}",
                get_stylesheet_directory_uri() . '/' . $style_rel,
                [],
                file_exists($style_path) ? filemtime($style_path) : $ver
            );
        }

        if (!empty($subsite['script'])) {
            $script_rel  = ltrim($subsite['script'], '/');
            $script_path = get_stylesheet_directory() . '/' . $script_rel;

            wp_dequeue_script('thetheme'); // Optional
            wp_enqueue_script(
                "subsite-script-{$subsite_id}",
                get_stylesheet_directory_uri() . '/' . $script_rel,
                [],
                file_exists($script_path) ? filemtime($script_path) : $ver,
                true
            );
        }

        return;
    }

    // Fallback: www. Only reached when no subsite resolved (the branch above returns
    // for any resolved entry, declared assets or not). Filterable so a theme built to
    // another layout can point at it without moving its build output.
    $style_rel   = ltrim((string) apply_filters('thetheme_default_stylesheet', 'assets/css/www/app.css'), '/');
    $script_rel  = ltrim((string) apply_filters('thetheme_default_script', 'assets/js/www/app.js'), '/');
    $style_path  = get_stylesheet_directory() . '/' . $style_rel;
    $script_path = get_stylesheet_directory() . '/' . $script_rel;

    wp_enqueue_style(
        'thetheme',
        get_stylesheet_directory_uri() . '/' . $style_rel,
        [],
        file_exists($style_path) ? filemtime($style_path) : $ver
    );
    wp_enqueue_script(
        'thetheme',
        get_stylesheet_directory_uri() . '/' . $script_rel,
        [],
        file_exists($script_path) ? filemtime($script_path) : $ver,
        true
    );
}
add_action('wp_enqueue_scripts', 'thetheme_enqueue_subsite_assets', 20);

/**
 * Resolve the editor.css (relative) for the post being edited, using the subsite's
 * section / template / admin_conditions. Falls back to the www default.
 *
 * All three matches need a post id, so on a brand-new post there is nothing to match
 * against — hence the single-subsite short-circuit below.
 */
function thetheme_resolve_editor_css_rel(): string {
    $subsites = thetheme_get_registered_subsites();
    $resolved = null;

    $post_id = isset($_GET['post'])
        ? (int) $_GET['post']
        : (function_exists('get_the_ID') ? (int) get_the_ID() : 0);

    if ($post_id) {
        // 1) Section-based detection in admin
        foreach ($subsites as $id => $subsite) {
            if (!empty($subsite['section']) && thetheme_post_is_in_section($post_id, $subsite['section'])) {
                $resolved = $id;
                break;
            }
        }

        // 2) Template-based detection in admin (explicit editor template)
        if (!$resolved) {
            $tpl = get_page_template_slug($post_id);
            if ($tpl) {
                foreach ($subsites as $id => $subsite) {
                    if (!empty($subsite['template']) && $tpl === $subsite['template']) {
                        $resolved = $id;
                        break;
                    }
                    if (!empty($subsite['templates']) && is_array($subsite['templates']) && in_array($tpl, $subsite['templates'], true)) {
                        $resolved = $id;
                        break;
                    }
                }
            }
        }

        // 3) Admin-only conditions
        if (!$resolved) {
            foreach ($subsites as $id => $subsite) {
                if (!empty($subsite['admin_conditions']) && is_array($subsite['admin_conditions'])) {
                    foreach ($subsite['admin_conditions'] as $fn) {
                        if (is_callable($fn) && call_user_func($fn, $post_id) === true) {
                            $resolved = $id;
                            break 2;
                        }
                    }
                }
            }
        }
    }

    // 4) Exactly one registered subsite: there is nothing to disambiguate, so it is the
    // answer whether or not a post id exists. Without this, a NEW post — no id, so none
    // of the three matches above can run — falls through to the default and the site's
    // only editor stylesheet never loads.
    if (!$resolved && count($subsites) === 1) {
        $resolved = array_key_first($subsites);
    }

    if ($resolved && !empty($subsites[$resolved]['editor'])) {
        return ltrim($subsites[$resolved]['editor'], '/');
    }

    // Same fallback rule as the front end: filterable, default unchanged. $resolved is
    // passed through because this branch is also taken by a subsite that resolved but
    // declares no 'editor' entry.
    return ltrim((string) apply_filters('thetheme_default_editor_stylesheet', 'assets/css/www/editor.css', $resolved), '/');
}

/**
 * Editor canvas styles: load the resolved editor.css as a native <link> INSIDE the
 * block editor iframe via `enqueue_block_assets` (fires in the iframe since WP 6.3)
 * gated by `is_admin()`.
 *
 * NOT `block_editor_settings_all`: that routes CSS through Gutenberg's in-browser
 * `transformStyles` scoper, which can't parse Tailwind v4 (`@property`, `@layer`,
 * `color-mix()`, nesting) and silently drops the whole sheet — so nothing applies.
 * A native <link> is parsed by the browser, exactly like the front end.
 */
function thetheme_enqueue_subsite_editor_styles(): void {
    if (!is_admin()) {
        return; // editor context only (incl. the iframe); never the front end
    }

    $rel  = thetheme_resolve_editor_css_rel();
    $path = get_stylesheet_directory() . '/' . $rel;
    if (!file_exists($path)) {
        // Nothing to enqueue means the canvas gets no theme CSS at all. Say so under
        // WP_DEBUG rather than leaving it a silent no-op — the symptom (an unstyled
        // editor) reads as a CSS problem, not a missing file.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[thetheme] Editor stylesheet not found, canvas left unstyled: {$rel}");
        }
        return;
    }

    wp_enqueue_style(
        'thetheme-editor',
        get_stylesheet_directory_uri() . '/' . $rel,
        [],
        filemtime($path)
    );
}
add_action('enqueue_block_assets', 'thetheme_enqueue_subsite_editor_styles');