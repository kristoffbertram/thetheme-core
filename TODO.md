# TODO — thetheme-core

Short ADR-style notes. Decisions only; the reference documentation is `README.md`
and `thetheme_modules/README.md`.

---

## D1 — Promoted functions are guarded AND prefixed

Every function this package absorbs from a consuming theme ships wrapped in
`function_exists()` (classes in `class_exists()`) **and** carries a `thetheme_`
prefix.

**Why both.** The package loads on `setup_theme`, which runs before a theme's
`functions.php`. A theme that declares the same unprefixed name at file scope is
therefore a compile error, not a warning — and because the name is unowned, the
failure names no package. Guarding stops the fatal; prefixing stops the shadowing
that made the fatal possible. Neither alone is enough: guarding without prefixing
leaves the theme's older copy silently winning, and prefixing without guarding
still fatals on any theme that has not been ported yet.

The unprefixed names below are the live hazard. They exist at file scope in themes
that have not been ported, so nothing may be promoted under these names:

| Legacy name | Name in this package |
|---|---|
| `custom_excerpt_more` | `thetheme_excerpt_more` |
| `tags_categories_support_all` / `_query` | `thetheme_taxonomies_on_pages` / `_query` |
| `the_duplicate_post_as_draft` / `the_duplicate_post_link` | `thetheme_duplicate_post_as_draft` / `_link` |
| `the_add_template_name_to_admin_body_class` | `thetheme_admin_body_template_class` |
| `the_remove_x_pingback_header` | `thetheme_remove_pingback_header` |
| `the_custom_security_headers` | `thetheme_send_security_headers` |
| `class The_Theme_Image_Sizes` | `class Thetheme_Image_Sizes` |
| `class The_Theme_Allow_Custom_Mimes` | `class Thetheme_Custom_Mimes` |

Names already carrying the prefix keep it and gain only the guard.

A theme that calls a legacy name from a template keeps its own guarded copy until
the call site moves; the package never ships an unprefixed alias.

---

## D2 — One opt-out filter per module, not per behaviour

A module is a posture a site either wants or does not. Two exceptions get finer
granularity because a theme in the estate has a measured objection to that one
behaviour while wanting the rest of its module:

- the default-image-size stripper — one theme renders `medium_large`, so removing
  it blanks two templates
- the REST-API auth gate — one theme serves a public API

Everything else is governed by its module's single filter.

---

## D3 — Module surface

The set this package has converged on. Rows marked *planned* do not exist yet; they
are listed so the filter names are settled before the first one lands.

| Module | Status | Governing filter | Default |
|---|---|---|---|
| `acf-loader.php` | shipped | `thetheme_acf_legacy_blocks`, `thetheme_log_registered_blocks` | `[]`, `false` |
| `defaults.php` | **shipped 2026-09-02** | `thetheme_html5_markup`, `thetheme_responsive_embeds`, `thetheme_page_excerpts`, `thetheme_excerpt_more_text`, `thetheme_remove_emoji_support`, `thetheme_remove_wp_embed` | `true` / `''` |
| `templating.php` | shipped | — | — |
| `debug.php` | shipped | — | — |
| `editor.php` | shipped + extend | `thetheme_reset_core_block_styles`, `thetheme_allowed_core_blocks`, `thetheme_allowed_plugin_blocks`; also owns `disable-custom-gradients`, an empty `editor-gradient-presets`, `editor-spacing-sizes` and the drop-cap filter *(2026-09-02, extended 2026-09-04)* | `true`, `[]`, `[]` |
| `images.php` | shipped | `thetheme_removed_default_image_sizes` *(landed 2026-09-02)* | `[]` = off |
| `media.php` | planned | `thetheme_custom_mime_types` | `[]` |
| `security.php` | **shipped 2026-09-02** | `thetheme_remove_feed_links`, `thetheme_hide_admin_bar`, `thetheme_login_error_message`, `thetheme_redirect_author_archives`, `thetheme_disallow_file_edit`, `thetheme_rest_requires_auth`, `thetheme_security_headers` | `true`×5, **`false`**, header map |
| `body-classes.php` | **shipped 2026-09-02** | `thetheme_body_classes` | `true` |
| `subsites.php` | shipped + extend | `thetheme_default_stylesheet`, `thetheme_default_script`, `thetheme_default_editor_stylesheet`, `thetheme_script_data` | paths, `[]` |
| `wp-admin.php` | **removed 2026-09-02** — both candidates for it were rejected (D5) | — | — |
| `wp-login.php` | shipped + extend | `thetheme_login_stylesheet`, `thetheme_login_logo` | path, `''` |

