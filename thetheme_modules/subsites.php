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

function thetheme_get_template_part(string $type = 'header'): string {
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
                ['jquery'],
                file_exists($script_path) ? filemtime($script_path) : $ver,
                true
            );
        }

        return;
    }

    // Fallback: www
    wp_enqueue_style('thetheme', get_stylesheet_directory_uri() . '/assets/css/www/app.css', [], $ver);
    wp_enqueue_script('thetheme', get_stylesheet_directory_uri() . '/assets/js/www/app.js', ['jquery'], $ver, true);
}
add_action('wp_enqueue_scripts', 'thetheme_enqueue_subsite_assets', 20);

/**
 * Editor styles: resolve subsite in admin using section, template and/or admin_conditions.
 *
 * Injects the resolved stylesheet into the block editor iframe via
 * `block_editor_settings_all`. `enqueue_block_editor_assets` would only reach
 * the admin shell — the Gutenberg post canvas is iframed (WP 6.3+) and only
 * picks up entries in $settings['styles'].
 */
function thetheme_inject_subsite_editor_styles(array $settings, $context): array {
    $subsites = thetheme_get_registered_subsites();
    $resolved = null;

    $post_id = 0;
    if (is_object($context) && isset($context->post) && $context->post instanceof WP_Post) {
        $post_id = (int) $context->post->ID;
    }

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

    $rel = ($resolved && !empty($subsites[$resolved]['editor']))
        ? ltrim($subsites[$resolved]['editor'], '/')
        : 'assets/css/www/editor.css';

    $css_path = get_stylesheet_directory() . '/' . $rel;
    if (!file_exists($css_path)) {
        return $settings;
    }

    $css = file_get_contents($css_path);
    if ($css === false || $css === '') {
        return $settings;
    }

    if (!isset($settings['styles']) || !is_array($settings['styles'])) {
        $settings['styles'] = [];
    }

    $settings['styles'][] = [
        'css'     => $css,
        'baseURL' => get_stylesheet_directory_uri() . '/' . dirname($rel) . '/',
    ];

    return $settings;
}
add_filter('block_editor_settings_all', 'thetheme_inject_subsite_editor_styles', 10, 2);