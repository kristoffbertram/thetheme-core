<?php
/**
 * Security posture — remove what WordPress emits that nothing consumes, and gate
 * what trades a real capability for hardening.
 *
 * Consolidated from four themes on 2026-09-02. The split below is the design:
 *
 *   ESSENTIAL   no filter. Nothing legitimate consumes these, on this estate or
 *               anywhere else, so a switch would only be a way to get it wrong.
 *   SWITCHABLE  a filter, because switching it on costs something real — feed
 *               discovery, an editor's toolbar, anonymous API access.
 *
 * The package's standing assumption applies: WordPress's defaults are not trusted to
 * stay as they are, so core removes and a theme opts back in where it needs to,
 * rather than core leaving a default in place and hoping.
 *
 * ── A NOTE ON WHAT WAS *NOT* CARRIED OVER ────────────────────────────────────
 *
 * The originals removed eleven wp_head hooks. Measured against WP core, only five
 * were ever registered:
 *
 *   index_rel_link, start_post_rel_link, parent_post_rel_link
 *       do not exist in WordPress at all — removed in 3.3.
 *   wlwmanifest_link
 *       unregistered since WP 6.7.
 *   adjacent_posts_rel_link, adjacent_posts_rel_link_wp_head
 *       not registered on wp_head.
 *
 * They are not reproduced here. remove_action() against a hook nothing registered is
 * silent, which is exactly why they survived a decade of copy-forward.
 *
 * The originals also carried add_filter('wp_generator', …) returning ''. There is no
 * `wp_generator` filter — it is only ever an action on wp_head — so that call hooked
 * nothing on three of the four sites. (The fourth used `the_generator`, which is
 * real, but moot: the remove_action below already stops the output.) Also dropped.
 */

/* ─────────────────────────────  ESSENTIAL  ───────────────────────────────── */

/**
 * Discovery and version noise. RSD is XML-RPC client discovery for editors that no
 * longer exist; the generator tag advertises the WordPress version to scanners; the
 * shortlink is a legacy ?p=123 alternate nothing follows.
 */
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head', 10);

/**
 * XML-RPC, and the pingback header that advertises it.
 *
 * This is the estate's main brute-force amplification surface: xmlrpc.php accepts
 * batched authentication attempts in a single request. Verified safe here before
 * making it unconditional — the management plugin in use references xmlrpc.php only
 * as a backup exclusion path, not as a transport, and no site on the package runs
 * Jetpack or publishes from the mobile app.
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Belt and braces on XML-RPC: strip the pingback and multicall methods even if
 * something later re-enables the endpoint. `system.multicall` is what turns one
 * request into hundreds of login attempts.
 */
if (!function_exists('thetheme_strip_xmlrpc_methods')) {
    function thetheme_strip_xmlrpc_methods($methods) {
        unset(
            $methods['pingback.ping'],
            $methods['pingback.extensions.getPingbacks'],
            $methods['system.multicall']
        );
        return $methods;
    }
}
add_filter('xmlrpc_methods', 'thetheme_strip_xmlrpc_methods');

if (!function_exists('thetheme_remove_pingback_header')) {
    function thetheme_remove_pingback_header($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    }
}
add_filter('wp_headers', 'thetheme_remove_pingback_header');

/**
 * A search request can arrive with `s` as an array, which makes anything treating it
 * as a string emit warnings or worse. Coerce it once, centrally.
 */
add_filter('request', function ($query_vars) {
    if (isset($query_vars['s']) && is_array($query_vars['s'])) {
        $query_vars['s'] = implode(' ', $query_vars['s']);
    }
    return $query_vars;
});

/* ────────────────────────────  SWITCHABLE  ──────────────────────────────── */

/**
 * Feed discovery links.
 *
 * ON by default, to match the estate as it stands — but this is the one entry here
 * that is a CONTENT decision rather than hardening. Removing the links does not
 * disable feeds (/feed/ still responds); it stops readers and aggregators finding
 * them. A site with a news or article section usually wants them back:
 *
 *     add_filter('thetheme_remove_feed_links', '__return_false');
 */
