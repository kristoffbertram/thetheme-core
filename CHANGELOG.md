# Changelog

Every released version of `thetheme-core`, oldest first. Releases are semver tags
(`v1.0.0`, `v1.1.0`, …) resolved by Composer's VCS driver; themes pin a range such
as `^1.4`. Entries moved here verbatim from the README's former *Status* section —
*italic* cross-references point to headings in [`README.md`](README.md).

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
- **v1.4.0** (2026-09-15) — the package takes over what the app layers used to carry, and
  closes what nothing consumes. Every consumer on a path repository already runs this tree
  (vendored 2026-09-02); the tag is what the vcs route resolves.
  - **BREAKING (markup):** the `div.wp-block-group__inner-container` WordPress injects into
    every non-flex, non-grid `core/group` is removed on every consumer, unconditionally —
    `defaults.php` unhooks `wp_restore_group_inner_container` at file scope, and there is no
    opt-out filter. Selectors that assumed the div (four themes had them) are rewritten
    before a site takes this version. See *WordPress injects a div into every group block*.
  - **BREAKING (loader):** the retired `thetheme_app/` pass is gone from the app loader —
    only `thetheme_functions/` is walked. Verified to affect no consumer: none has the
    directory. The walk is now sorted (`SORT_STRING`), so load order is documented rather
    than filesystem-dependent.
  - **Default changes, live estate-wide since 2026-09-02:** `thetheme_rest_requires_auth`
    is **`true`** — the REST API answers 401 to anonymous callers unless a theme returns
    `false` from the filter or lists route prefixes in `thetheme_public_rest_routes`; and
    `thetheme_login_error_message` returns a generic string where the originals returned
    `''`.
  - New module `security.php`: REST behind auth, feed endpoints 404, oEmbed off, the core
    XML sitemap off — and trimmed to `post` + `page` when a site opts it back in (the
    `users` and `taxonomies` providers are dropped with no filter); three user-enumeration
    vectors closed (`?author=N`, the REST users endpoints, oEmbed `author_name`); RSD,
    generator tag, shortlink, XML-RPC and `X-Pingback` removed with no switch.
  - New module `body-classes.php`.
  - Module rename/split: `editing.php` → `editor.php`; `developing.php` → `templating.php`
    + `debug.php`; the empty `wp-admin.php` deleted.
  - `editor.php` declares the five switches a deleted `theme.json` hands back to WordPress:
    `disable-custom-gradients`, an empty `editor-gradient-presets`, `editor-spacing-sizes`,
    drop cap off (via `block_editor_settings_all`), and `align-wide`. See *What a deleted
    `theme.json` hands back*.
  - New filters, default in brackets. The first six replace a hard-coded literal with the
    same literal; the rest arrived with the modules above:
    - `thetheme_reset_core_block_styles` (`true`) — the core-block style dequeue; the
      dequeue is now a named function (`thetheme_dequeue_core_block_styles`) a theme can
      unhook, and the filter is read on `after_setup_theme` priority 1.
    - `thetheme_default_stylesheet` (`assets/css/www/app.css`)
    - `thetheme_default_script` (`assets/js/www/app.js`)
    - `thetheme_default_editor_stylesheet` (`assets/css/www/editor.css`)
    - `thetheme_login_stylesheet` (`assets/css/wp-login.css`)
    - `thetheme_log_registered_blocks` (`false`) — the registered-block debug log.
    - `thetheme_removed_default_image_sizes` (`[]`) — opt-in stripper of core's default
      image sizes; an empty list does nothing.
    - `thetheme_html5_markup` (`true`)
    - `thetheme_responsive_embeds` (`true`)
    - `thetheme_page_excerpts` (`true`)
    - `thetheme_excerpt_more_text` (`''`)
    - `thetheme_remove_emoji_support` (`true`)
    - `thetheme_remove_wp_embed` (`true`)
    - `thetheme_body_classes` (`true`)
    - `thetheme_rest_requires_auth` (**`true`**)
    - `thetheme_public_rest_routes` (`[]`)
    - `thetheme_disable_feeds` (`true`)
    - `thetheme_enable_xml_sitemap` (`false`)
    - `thetheme_disable_oembed` (`true`)
    - `thetheme_block_user_enumeration` (`true`)
    - `thetheme_remove_feed_links` (`true`)
    - `thetheme_hide_admin_bar` (`true`)
    - `thetheme_login_error_message` (generic string)
    - `thetheme_redirect_author_archives` (`true`)
    - `thetheme_disallow_file_edit` (`true`)
    - `thetheme_security_headers` (header map; no HSTS, no CSP)
  - Fixes: the `@theme` palette parser ignores comments and takes the last `@theme` block;
    the editor stylesheet resolves on a single-subsite site with no post id; `fgc()`
    honours `$echo`; `alt` and `class` are escaped in `thetheme_image()`.
  - `LICENSE` (MIT) is in the tree — `v1.3.0` declared MIT in `composer.json` with no
    licence file.
  - Repo hygiene: `.gitattributes` marks `HANDBOOK.md`, `CLAUDE.md` and `.DS_Store`
    `export-ignore`, so none of them reaches a Composer dist.