An allowlist filter defaulting to `[]` means *leave WordPress alone* — a theme that
sets nothing behaves exactly as it does today.

---

## D4 — The template router is not promoted wholesale

Every consuming theme has a `template_include` router, which reads as a strong
signal, but each route table is genuinely different and the shared part is about
fifteen lines of walking a map. The duplication that is actually byte-identical is
the **subsite fallback branch**, and that is subsite logic.

So: the fallback branch becomes a helper in `subsites.php`. A general router stays
out until three consuming themes have independently converged on the same
route-table shape. Reversal is cheap; a wrong abstraction in a public package is
not.

---

## D5 — What stays out of the package

Rejected on scope, not on effort. Each of these appears in three or more themes.

- **The "Modified" admin list column.** *Decided 2026-09-02: deleted estate-wide, not
  promoted.* It was never theme code — an inlined copy of a plugin that is installed
  on no site in the estate, so its textdomain never resolved. Twenty-one files, two
  label variants, and twenty of them carrying the same unbalanced `<strong>`. A
  package-owned version would have preserved a feature nobody had deliberately chosen;
  removal is the alignment.
- **"Duplicate as draft" row actions.** *Decided 2026-09-02: deleted, not promoted.*
  A pasted snippet, on six core sites in two naming variants (`the_*`, `rd_*`) and
  eleven more off core. Dedicated plugins do this properly — one estate site already
  runs `duplicate-page` and carries no copy at all, which is the shape the rest should
  reach. Installing a plugin is a per-site decision, out of scope here.
- **The SingleSnip CPT and its shortcode.** *Decided 2026-09-02: standardised into the
  project layer, not promoted.* One canonical file per site at
  `thetheme_functions/post-types/singlesnip.php`, identical across all eight core
  sites — previously five drifted variants inlined into whichever catch-all file a
  site happened to have, plus a stand-alone plugin on several. It is content
  machinery: what a site stores, not how the engine runs. The `function_exists()`
  guard stays, because the old plugin declares the same names unguarded and loads
  first. **The old plugin's `uninstall.php` force-deletes every Snip post** — remove
  it by deactivating and deleting the directory, never via Delete in wp-admin.
- **Icon shortcodes.** Multiple incompatible bodies behind one shortcode tag, with
  per-site content depending on its own. No single correct behaviour to promote.
- **Brand or estate-specific helpers**, including layout-token functions. A public
  package is the wrong scope.
- **Old-lineage layout shortcodes.** Content compatibility for a lineage being
  retired; promoting them gives it a permanent home here.

---

## D6 — Consuming themes pin a tag, not a branch

Themes currently pin this package four different ways across the estate. That makes
a release land on some sites and not others, which defeats the point of the package.

One form, everywhere: a caret range against a published tag. Branch pins
(`dev-main`, `dev-master as x.y.z`) are migrated when a theme is next touched, not
pre-emptively. Each theme records the resolved version and any intentional deviation
in its own local notes.

---

## D7 — There is one app-layer directory, and `thetheme_app/` is not it

*Done 2026-09-02.* `thetheme_load_app()` used to walk `thetheme_functions/` **and** a
top-level `thetheme_app/`, as back-compat for themes mid-migration. The second pass is
removed.

It was kept alive by exactly one file on one sample install — an `acf-legacy.php` that
was byte-identical to the one the starter theme already ships at
`thetheme_functions/app/`. Moving it and deleting the directory made the branch dead
code, so it went with it. `thetheme_app/` now exists nowhere on disk, and the
convention checker's B1 rule (which already scored its presence as a failure) passes
everywhere.

Consequence to state plainly: a theme that still has one will **silently lose its app
layer** — no error, no log line. Nothing in the estate is in that position today, but
it is the reason this is a minor bump and not a patch.

---

## D8 — Essential vs switchable, in `security.php`

