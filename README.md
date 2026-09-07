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
├── body-classes.php   Slug/section/category classes on <body>, and the admin template class
├── debug.php          dump() — debug output, ships unused
├── defaults.php       WordPress defaults the package adjusts (html5, emoji, excerpts, embeds)
├── editor.php         Block-editor tweaks: core block CSS, font sizes, colour palette
├── images.php         thetheme_image() + the opt-in default-size stripper
├── security.php       Head cleanup, xmlrpc, user-enumeration blocking, response headers
├── subsites.php       Subsite resolver + editor-canvas stylesheet enqueue
├── templating.php     fgc() — inline a theme file (used by live markup)
└── wp-login.php       Login screen tweaks
```

Plus a `bootstrap.php` — the package's entry point, auto-included by Composer
(`autoload.files`). It is **not** called from the theme's `functions.php`: since v1.1.0
the boot mu-plugin does the loading, precisely so that editing or gutting
`functions.php` cannot remove it. See *Resilient loading* below.

**Not core:** `thetheme_functions/` (app logic), `thetheme_components/`,
`thetheme_blocks/`, `thetheme_src/`, `thetheme_templates/`,
`thetheme_template-parts/`. These are the **app / presentation layer** — they live
in the project (and are demonstrated in the template theme), never in the
engine.

## The carve-out contract (core stays byte-identical)

Core files must contain **zero project data**. Where core needs a per-project
list, the project injects it via a filter; core ships only the default:

| Core file | Project data removed | Injected via |
|---|---|---|
| `subsites.php` | the registered-subsites array | a **registration call**, not a filter: the project calls `thetheme_register_subsites([...])` from `thetheme_functions/app/subsites.config.php`; core ships an empty registry and `thetheme_get_registered_subsites()` reads it back |
| `acf-loader.php` | the `$legacy` block array | `apply_filters('thetheme_acf_legacy_blocks', [])` — project returns its list from `thetheme_functions/app/acf-legacy.php` |

After this, every core file is identical across deployments and `composer update`
is a safe, mechanical sync.

## Scaffolding doctrine for the app/template layer

The project layer is organised **by purpose, not by type** — a reader should guess
where code lives from its responsibility alone.

```
thetheme_functions/        Behavioural code, purpose-split:
  app/                     App wiring (menus, sections, templates, breadcrumb, …)
    subsites.config.php    returns the registered-subsites array
    acf-legacy.php         returns the project legacy-block list
  blocks/                  Block registration glue / shared block helpers
  post-types/              CPT + taxonomy registration
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
thetheme_src/{js,css,fonts,images}/    Front-end source; built to assets/ (Mix).
                           css/ holds the stylesheet entry and its partials.
                           A theme that builds elsewhere keeps its layout — see
                           "Where asset paths come from" below.
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

## Where asset paths come from

Core never scans for build output; it resolves four theme-relative paths and hands
them to WordPress. Every one has the same three-step precedence:

1. **What the subsite declares** — `style`, `script` and `editor` on the registered
   subsite entry. Highest priority, and the route to prefer.
2. **A filter**, when no subsite declared it.
3. **The literal default**, when nothing is filtered.

The editor path has one extra rule at step 1, and it exists because that path resolves
its subsite differently — see *One registered subsite is always the answer*, below.

| Asset | Subsite key | Filter | Default |
|---|---|---|---|
| Front-end stylesheet | `style` | `thetheme_default_stylesheet` | `assets/css/www/app.css` |
| Front-end script | `script` | `thetheme_default_script` | `assets/js/www/app.js` |
| Editor-canvas stylesheet | `editor` | `thetheme_default_editor_stylesheet` | `assets/css/www/editor.css` |
| Login stylesheet | — | `thetheme_login_stylesheet` | `assets/css/wp-login.css` |

The defaults describe this package's own build layout, and **they are a default, not a
requirement**. A theme whose Mix pipeline builds to `css/` and `js/` — tuned by
`mix.setResourceRoot('../')`, as the older lineage is — points the filters at its own
layout rather than moving build output, which would rewrite every font and image URL
inside the built CSS for no functional gain:

```php
add_filter('thetheme_default_stylesheet',        fn() => 'css/www/app.css');
add_filter('thetheme_default_script',            fn() => 'js/www/app.js');
add_filter('thetheme_default_editor_stylesheet', fn() => 'css/www/editor.css');
add_filter('thetheme_login_stylesheet',          fn() => 'css/wp-login.css');
```