if (apply_filters('thetheme_remove_feed_links', true)) {
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
}

/**
 * Hide the front-end admin bar from everyone below administrator.
 *
 * Policy, not security: it costs editors and authors the front-end "Edit" link. On a
 * site with several content editors you may want it left alone.
 *
 *     add_filter('thetheme_hide_admin_bar', '__return_false');
 */
if (!function_exists('thetheme_hide_admin_bar')) {
    function thetheme_hide_admin_bar() {
        if (!apply_filters('thetheme_hide_admin_bar', true)) {
            return;
        }
        if (!current_user_can('administrator') && !is_admin()) {
            show_admin_bar(false);
        }
    }
}
add_action('after_setup_theme', 'thetheme_hide_admin_bar');

/**
 * Login errors: one generic message, not silence.
 *
 * The originals returned an empty string, which tells a user who mistyped their
 * password precisely nothing. The threat being addressed is username enumeration —
 * WordPress's default errors distinguish "unknown username" from "wrong password" —
 * and a single generic message closes that without making the form unusable.
 *
 * Return '' from the filter to restore the old silence.
 */
if (!function_exists('thetheme_login_error_message')) {
    function thetheme_login_error_message($error) {
        return (string) apply_filters(
            'thetheme_login_error_message',
            __('Those details were not recognised.', 'thetheme'),
            $error
        );
    }
}
add_filter('login_errors', 'thetheme_login_error_message');

/**
 * Author archives redirect to the home page.
 *
 * /author/<name>/ discloses usernames, which are half of a credential. Off-limits on
 * a site that genuinely publishes per-author archives:
 *
 *     add_filter('thetheme_redirect_author_archives', '__return_false');
 */
add_action('template_redirect', function () {
    if (!apply_filters('thetheme_redirect_author_archives', true)) {
        return;
    }
    if (!is_admin() && is_author()) {
        wp_safe_redirect(home_url(), 301);
        exit;
    }
});

/**
 * Disallow the built-in theme/plugin file editor.
 *
 * Guarded: wp-config.php is the proper home for this, and defining a constant twice
 * is a notice. If wp-config already sets it, wp-config wins.
 */
