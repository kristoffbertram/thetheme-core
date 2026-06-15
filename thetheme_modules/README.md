# thetheme-core modules reference

The engine. Every file here is **project-agnostic** and synced verbatim across
deployments — never hand-edit a copy in a project; change it here and re-publish.
Per-project data is injected via filters (the carve-out contract).

Loaded by `bootstrap.php` (`thetheme_core_boot()`), in filename order, after WP
core is available.

| File | Purpose | Key API / hooks | Project conventions it relies on |
|---|---|---|---|
| `acf-loader.php` | Registers ACF blocks from `thetheme_blocks/` (child + parent), includes each block's `block.php`/`fields.php`, and registers no-op fallbacks for legacy block names. | `thetheme_block_dirs()`; hooks `init` (blocks), `acf/init` (fields), `acf/blocks/wrap_frontend_innerblocks`. **Carve-out filter:** `thetheme_acf_legacy_blocks` (default `[]`). | `thetheme_blocks/<name>/{block.json,block.php,fields.php}`. Legacy list supplied by `thetheme_app/acf-legacy.php`. |
| `subsites.php` | Multi-subsite engine: register subsites, resolve the current one (by section / page-template / condition), pick template-parts, enqueue per-subsite assets, and inject per-subsite editor CSS into the Gutenberg iframe. | `thetheme_register_subsites()`, `thetheme_get_registered_subsites()`, `thetheme_is_section()`, `thetheme_post_is_in_section()`, `thetheme_resolve_current_subsite_id()`, `thetheme_get_template_part()`, `thetheme_enqueue_subsite_assets()`, `thetheme_inject_subsite_editor_styles()`; hooks `wp_enqueue_scripts`, `block_editor_settings_all`. | Subsites registered by the project (call `thetheme_register_subsites()` from `thetheme_app/subsites.config.php`). Paths `thetheme_template-parts/<id>/`, `assets/css/www/{app,editor}.css`. |
| `editing.php` | Block-editor hardening + theme typography/colour setup: disables on-demand/separate core block assets, dequeues default block CSS, registers font-size scale, and derives the editor colour palette from the theme SCSS. | `after_setup_theme`, `wp_enqueue_scripts`; `add_theme_support` for post-thumbnails, font sizes, line-height, colour palette. | Reads colour vars from `thetheme_src/css/theme.scss` (`@theme { --color-*: #hex }`). |
| `defaults.php` | Baseline theme supports. | `add_theme_support('title-tag')`. | — |
| `images.php` | Responsive image helper. | `thetheme_image($id, $size, $class, $as_array)` — returns `<img>` markup with srcset/sizes. | — |
| `developing.php` | Dev/debug helpers. | `fgc($path)` (echo file contents), `dump($var, $title)` (styled `print_r`). | — |
| `wp-admin.php` | Admin-shell tweaks. | (currently empty — reserved). | — |
| `wp-login.php` | Stylised login screen. | `thetheme_login_css()`; hook `login_enqueue_scripts`. | Optional `assets/css/wp-login.css`. |

## Carve-out filters (project data lives in the app layer)

| Filter | Default | Supplied by |
|---|---|---|
| `thetheme_acf_legacy_blocks` | `[]` | `thetheme_app/acf-legacy.php` — returns/append legacy block names |

Subsite registrations are not a filter but a registration call:
`thetheme_register_subsites([...])` from `thetheme_app/subsites.config.php`.