Paths are relative to `get_stylesheet_directory()`; a leading `/` is stripped, so
`/css/app.css` and `css/app.css` are the same thing. Returning `''` from
`thetheme_login_stylesheet` switches the login stylesheet off.

**The three front-end/editor fallbacks fire less often than they look.** The front-end
pair is reached only when no subsite resolves at all — any resolved entry returns
before them, declared assets or not. The editor fallback is reached when no subsite
resolves *or* the resolved one declares no `editor`. A project that declares `style`,
`script` and `editor` on every subsite never touches them and does not need the
filters.

### One registered subsite is always the answer

The front end resolves its subsite from the request. The editor cannot: it matches on
`section`, `template`/`templates` and `admin_conditions`, and **all three need a post
id**. On a brand-new post there is no id yet, so none of them can run.

That is fine when several subsites are registered — with nothing to match on there is
genuinely no answer, and the filtered default is the honest one. It is wrong when
**exactly one** subsite is registered: there is nothing to disambiguate, so its `editor`
entry is the only possible answer, post id or not. `thetheme_resolve_editor_css_rel()`
therefore short-circuits to that single subsite when the three matches produce nothing,
and a new post opens with the same canvas CSS an existing one gets.

Two consequences worth knowing:

- **Multi-subsite projects are untouched.** The short-circuit is guarded on a count of
  exactly one, so a project registering two or more behaves exactly as before, on new
  posts and existing ones alike.
- **`thetheme_default_editor_stylesheet` still tells the truth.** If that single subsite
  declares an `editor`, the filter is never reached. If it declares none, the filter runs
  and its second argument is that subsite's id — not `null`, because a subsite *did*
  resolve.

`add_editor_style()` from the project remains a valid alternative route: it covers every
editor screen including new posts, and does not depend on subsite resolution at all.

**The login screen has no subsite route, on purpose.** `wp-login.php` has no queried
page, and the section and template branches of `thetheme_resolve_current_subsite_id()`
both go through `is_page()` — only a project's own `conditions` callable could fire
there, so in practice it resolves to `null`. The filter is the route in, and this is
the path that had none before.

## Core blocks arrive unstyled — the project supplies the CSS

`thetheme_modules/editor.php` disables both per-block asset strategies
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

### Switching the reset off — a conversion runway

A theme being ported onto this package has not written that parity CSS yet, and
until it has, booting the engine is a total visual collapse — every `has-*-color`
/ `has-*-font-size` class in existing post content, and the `--wp--preset--*`
variables a theme's own stylesheet overrides, are defined by the very handles the
reset removes. So the whole reset is gated:

```php
// thetheme_functions/app/wp-block-styles.php, or functions.php, or an mu-plugin
add_filter('thetheme_reset_core_block_styles', '__return_false');
```

The default is `true` — an existing consumer that does nothing keeps the reset,
byte for byte. When it returns false **nothing is hooked at all**: neither
`should_load_*` filter is registered and neither is the dequeue, so WordPress's
own defaults apply untouched. That matters more than it sounds — it is what
brings back per-block assets and a `classic-theme-styles` that WordPress
registers on every request but does not always enqueue, which re-enqueueing by
handle would wrongly switch on.

**Register the filter no later than `after_setup_theme` priority 0.** The gate is
read once, on `after_setup_theme` priority 1 — after the app layer loads
(`thetheme_load_app()` runs at priority 0) and before anything applies the two
`should_load_*` filters, which first happens on `init`. A filter added on `init`
is too late.

Both callbacks are named, so a consumer that misses the filter can still unhook
them:

```php
remove_action('after_setup_theme', 'thetheme_register_core_block_style_reset', 1);
remove_action('wp_enqueue_scripts', 'thetheme_dequeue_core_block_styles', 100);
```

This is a **conversion runway, not a permanent setting**. A theme sitting on the
opt-out is shipping WordPress's block CSS *and* Tailwind, which is the fight the
reset exists to end. Write `_wp.scss` (below), then delete the filter.

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

Token classes are the same story with a different home. `editor.php` derives the
editor palette from `@theme { --color-*: … }` in the theme's SCSS entry and registers
a font-size scale, so the editor writes `has-<slug>-color`,
`has-<slug>-background-color` and `has-<slug>-font-size` classes into content — but
`global-styles`, which would define them, is dequeued too. Define them in the SCSS
entry beside the `@theme` block that names them, not in `_wp.scss`: the partial is
for block *structure*, these are *tokens*. Watch the sanitised slug — a `2xl` font
size becomes `has-2-xl-font-size`, with the hyphen.