The split is the design, not a convenience. **Essential** means nothing legitimate
consumes the thing, so a switch would only be a way to get it wrong: RSD discovery,
the generator tag, the shortlink, XML-RPC, the pingback header. **Switchable** means
turning it on costs a real capability — feed discovery, an editor's toolbar,
anonymous REST access, a usable login error.

Two defaults follow from that and should not be flipped casually:

- `thetheme_rest_requires_auth` defaults to **true** *(changed 2026-09-02)*. The
  earlier default of false rested on the belief that one theme served a public API;
  on inspection that theme *consumes* a remote API over cURL and exposes nothing. No
  theme or mu-plugin on the package registers a REST route, and no front-end script
  fetches `/wp-json`. A route that must answer anonymously is named through
  `thetheme_public_rest_routes` rather than the gate being switched off.
- `thetheme_login_error_message` defaults to a **generic string, not `''`**. The
  originals returned an empty string, which tells a user who mistyped their password
  nothing at all. The threat is username enumeration; one generic message closes it
  without breaking the form.

A consuming theme was found to be AHEAD of the package on 2026-09-02 and its work was
promoted rather than discarded: blocking `?author=N` at `parse_request`, hiding the
REST users endpoints from anonymous callers, and stripping `author_name` from oEmbed.
Those are the vectors that actually hand out a username; the author-archive redirect
this package already had only covers `/author/<slug>/`. All three now sit behind
`thetheme_block_user_enumeration`. When a site has solved something better than core
has, the direction of travel is upward.

HSTS and CSP are absent from the header default on purpose. HSTS is remembered for a
year across every subdomain and cannot be withdrawn client-side; CSP is already owned
by an mu-plugin on at least one site.

---

## D9 — No JSON, no XML, unless it is deliberately opened

*Standing rule, 2026-09-02.* WordPress ships four read interfaces to the whole content
set open by default: the REST API, RSS/Atom feeds, the core XML sitemap, and oEmbed.
None of them was asked for by any site on this estate. `security.php` closes all four
and each is reopened by naming it.

The one with a cost outside security is the sitemap, and the dev copies mislead here.
Scanning `wp-content/plugins` locally finds an SEO plugin on ONE of the thirteen sites.
**Production is different:** nearly all of them run The SEO Framework (Sybre Waaijer),
which is simply not present in these dev trees. So the local measurement understates
sitemap coverage badly, and no conclusion about a site's sitemap may be drawn from a
dev checkout.

What that means in practice: an SEO plugin disables core's `/wp-sitemap.xml` and serves
its own, so on those sites `thetheme_enable_xml_sitemap` defaulting to false changes
nothing — the plugin's sitemap is the controlled surface and core never had the job.
The filter only matters on a site with no SEO plugin, where core's sitemap was the only
one. **Verify against production, not against the dev tree, before assuming a site has
lost its sitemap.**

Feeds carry no such risk: confirmed 2026-09-02 that no site on the estate publishes a
feed anyone uses, so the endpoints returning 404 costs nothing.

Feeds are the same shape: the endpoints now 404, not merely go unadvertised. A site
that publishes a feed people actually subscribe to turns it back on.

---

## D10 — Next release is v1.4.0

Everything past `v1.3.0`, which is now substantial:

- **Two new modules** — `body-classes.php` and `security.php`.
- **`defaults.php` extended** with HTML5, responsive embeds, page excerpts, emoji
  removal and the oEmbed host script; **`editor.php`** takes `disable-custom-gradients`;
  **`images.php`** gains the opt-in default-size stripper.
- **Fifteen earlier commits** carrying six filters (`thetheme_reset_core_block_styles`,
  the three asset-path filters, `thetheme_login_stylesheet`,
  `thetheme_log_registered_blocks`) plus fixes to the `@theme` parser, the
  single-subsite editor stylesheet, `fgc()` `$echo` and attribute escaping.
- **`thetheme_app/` loader pass removed** (D7), and `wp-admin.php` deleted.

All additive or opt-in except the two removals, neither of which any site depends on,
so: **minor**.

THE SITES DO NOT HAVE ANY OF THIS YET. Every consuming theme runs a vendored snapshot;
the app layers have already been stripped of the code these modules replace, so the
`composer update` that lands this version is what makes the two halves meet. Until
then a site is missing behaviour it used to have.

Tagging and pushing this repo is done by hand, never by an agent.