if (apply_filters('thetheme_disallow_file_edit', true) && !defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/* ──────────────  JSON AND XML ARE CLOSED UNLESS OPENED  ────────────── */

/**
 * The standing rule for this estate: a site serves no JSON and no XML unless that
 * response is deliberately turned on. WordPress ships four such surfaces open — the
 * REST API, RSS/Atom feeds, the core XML sitemap, and oEmbed — and each is a
 * read interface to the whole content set that nobody asked for.
 *
 * Every one of them is closed here by default and reopened by naming it. Verified
 * before flipping these on 2026-09-02: no theme on the package registers a REST
 * route, no mu-plugin does, and no front-end script fetches /wp-json.
 */

/**
 * REST API — authenticated callers only.
 *
 * DEFAULT CHANGED 2026-09-02, from false to true. The previous default was justified
 * on the belief that one theme served a public API; on inspection that theme
 * *consumes* a remote API over cURL and exposes nothing. Nothing on the estate needs
 * anonymous REST.
 *
 * The block editor, and every plugin that uses REST from wp-admin, are unaffected —
 * those callers are logged in.
 *
 * A route that genuinely must answer anonymously is named, not switched on wholesale:
 *
 *     add_filter('thetheme_public_rest_routes', fn($r) => [...$r, '/wp/v2/search']);
 */
add_filter('rest_authentication_errors', function ($access) {

    if (!apply_filters('thetheme_rest_requires_auth', true) || is_user_logged_in()) {
        return $access;
    }

    $route  = $GLOBALS['wp']->query_vars['rest_route'] ?? ($_GET['rest_route'] ?? '');
    $public = (array) apply_filters('thetheme_public_rest_routes', []);

    foreach ($public as $allowed) {
        if ($route !== '' && str_starts_with(ltrim($route, '/'), ltrim($allowed, '/'))) {
            return $access;
        }
    }

    return new WP_Error(
        'rest_cannot_access',
        __('Only authenticated users can access the REST API.', 'thetheme'),
        ['status' => rest_authorization_required_code()]
    );
});

/**
 * Stop advertising the API that is now closed — the <link rel="https://api.w.org/">
 * in the head, and the same thing as an HTTP Link header.
 */
if (apply_filters('thetheme_rest_requires_auth', true)) {
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
}

/**
 * Feeds — the endpoints, not just the discovery links.
 *
 * `thetheme_remove_feed_links` above stops advertising them; this stops them
 * answering. /feed/, /comments/feed/ and ?feed=rss2 each return the full recent
 * content set as XML.
 *
 * A site that publishes a real feed — a news or article section people subscribe to —
 * turns this back on:
 *
 *     add_filter('thetheme_disable_feeds', '__return_false');
 */
if (!function_exists('thetheme_disable_feed')) {
    function thetheme_disable_feed() {

        if (!apply_filters('thetheme_disable_feeds', true)) {
            return;
        }

        wp_die(
            esc_html__('No feed is available on this site.', 'thetheme'),
            '',
            ['response' => 404]
        );
    }
}
foreach (['rdf', 'rss', 'rss2', 'atom', 'rss2_comments', 'atom_comments'] as $thetheme_feed) {
    add_action('do_feed_' . $thetheme_feed, 'thetheme_disable_feed', 1);
}
unset($thetheme_feed);

/**
 * WordPress's own XML sitemap (/wp-sitemap.xml, WP 5.5+).
 *
 * OFF by default. IF THIS SITE HAS NO SEO PLUGIN, TURNING IT OFF REMOVES ITS ONLY
 * SITEMAP — that is a search-visibility decision, not a security one, so make it
 * knowingly:
 *
 *     add_filter('thetheme_enable_xml_sitemap', '__return_true');
 *
 * A site running an SEO plugin is unaffected either way: those plugins disable core's
 * sitemap and serve their own, which is a controlled surface and not touched here.
 *
 * WHAT A CONSUMER GETS WHEN IT OPTS IN: pages and posts, and nothing else
 * (thetheme-core-D35). Core registers three providers — `posts`, `taxonomies`, `users`
 * (wp-includes/sitemaps/class-wp-sitemaps.php) — and two of them are dropped below
 * unconditionally. There is no filter to switch them back on; that is the point.
 *
 *   - `users` publishes every author name at /wp-sitemap-users-1.xml. That is username
 *     disclosure, and this same module already redirects /author/<name>/ for exactly
 *     that reason: handing the same list back as XML would undo through one surface
 *     what is closed on another.
 *   - `taxonomies` publishes category and tag archives, which is not what "pages and
 *     posts only" means.
 *
 * The surviving `posts` provider is pinned to `post` and `page` rather than left to
 * carry every public post type, so a consumer that registers a custom type does not
 * publish it without having decided to.
 *
 * The URL is core's and stays core's: nothing here touches /wp-sitemap.xml, the
 * rewrite rules or robots.txt.
 */
add_filter('wp_sitemaps_enabled', function ($enabled) {
    return (bool) apply_filters('thetheme_enable_xml_sitemap', false);
});

add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    return in_array($name, ['users', 'taxonomies'], true) ? null : $provider;
}, 10, 2);

add_filter('wp_sitemaps_post_types', function ($post_types) {
    return array_intersect_key($post_types, ['post' => true, 'page' => true]);
});

/**
 * oEmbed — the JSON/XML endpoint that lets OTHER sites embed this one.
 *
 * This is not about embedding a video here; that is unaffected. It removes the
 * discovery links, the /wp-json/oembed/1.0/embed route and the legacy ?oembed=true
 * response, all of which hand out post data anonymously.
 */
if (apply_filters('thetheme_disable_oembed', true)) {

    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');

    add_filter('embed_oembed_discover', '__return_false');

    add_filter('rest_endpoints', function ($endpoints) {
        unset($endpoints['/oembed/1.0/embed'], $endpoints['/oembed/1.0/proxy']);
        return $endpoints;
    });
}