That read strips CSS comments before it looks for the block, and takes the **last**
`@theme` block in the file. So a docblock explaining the mechanism may write the
at-rule name followed by a brace without becoming the match, and a stale or example
block earlier in the file doesn't shadow the real one. Under `WP_DEBUG`, a stylesheet
that exists but yields no palette is logged rather than returning silently — the
silence is what makes a missing palette read as a Gutenberg problem.

## WordPress injects a div into every group block — the package removes it

Core registers `wp_restore_group_inner_container()` on `render_block_core/group`
(`wp-includes/block-supports/layout.php`; find it by string, the line number moves
between core versions). When it runs, every non-flex, non-grid `core/group` leaves the
renderer wrapped in an extra `div.wp-block-group__inner-container`, and the
`is-layout-*` classes are **moved off** the group element onto that inner div:

```html
<!-- with core's filter active -->
<div class="wp-block-group alignfull header-cta">
  <div class="wp-block-group__inner-container is-layout-flow wp-block-group-is-layout-flow">
    …
  </div>
</div>

<!-- with the filter removed, which is what this package now does -->
<div class="wp-block-group alignfull header-cta is-layout-flow wp-block-group-is-layout-flow">
  …
</div>
```

**Deleting `theme.json` is what switches it on.** The function's first bail is
`wp_theme_has_theme_json()`, so it is inert on a site that has one. A site running this
package has none — the convention deletes that file as part of the conversion — so
converting a theme *enables* this filter as a side effect, and a rendered document
silently gains a hop no theme in the estate was written against. Any stylesheet or
script that reaches through a group with a direct-child chain stops matching:
`.all > header > .wp-block-group > *` finds nothing, because `*` is now the injected
div. That is not hypothetical — on one consumer it left the site header entirely
unstyled, and 25 rules were dead before anyone looked at the markup.

A bare `file_exists()` on `theme.json` changing rendered markup estate-wide is exactly
the class of implicit core behaviour this package exists to take control of. Where core
decides output from the presence of a file rather than from a declaration, the package
states its position explicitly. So `thetheme_modules/defaults.php` unhooks it on `init`:

```php
remove_filter('render_block_core/group', 'wp_restore_group_inner_container', 10);
```

The wrapper goes and the layout classes stay on the group element itself — the removal
does not cost you `is-layout-constrained` / `wp-block-group-is-layout-constrained`,
which is the failure mode to check for if you ever reproduce this by hand.

### Putting the div back — a conversion runway

A theme that was ported *while* the filter was active may have adapted to it, with
selectors that reach through `> .wp-block-group__inner-container >`. Removing the
wrapper strands those. It can put the div back while it rewrites them:

```php
// thetheme_functions/app/wp-block-styles.php, or functions.php, or an mu-plugin
add_filter('thetheme_restore_group_inner_container', '__return_true');
```

The default is `false` — do nothing and core's wrapper is gone. Register the filter no
later than `after_setup_theme` priority 0; the gate is read on `init`, after the app
layer loads. The callback is named, so a consumer can also unhook it outright:

```php
remove_action('init', 'thetheme_remove_group_inner_container');
```

This is a **conversion runway, not a per-site setting**, in exactly the sense of
`thetheme_reset_core_block_styles` above. Markup that depends on *when* a site was
converted is the drift this package exists to remove, so a theme sitting on `true` is
mid-revert, not configured: rewrite the selectors, then delete the filter. Note also
that some themes hand-write `wp-block-group__inner-container` in their own PHP
templates — `remove_filter()` does not touch those, so a find-and-replace across a
theme's CSS will break the regions its templates still emit. Read the rendered output
per site; do not sweep.

## The editor canvas is a second, hostile environment

`subsites.php` enqueues the resolved editor stylesheet as a native `<link>` on
`enqueue_block_assets`, gated by `is_admin()` (v1.3.0). Deliberately **not**
`block_editor_settings_all`: that route hands the CSS to Gutenberg's in-browser
`transformStyles` scoper, which cannot parse Tailwind v4 (`@property`, `@layer`,
`color-mix()`, nesting) and silently drops the whole sheet. A native `<link>` is parsed
by the browser, exactly like the front end.

