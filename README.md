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
thetheme_blocks/<name>/    Self-contained block: block.json, template.php, fields.php
                           (+ co-located block.scss / block.js / view.php)
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

See `~/Development/ROADMAP.md` (thetheme section).