/**
 * Response headers.
 *
 * Filterable as a map so a site can add, drop or reword any of them in one place.
 *
 * TWO OMISSIONS FROM THE DEFAULT, BOTH DELIBERATE:
 *
 *   Strict-Transport-Security — the originals sent max-age=31536000 with
 *   includeSubDomains. HSTS is remembered by the browser for a year and applies to
 *   every subdomain; sending it from a theme, on a site that may not serve every
 *   subdomain over HTTPS, is a footgun that cannot be undone client-side. Add it at
 *   the server, once, knowingly.
 *
 *   Content-Security-Policy — too site-specific to default, and at least one site in
 *   the estate already sets it from an mu-plugin. Two agents on one header is worse
 *   than none.
 */
if (!function_exists('thetheme_send_security_headers')) {
    function thetheme_send_security_headers() {

        $headers = (array) apply_filters('thetheme_security_headers', [
            'Referrer-Policy'        => 'no-referrer-when-downgrade',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Permissions-Policy'     => 'geolocation=(), fullscreen=()',
        ]);

        foreach ($headers as $name => $value) {
            if ($value !== '' && $value !== false) {
                header($name . ': ' . $value);
            }
        }
    }
}
add_action('send_headers', 'thetheme_send_security_headers');

/**
 * User enumeration — three vectors, one switch.
 *
 * Ported from a consuming theme that had gone further than this package on 2026-09-02,
 * and kept close to its original because the reasoning was already measured there.
 *
 * This is a different concern from the author-archive redirect above, and stricter.
 * The redirect handles /author/<slug>/. The vectors below hand out a *username* to a
 * caller who does not have one yet, which is half a credential:
 *
 *   /?author=1                 canonical-redirects to /author/<slug>/, leaking the slug
 *   /wp-json/wp/v2/users       returns every account's name and slug
 *   /wp-json/oembed/1.0/embed  returns author_name for any public post
 *
 *     add_filter('thetheme_block_user_enumeration', '__return_false');
 */
if (!function_exists('thetheme_block_user_enumeration_enabled')) {
    function thetheme_block_user_enumeration_enabled(): bool {
        return (bool) apply_filters('thetheme_block_user_enumeration', true);
    }
}

/**
 * Hooked on parse_request so it lands before redirect_canonical() sees the query var.
 * Pretty author archives still resolve — they leak nothing to a caller who does not
 * already know the slug.
 *
 * REST is unaffected: core's rest_api_loaded() is on the same hook at the same
 * priority but registered first, so it serves and exits before this runs.
 */
if (!function_exists('thetheme_block_author_enumeration')) {
    function thetheme_block_author_enumeration($wp) {

        if (is_admin() || !thetheme_block_user_enumeration_enabled()) {
            return;
        }

        $author = $_GET['author'] ?? '';

        if ($author !== '' && preg_match('/^\d+$/', (string) $author)) {
            $wp->query_vars = ['error' => '404'];
        }
    }
}
add_action('parse_request', 'thetheme_block_author_enumeration');

/**
 * Anonymous callers lose the users endpoints. Authenticated ones keep them — the
 * block editor and several SEO plugins need them, so this must not be a blanket unset.
 */
if (!function_exists('thetheme_hide_rest_users')) {
    function thetheme_hide_rest_users($endpoints) {

        if (is_user_logged_in() || !thetheme_block_user_enumeration_enabled()) {
            return $endpoints;
        }

        unset(
            $endpoints['/wp/v2/users'],
            $endpoints['/wp/v2/users/(?P<id>[\d]+)']
        );

        return $endpoints;
    }
}
add_filter('rest_endpoints', 'thetheme_hide_rest_users');

if (!function_exists('thetheme_hide_oembed_author')) {
    function thetheme_hide_oembed_author($data) {

        if (!thetheme_block_user_enumeration_enabled()) {
            return $data;
        }

        unset($data['author_name'], $data['author_url']);

        return $data;
    }
}
add_filter('oembed_response_data', 'thetheme_hide_oembed_author');
