# thetheme-core modules reference

The engine. Every file here is **project-agnostic** and synced verbatim across
deployments — never hand-edit a copy in a project; change it here and re-publish.
Per-project data is injected via filters (the carve-out contract).

Loaded by `bootstrap.php` (`thetheme_core_boot()`), in filename order, after WP
core is available.

| File | Purpose | Key API / hooks | Project conventions it relies on |
|---|---|---|---|
| `acf-loader.php` | Registers ACF blocks from `thetheme_blocks/` (child + parent) by folder, so ACF reads each `block.json` and renders the file its `acf.renderTemplate` names. Also includes each block's `fields.php` on `acf/init` and its optional `block.php` once at registration time, and registers no-op fallbacks for legacy block names. | `thetheme_block_dirs()`; hooks `init` (blocks), `acf/init` (fields), `acf/blocks/wrap_frontend_innerblocks`. **Carve-out filters:** `thetheme_acf_legacy_blocks` (default `[]`); `thetheme_log_registered_blocks` (default `false`) — opt in to log every registered block name on an admin request. | `thetheme_blocks/<name>/{block.json,template.php,fields.php}`, plus an **optional** `block.php` — a registration-time include, not a render file (see `README.md` § *Scaffolding doctrine*). Legacy list supplied by `thetheme_app/acf-legacy.php`. |
| `subsites.php` | Multi-subsite engine: register subsites, resolve the current one (by section / page-template / condition), pick template-parts, enqueue per-subsite assets, and enqueue the per-subsite editor stylesheet into the block-editor canvas as a native `<link>`. | `thetheme_register_subsites()`, `thetheme_get_registered_subsites()`, `thetheme_is_section()`, `thetheme_post_is_in_section()`, `thetheme_resolve_current_subsite_id()`, `thetheme_get_template_part()`, `thetheme_enqueue_subsite_assets()`, `thetheme_resolve_editor_css_rel()`, `thetheme_enqueue_subsite_editor_styles()`; hooks `wp_enqueue_scripts`, `enqueue_block_assets`. | Subsites registered by the project (call `thetheme_register_subsites()` from `thetheme_app/subsites.config.php`). Paths `thetheme_template-parts/<id>/`, `assets/css/www/{app,editor}.css`. The editor entry must exist per declaring subsite — a missing file is an early `return`, i.e. **no theme CSS in the canvas at all**. |
| `editing.php` | Block-editor hardening + theme typography/colour setup: disables on-demand/separate core block assets, dequeues default block CSS, registers font-size scale, and derives the editor colour palette from the theme SCSS. Carries **no** editor-style injection — that is `subsites.php`. | `thetheme_register_core_block_style_reset()` (on `after_setup_theme` 1), `thetheme_dequeue_core_block_styles()` (on `wp_enqueue_scripts` 100); `add_theme_support` for post-thumbnails, font sizes, line-height, colour palette. **Opt-out filter:** `thetheme_reset_core_block_styles` (default `true`) — false unhooks the whole reset. | Reads colour vars from `thetheme_src/css/theme.scss` (`@theme { --color-*: #hex }`) — a **runtime** read of the SCSS source, so that file must exist on the server. Note the dequeue is `wp_enqueue_scripts`, front end only: WP's block CSS still reaches the editor. |
| `defaults.php` | Baseline theme supports. | `add_theme_support('title-tag')`. | — |
| `images.php` | Responsive image helper. | `thetheme_image($id, $size, $class, $as_array)` — returns `<img>` markup with srcset/sizes. | — |
| `developing.php` | Dev/debug helpers. | `fgc($path)` (echo file contents), `dump($var, $title)` (styled `print_r`). | — |
| `wp-admin.php` | Admin-shell tweaks. | (currently empty — reserved). | — |
| `wp-login.php` | Stylised login screen. | `thetheme_login_css()`; hook `login_enqueue_scripts`. | Optional `assets/css/wp-login.css`. |

## Carve-out filters (project data lives in the app layer)

| Filter | Default | Supplied by |
|---|---|---|
| `thetheme_acf_legacy_blocks` | `[]` | `thetheme_app/acf-legacy.php` — returns/append legacy block names |
| `thetheme_reset_core_block_styles` | `true` | the app layer — return false to keep WordPress's own block CSS while a conversion writes its parity CSS. Must be set by `after_setup_theme` priority 0; see `README.md` § *Switching the reset off* |

Subsite registrations are not a filter but a registration call:
`thetheme_register_subsites([...])` from `thetheme_app/subsites.config.php`.
