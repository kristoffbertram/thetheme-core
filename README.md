# thetheme-core

The **engine** behind the shared `thetheme_*` WordPress scaffold — the small,
project-agnostic runtime that every project-level theme depends on and stays in
sync with. Distributed as a **public Composer package**; consumers pin a version.

This README is opinion-driven on purpose. It defines (1) what belongs in core and
(2) the scaffolding doctrine the best-use template and every descendant project
should follow. Adhering to it is the point.

## What core is (and only this)

Core is the generic module runtime — the files that could drop into *any* project
unchanged. From the reference theme (`the reference theme`) that is the
`thetheme_modules/` set:

```
thetheme_modules/
├── acf-loader.php     ACF block/field registration engine (+ legacy carve-out, see below)
├── defaults.php       Generic theme defaults / supports
├── developing.php     Dev-only helpers
├── editing.php        Block-editor tweaks (incl. iframe editor-style injection)
├── images.php         Generic image sizes / handling
├── subsites.php       Subsite resolver + editor-style injector (+ registration carve-out)
├── wp-admin.php       Admin shell tweaks
└── wp-login.php       Login screen tweaks
```

Plus a `bootstrap.php` that includes these (the package's entry point), called from
each theme's `functions.php`.

**Not core:** `thetheme_functions/` (app logic), `thetheme_components/`,
`thetheme_blocks/`, `thetheme_src/`, `thetheme_templates/`,
`thetheme_template-parts/`. These are the **app / presentation layer** — they live
in the project (and are demonstrated in `~/Development/thetheme`), never in the
engine.

## The carve-out contract (core stays byte-identical)

Core files must contain **zero project data**. Where core needs a per-project
list, the project injects it via a filter; core ships only the default:

| Core file | Project data removed | Injected via |
|---|---|---|
| `subsites.php` | `thetheme_get_registered_subsites()` body | `apply_filters('thetheme_registered_subsites', [])` — project returns its array (e.g. `thetheme_app/subsites.config.php`) |
| `acf-loader.php` | the `$legacy` block array | `apply_filters('thetheme_acf_legacy_blocks', $defaults)` — project adds e.g. `acf/carouselitem` (a consuming project) |

After this, every core file is identical across deployments and `composer update`
is a safe, mechanical sync.

## Scaffolding doctrine for the app/template layer

The project layer is organised **by purpose, not by type** — a reader should guess
where code lives from its responsibility alone.

```
thetheme_functions/        Behavioural code, purpose-split:
  app/                     App wiring (menus, sections, templates, breadcrumb, …)
  blocks/                  Block registration glue / shared block helpers
  post-types/              CPT + taxonomy registration
thetheme_app/              Project config the engine reads:
  subsites.config.php      returns the registered-subsites array
  acf-legacy.php           returns the project legacy-block list
thetheme_components/       Reusable partials — card.php link-neutralisation pattern
thetheme_blocks/<name>/    Self-contained block — four files (+ co-located
                           block.scss / block.js / view.php):
  block.json               Registration + ACF config. Its acf.renderTemplate key
                           names the render file, resolved relative to this folder.
  template.php             The render file. ACF includes it per render; this is what
                           outputs the block's markup.
  fields.php               ACF field group, included on acf/init.
  block.php                OPTIONAL. Included once on init at registration time, with
                           no block arguments and no output capture — it cannot render.
                           For registration-time code (a variation, a render_callback,
                           an asset registration). Most blocks have none.
thetheme_src/{js,scss,fonts,images}/   Front-end source; built to assets/ (Mix)
thetheme_template-parts/   Composable fragments
thetheme_templates/        Full page templates (selectable by slug)
```

### Principles

1. **Purpose over type.** New code goes to the directory naming its job.
2. **Core stays project-agnostic.** If a file names a specific site/client/block,
   it's app, not core. Litmus: would it drop unchanged into any project?
3. **Carve out extension points** (see contract above) so core files stay syncable.
4. **One responsibility per file.** The loader includes whole directories anyway.
5. **Self-contained blocks.** One block = one folder, copyable between projects.

## Core blocks arrive unstyled — the project supplies the CSS

`thetheme_modules/editing.php:5-13` disables both per-block asset strategies
(`should_load_block_assets_on_demand`, `should_load_separate_core_block_assets`)
and then dequeues **and deregisters** `wp-block-library`, plus dequeues
`classic-theme-styles` and `global-styles`. This is deliberate: the engine assumes
a Tailwind theme owns its own CSS and does not want core's stylesheet fighting it.
Deregistering (not merely dequeuing) `wp-block-library` is what makes it stick — a
dequeued handle stays registered and any later `wp_enqueue_style()` or dependency
resolution brings it straight back.

The consequence is the part to remember. **Any `core/*` block a project allows
renders with no core CSS whatsoever.** There is no partial fallback — the two
filters consolidate everything into the one handle that then gets deregistered.
And because the dequeue runs on `wp_enqueue_scripts` (front end only) while the
filters apply everywhere, a core block still looks right in the editor and breaks
on the front end.

### The allowlist is a project file, on purpose

Which core blocks are allowed is a **project** decision and deliberately not part
of this package: the filter lives in the consuming theme at
`thetheme_functions/app/wp-allowed-blocks.php`, filtering `allowed_block_types_all`.
It lists the permitted `core/*` blocks by hand, then merges in every block under
`thetheme_blocks/` automatically by reading each `block.json`'s `name` — so custom
blocks are never listed, only core ones. Every project allows a different set, which
is why the file is hand-copied per project and why it does not belong in the engine.
Do not move it here.

### `_wp.scss` is the answer

The styling half is a `thetheme_src/css/_wp.scss` partial in the consuming theme,
imported from the SCSS entry **after** `@import "tailwindcss"` so its rules outrank
preflight:

```scss
@import "tailwindcss";
@import "_wp";
```

On Tailwind v4 the partial must open with `@reference "tailwindcss";` or its
`@apply` rules will not resolve at build time.

It carries the structural CSS core would have shipped — in practice the `cover`
z-index/absolute-fill stack, `columns`/`column` flex behaviour (including `grow-0`
so editor-set inline `flex-basis` values don't also grow), `media-text`'s grid and
image object-fit, `navigation` list spacing, and `social-links`' `fill: currentColor`
plus the visually-hidden label. **It grows with the allowlist**: one rule per allowed
core block that needs one. A short `_wp.scss` — or none at all — is not drift; it
means that project allows few core blocks, or only ones that need no layout.

Token classes are the same story with a different home. `editing.php` derives the
editor palette from `@theme { --color-*: … }` in the theme's SCSS entry and registers
a font-size scale, so the editor writes `has-<slug>-color`,
`has-<slug>-background-color` and `has-<slug>-font-size` classes into content — but
`global-styles`, which would define them, is dequeued too. Define them in the SCSS
entry beside the `@theme` block that names them, not in `_wp.scss`: the partial is
for block *structure*, these are *tokens*. Watch the sanitised slug — a `2xl` font
size becomes `has-2-xl-font-size`, with the hyphen.

## Resilient loading (boot mu-plugin)

WordPress only auto-loads `functions.php` from the theme, and that file is editable —
so any loader placed there can be removed. To prevent that, the loaders live in a
must-use plugin instead:

- `mu-plugin/thetheme-boot.php` boots core on `setup_theme` (so core helpers are
  available in `functions.php`) and loads the active theme's app layer
  (`thetheme_functions/`, `thetheme_app/`) on `after_setup_theme` via
  `thetheme_load_app()`.
- `src/Installer.php` copies that stub into `wp-content/mu-plugins/` automatically
  during `composer install/update` — no manual placement.

Result: `functions.php` carries **no loader code**; editing or gutting it cannot
remove core or app loading.

### Consuming theme wiring

```jsonc
{
  "require": { "kristoffbertram/thetheme-core": "^1.1" },
  "repositories": [
    { "type": "vcs", "url": "<public-git-url>" }
  ],
  "scripts": {
    "post-install-cmd": "KristoffBertram\\ThethemeCore\\Installer::copyMuPlugin",
    "post-update-cmd":  "KristoffBertram\\ThethemeCore\\Installer::copyMuPlugin"
  }
}
```

`functions.php` then needs nothing more than to exist (WP requires the file); the
mu-plugin handles loading. Non-standard content dirs: set
`extra.thetheme-mu-plugins-dir`.

## Versioning & consumption

Tag releases as semver (`v1.0.0`, `v1.1.0`, …) so Composer's VCS resolver picks
them up — a `core-vX` prefix would not parse as a version. Themes pin a range
(`^1.1`) and record the resolved version in their (gitignored) `CLAUDE.md`
core-tracking block, alongside any intentional deviations.

## Status

- **v1.0.0** (2026-06-15) — engine extracted from `the reference theme` (8 modules + bootstrap).
- **v1.1.0** (2026-06-15) — resilient boot mu-plugin + composer installer; app loader
  moved into core (`thetheme_load_app()`).
- **v1.2.0** (2026-06-17) — upstreamed from the multi-subsite migration:
  - `developing.php`: `fgc()` hardened against path traversal (resolves inside theme dir only). **Security fix.**
  - `images.php`: `thetheme_image()` now emits `alt` and returns `image_alt`.
  - `subsites.php`: `thetheme_get_template_part()` gains an optional `$args` param.
  - **BREAKING (behaviour):** subsite script enqueues no longer declare `['jquery']`
    as a dependency (now `[]`). Deployments that rely on jQuery being auto-enqueued
    via the theme must enqueue it themselves. Verify per project on sync.
- **v1.3.0** (2026-06-17) — editor canvas styles now load as a **native `<link>`
  inside the iframe** via `enqueue_block_assets` + `is_admin()`, replacing the
  `block_editor_settings_all` injection. The old path routed CSS through Gutenberg's
  `transformStyles` scoper, which can't parse Tailwind v4 (`@property`, `@layer`,
  `color-mix()`, nesting) and silently dropped the whole sheet — so no editor styles
  applied. Subsite resolution preserved (`thetheme_resolve_editor_css_rel()`).

See `~/Development/ROADMAP.md` (thetheme section).