Getting the sheet *there* is only half of it. Four properties of the canvas bite, and
none of them show up on the front end.

### WordPress resets the canvas, and its reset outranks inheritance

WordPress loads `wp-block-library/reset.min.css` into the editor and it ships:

```css
html :where(.editor-styles-wrapper){background:#fff;color:initial;font-family:serif;font-size:medium;line-height:normal}
```

`.editor-styles-wrapper` is the canvas container. A Tailwind theme sets its font in
exactly one place — preflight's `html, :host { font-family: var(--font-sans, …) }` — which
reaches that container only by **inheritance**, and a declaration made directly on an
element beats an inherited value at any specificity. So the canvas renders in the
browser's serif while the front end is perfect, which reads as "the custom fonts aren't
loading" and sends people hunting for a 404 that isn't there.

The engine cannot fix this for you: it ships no SCSS, and the editor stylesheet's source
is a project file. Restate the token in the **editor-only** SCSS entry:

```scss
@import "shared";

.editor-styles-wrapper {
    font-family: var(--font-sans);
}
```

Three things about that rule, all load-bearing:

- **Unlayered.** Write it as plain top-level CSS. Tailwind v4 emits preflight inside
  `@layer base`, and unlayered beats layered whatever the specificity. Where WP's reset
  also arrives unlayered — it does when the canvas is not iframed and the reset comes
  through the concatenated admin styles — plain specificity settles it instead: `0,1,0`
  against the reset's `0,0,1`, since `:where()` contributes nothing. Either way the rule
  wins, and neither route needs `!important`. Do not add one: an editor sheet that
  shouts is one nobody can override per block.
- **The token, not the family name.** `var(--font-sans)` keeps the `@theme` block the
  single source of truth.
- **In the editor entry only.** It must go *after* the shared import in the SCSS entry
  that nothing else imports. Putting it in the shared partial or the theme entry ships
  dead CSS to every front-end page.

The same reset also `revert`s `list-style-type`, `margin` and `padding` on `ol`/`ul`
inside the canvas, so list markers reappear in the editor while preflight still strips
them on the front end. **Canvas appearance is not evidence about theme CSS.** Judge
styling on the front end.

### The canvas caps blocks at WordPress's own content width

Second symptom, same family: blocks sit in a narrow column in the editor while the
front end is laid out wide. It reads as a broken layout and it is not one — nothing
the theme wrote is being ignored.

Two causes produce it, and **either one alone is enough**, which is why diagnosing
from the first is a trap:

- **The one-sided reset.** `thetheme_modules/editor.php` hooks its dequeue on
  `wp_enqueue_scripts` — **front end only**. The editor therefore keeps
  `global-styles` and WordPress's layout CSS while the front end loses them. With no
  `theme.json` (the convention here keeps that file disable-only — switch core
  behaviour off, never support rendering), the canvas falls back to WordPress's *own*
  default `contentSize` and constrains every block to it, while Tailwind lays the
  front end out wide. Identical asymmetry to the serif font above.
- **WordPress's own cap, which survives the reset.** Core also ships
  `html :where(.wp-block){max-width:…}` in the canvas. A theme that keeps the full
  front-end style queue — one still on the conversion runway (see *Switching the
  reset off*, above), or one that restores the queue itself — has **no dequeue
  asymmetry at all** and still gets narrow blocks. So "we didn't dequeue anything"
  is not a reason to skip the rule.

**The fix is the project's, not the engine's.** Core is PHP-only: it ships no
stylesheet, no build and no asset pipeline, so it has nothing to emit the rule
*into*. The one PHP-only lever that would set a canvas width without CSS is the
`theme.json` layer, and that layer is disable-only by convention — so that route is
closed by an existing rule, not by preference. Write it by hand in the same
editor-only entry as the font rule:

```scss
@import "shared";

.editor-styles-wrapper {
    font-family: var(--font-sans);
}

.wp-block,
.wp-block-separator {
    max-width: var(--content-width); /* the theme's own token — see below */
}

/* Left to Gutenberg on purpose — do not restate them:
.wp-block[data-align="wide"] { … }
.wp-block[data-align="full"] { … }
*/
```

That is the whole portable rule. Four things about it:

- **Both selectors.** `.wp-block-separator` needs its own entry; capping `.wp-block`
  alone leaves a horizontal rule running the full canvas width inside an otherwise
  constrained column.