- **v1.4.1** (2026-09-22) — the line-height support name corrected.
  `editor.php` declared `editor-line-height`, a name WordPress has never read; it now
  declares `custom-line-height`, the one `wp-includes/block-editor.php:815` maps to
  `enableCustomLineHeight`. Consumers that re-vendor gain the editor's line-height control
  — the one `line-height:N` values already in published content need — and nothing else
  changes. See *What a deleted `theme.json` hands back*.
  - `reference/_wp-buttons.scss` added: the core-block parity rules for
    `core/buttons` / `core/button`, which every port now pastes into its `_wp.scss`. The
    button is the block a conversion misses invisibly — the house look is written through
    `.wp-block-buttons .wp-block-button a`, which never reaches a `wp:button` saved with no
    `wp:buttons` wrapper, so those render as bare anchor text on a page that otherwise looks
    finished. Copy-source only: nothing in the package requires, enqueues or compiles it, so
    **no runtime behaviour changes and a consumer that re-vendors gains a file it must still
    paste**. See *The button block is the exception the package ships*.

- **v1.4.2** (2026-09-25) — **the www asset fallback cache-busts on `filemtime`, not the theme
  `Version:`.** `subsites.php`'s subsite branch already versioned styles and scripts on
  `filemtime`; the `www` fallback — the branch every non-subsite page takes — versioned on
  `wp_get_theme()->get('Version')` alone. A rebuilt stylesheet therefore kept serving under the
  old `?ver=`, and browsers held the previous copy indefinitely. It presented as "I deployed and
  nothing changed": one estate site lost its footer colour on 2026-09-24 and another
  lost its heading font on 2026-09-25, both fixed by a hard refresh and neither
  leaving a trace in any log. **Every consumer should re-vendor** — the bug is silent, affects all
  of them, and the fix needs no change on their side. Also in this release: `reference/_wp-buttons.scss`
  re-derived against WP 7.0.4 and diff-checked to 7.1.2, and the stretched-row width core never writes
  dropped from the button copy-source.
  - `reference/_wp-buttons.scss` re-derived against WP 6.6.1.
    It shipped in `v1.4.1` with an unscoped `.wp-block-buttons .wp-block-button__link
    { width: 100% }` that core writes nowhere: pasted faithfully it stretched **every**
    button in a row, so a port was broken *by* following the reference. That line is gone,
    core's own scoped widths are in (`.has-custom-width` pair, the `:not(.is-content-
    justification-*) .wp-block-button.aligncenter` block, and the `wp-block-button__width-N`
    set the editor's width control writes), the `[style*=text-decoration]` pairs are carried,
    and the docblock now names the trap so the next re-derive cannot reintroduce it. The two
    rules that are *not* core — `box-sizing` on the row, the `.wp-block-button.alignright`
    wrapper form — are labelled as such in place. Still copy-source, so **no runtime
    behaviour changes**; this rides the next behaviour cut rather than being its own release.
    A port that already pasted the old file re-pastes the button section.
  - `reference/_wp-buttons.scss` re-derived against **WP 7.0.4, diff-checked
    against 7.1.2** — the lowest and highest versions any consumer serves. The entry above
    read a 6.6.1 tree, which nothing on the package renders, and its premise is false on 7.x:
    core writes `.wp-block-buttons .wp-block-button__link { width: 100% }` itself
    (`blocks/buttons/style.css:57`) and pairs it with `height: 100%; align-content: center`
    on the anchor (`blocks/button/style.css:7-8`). Both are back, because they are core's
    and they are latent while the wrapper is sized as core sizes it — the
    `.wp-block-buttons > .wp-block-button { display: inline-block; margin: 0 }` line the
    file now marks as the one a port must never drop, inside a row the site's own
    `_wp.scss` lays out with `body .is-layout-flex { display: flex }` and `.is-layout-flex
    { flex-wrap: wrap; align-items: center }` (global-styles' layout base, which the reset
    dequeues). Measured in headless Chrome over core's saved markup: a two-button row is
    unchanged (each anchor content-width), a lone `wp:button` is unchanged, a `__width-50`
    button is unchanged; the visible deltas are core's own — a centred button in an
    unjustified row now fills the row, and the editor's *Stretch* vertical alignment now
    gives equal-height buttons. Also from the 7.0.4 read: `box-sizing` on the row is core
    now (label dropped), the outline border is `currentColor` and its background
    `transparent`, and 7.1's free-form width pair (`[class*=wp-block-button__width]`,
    `--wp--block-button--width`) is carried, labelled *7.1+ only*. The one remaining
    non-core line, `.wp-block-button.alignright`, stays labelled. Still copy-source, so
    **no runtime behaviour changes** and it rides the next behaviour cut. A port that pasted
    the section re-pastes it from HEAD and confirms the wrapper line and the layout base
    in its own `_wp.scss`.
  - **behaviour change:** the www asset fallback now cache-busts on the
    file's mtime. `thetheme_enqueue_subsite_assets()` in `subsites.php` versioned the
    front-end stylesheet and script on the theme `Version` on every page where no subsite
    resolved, while its own subsite branch and the editor stylesheet already used
    `filemtime`. A rebuilt `app.css` was therefore served under an unchanged `?ver=` and
    browsers kept the old sheet — *"I deployed and nothing changed"*. Both enqueues now
    use `filemtime` when the file exists and `Version` only when it is missing, matching
    their siblings. **On every consumer that re-copies, the `?ver=` on those two URLs moves
    from the theme `Version` to the file's mtime, and consumers stop needing a `Version`
    bump per build.** Until a site re-copies, its fallback still versions on `Version`, so
    bumping `style.css` per CSS change stays the working rule there. See *Where asset
    paths come from*.