- **Leave `data-align="wide"` and `data-align="full"` alone.** Those variants are
  Gutenberg's to size, and capping them is how a full-width hero stops being
  full-width in the editor.
- **The theme's token, not a number.** Use whatever the theme already expresses its
  content width in — a custom property, or `@apply` with the theme's own max-width
  utility if it is a Tailwind project. Where that value lives varies: on this
  scaffold, layout utilities usually sit in the PHP templates rather than in SCSS, and
  a theme still carrying a `theme.json` may have it in `settings.layout.contentSize`.
  Grep all three before deciding the theme hasn't got one. (In practice three
  independent themes on this scaffold have each landed on the same 80rem / 1280px
  cap, so that is the likely answer for a theme with no idiom yet — but read the
  theme, don't copy the number.)
- **Unlayered, no `!important`, editor entry only** — the same three properties that
  make the font rule work, for the same reasons.

### The canvas is not always an iframe

Since WP 6.3 the canvas *can* be an iframe, and the v1.3.0 mechanism was written for
that. It is not guaranteed: measured on WP 7.0.1, a post-editor screen with meta boxes
registered (an SEO plugin, an ACF field group) and blocks registered at `apiVersion: 2`
renders **no iframe at all** — `.editor-styles-wrapper` is a plain `div` in the admin
document. Since any theme using ACF blocks registers `apiVersion: 2` blocks, this is the
common case, not the exception.

The native `<link>` works in both shapes, which is the point. But two things follow:
the editor stylesheet is loaded into the **whole admin page** when the canvas is not
iframed, so keep editor rules scoped to `.editor-styles-wrapper` rather than bare
element selectors; and never reason about the canvas from the iframe assumption — open
DevTools and check.

### A missing editor stylesheet leaves the canvas unstyled

`thetheme_resolve_editor_css_rel()` returns the resolved subsite's declared `editor`
path, falling back to the filtered default (see *Where asset paths come from*, above —
`assets/css/www/editor.css` unless a theme redirects it). If the resolved file does not exist,
`thetheme_enqueue_subsite_editor_styles()` simply `return`s — the canvas gets **no theme
CSS whatsoever**. The front-end path has an unconditional fallback; this one has none. A
subsite that declares an `editor` entry must have an SCSS source that actually builds it.

The `return` stays — enqueuing a 404 would help nobody — but it is no longer silent.
Under `WP_DEBUG` it writes one line naming the path it looked for:

```
[thetheme] Editor stylesheet not found, canvas left unstyled: assets/css/shop/editor.css
```

That is the whole signal, and it is deliberately debug-only: an unstyled canvas reads as
a CSS problem and sends people into DevTools, when the actual fault is a path that was
never built. With `WP_DEBUG` off, production stays quiet.

## Resilient loading (boot mu-plugin)

WordPress only auto-loads `functions.php` from the theme, and that file is editable —
so any loader placed there can be removed. To prevent that, the loaders live in a
must-use plugin instead:

- `mu-plugin/thetheme-boot.php` boots core on `setup_theme` (so core helpers are
  available in `functions.php`) and loads the active theme's app layer
  (`thetheme_functions/`) on `after_setup_theme` via `thetheme_load_app()`.
- `src/Installer.php` copies that stub into `wp-content/mu-plugins/` automatically
  during `composer install/update` — no manual placement.

Result: `functions.php` carries **no loader code**; editing or gutting it cannot
remove core or app loading.

### Consuming theme wiring

```jsonc
{
  "require": { "kristoffbertram/thetheme-core": "^1.1" },
  "repositories": [
    { "type": "vcs", "url": "https://github.com/kristoffbertram/thetheme-core.git" }
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
- **v1.3.0** (2026-06-17) — editor canvas styles now load as a **native `<link>` in the
  canvas** via `enqueue_block_assets` + `is_admin()`, replacing the
  `block_editor_settings_all` injection. The old path routed CSS through Gutenberg's
  `transformStyles` scoper, which can't parse Tailwind v4 (`@property`, `@layer`,
  `color-mix()`, nesting) and silently dropped the whole sheet — so no editor styles
  applied. Subsite resolution preserved (`thetheme_resolve_editor_css_rel()`).
  The commit message and the original note both said "inside the iframe"; that is the
  case it was written for, not a guarantee — see *The editor canvas is a second, hostile
  environment*, which is the current account of what the canvas actually is.
