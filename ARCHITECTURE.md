# ValuSafe — Architecture

**As of:** 28 September 2026 · Version 4.3.28 (all sections checked against the source before publication)
**Method:** reconstructed from the source, not from memory
**Audience:** developers who want to read, review or extend the code

---

## 1. What ValuSafe is

A self-hosted web application for cataloguing valuables in a private household. Users record items with a photo, purchase price, purchase date and location. From that it produces overviews, charts and exports — primarily for making a claim on household contents insurance.

It is **not SaaS**. Every installation runs on its operator's own webspace with its own database and credentials. What makes it unusual is less the domain than the **fleet management**: eleven production installations are compared and supplied with code from one central place.

## 2. Size

| Area | Files | Lines |
|---|---:|---:|
| Frontend root | 72 | 20,111 |
| Backend (admin) | 25 | 10,687 |
| Components | 6 | 2,642 |
| Language files (9 locales) | 10 | 10,371 |
| JavaScript (own; three libraries bundled besides) | 2 | 1,065 |
| CSS (13 theme stylesheets, 7 of them selectable) | 17 | 3,030 |

Roughly 48,000 lines of application code, counted in the published repository on 27 September 2026. Single author. Developed from late 2025 to now, with substantial AI assistance.

## 3. Technical foundations

**PHP 8.2 / 8.3**, MariaDB or MySQL 8, vanilla JavaScript. **No framework**, **no production dependencies**. The tests use PHPUnit as a development dependency; tests and `composer.json` live in the development repository and are not part of the published one.

That is a constraint rather than an oversight: the application has to run on plain shared hosting, without Composer, without Node, without shell access. Installing it means "upload the files, create `config.php`, import the schema".

**No external CDNs.** Chart.js, ZXing, the QR library and Tabler Icons are all served locally. The Content Security Policy permits no foreign hosts. This was a deliberate choice against third-party tracking and in favour of offline capability.

**What this means for a reader:** there is no router, no controller layer, no DI container. Every `.php` file in the web root is an entry point the web server executes directly.

## 4. Request lifecycle

There is no front controller. A typical page looks like this:

```php
require_once 'db.php';        // <- this is the actual bootstrap
require_once 'helpers.php';
requireLogin();
// ... page logic ...
```

`db.php` is the linchpin. On inclusion it does the following, in this order:

1. Load `config.php` (credentials, constants, session parameters)
2. Load `security.php` and `SecurityHeaders.php`
3. Emit security headers — CSP, HSTS, X-Frame-Options, Referrer-Policy
4. Start the session
5. Check session timeout
6. Open the PDO connection (`ERRMODE_EXCEPTION`, `EMULATE_PREPARES = false`)
7. Load the language file, populate `$translations`
8. Construct the global `$db` instance

After that, `$pdo` (raw) and `$db` (wrapper) plus every auth and permission function are available as globals.

**`config.php` is not in the repository.** It is instance-specific, written by the setup wizard, and gitignored. `db.php` is in the repository, and anyone reading the code needs to know that the central functions — `requireLogin()`, `hasPermission()`, `canEdit()`, `isAdmin()`, `isSuperAdmin()`, `validateRequest()` and `t()` — all live in `db.php`.

## 5. Directory layout

```
wert/                       application root; every .php is an entry point
├── db.php                  bootstrap, auth, permissions, DB wrapper
├── config.php              credentials and constants                  [not in git]
├── security.php            CSRF, session fingerprint, upload checks, password hashing
├── SecurityHeaders.php     CSP, HSTS and the rest of the HTTP headers
├── helpers*.php            formatting, images, search, pagination, column management
├── index.php               main list — filters, sorting, pagination, bulk actions
├── add.php / edit.php      item capture and editing
├── export_*.php            PDF, CSV, HTML, insurance and handover reports
├── version_agent.php       reports file checksums to the fleet hub   [see section 9]
├── ajax/                   small JSON endpoints
├── backend/                admin UI with its own access guard
│   ├── config.php          loads ../config.php and ../db.php; application code, not credentials
│   ├── layout/             the page frame currently in use
│   ├── users.php           users and roles
│   ├── permissions.php     permission matrix
│   ├── backup.php          backup (DB, images, PHP files)
│   └── restore.php         restore
├── components/             reusable UI blocks
├── lang/                   9 language files, ~980 keys each
├── css/                    13 theme stylesheets (7 selectable) plus tokens and WCAG rules
├── js/                     own JS plus locally hosted libraries
├── upload/                 user photos       [protected by .htaccess]
└── documents/              user documents    [protected by .htaccess]
```

## 6. Data model

The core table is `wertsachen` (items). Relevant columns:

`id`, `name`, `kategorie_id`, `raum_id`, `standort_id`, `position_id`, `kaufdatum`, `preis`, `aktueller_wert`, `aktueller_wert_datum`, `notizen`, `bild`, `barcode`, `versicherung_id`, `hidden`, `schadenfall`, `oeffentlich`, `public_token`, `sort_order`, `erstellt_von`, `erstellt_am`, `geaendert_am`, `gelistet_am`, plus two free-form fields (`custom1_wert`/`custom1_typ`, `custom2_wert`/`custom2_typ`).

Other tables: `users`, `kategorien`, `raeume`, `standorte`, `positionen`, `dokumente`, `item_images`, `wert_historie`, `versicherungen`, `app_settings`, `activity_log`, `user_activity`, `security_log`, `login_attempts`, `rate_limit`, `permissions`, `password_resets`, `schema_migrations` — 19 in all.

**Locations — resolved in September 2026.** Until then three models for
locations coexisted, and `wertsachen.raum_id` was resolved against two different
tables depending on where you looked: `raeume` in the item list and the entry
forms, `orte` in every export, the dashboard and the quick-add form. Where the
two tables disagreed, an item's room could read differently in the insurance PDF
than on screen.

| Model | Tables | Status |
|---|---|---|
| Old | `orte` | read paths moved to `raeume` in 4.3.24; table dropped in 4.3.25 |
| Current | `raeume`, `standorte`, `positionen` | the only model |
| Abandoned | `standort_raeume`, `standort_positionen` | never read by any code; room names merged into `raeume`; dropped in 4.3.25 |

All 22 read paths now resolve against `raeume`. Before the change it was measured
across all eleven installations that every `raum_id` in use existed in `raeume`,
so no room name could be lost. Note that `positionen` belong to `standorte`, not
to rooms: the column is named `raum_id` but carries a `standort_id` — an old
misnomer, not a bug.

Since 4.3.25 an item's location is stored in `wertsachen.standort_id` even
without a position; when a position is chosen, the location follows from it.
The old tables and `wertsachen.ort_id` are removed by migration 011 on
existing databases and are no longer in `install/schema.sql`.

**The schema is in version control.** `install/schema.sql` is the blueprint both
the setup wizard and the Docker build create the database from.

## 7. Permission model

Three roles: `admin`, `edit` (also `editor`), `read`.

On top sits a fine-grained permission matrix in the `permissions` table, with keys such as `items_edit`, `items_delete`, `bulk_hide`, `docs_view`, `export_pdf`. Resolution:

```php
hasPermission('items_delete')
  → isAdmin() ? true                                  // admins bypass everything
  : permissions table present ? edit_can/read_can column by role
  : _permissionFallback()                             // hardcoded defaults
```

The fallback applies when the table is missing, so an installation without the migration stays usable instead of locking everyone out.

Two specifics:

**`sieht_alle`** ("sees everything") — a per-user flag. When the `only_own_items` setting is active, non-admins without this flag see only records whose `erstellt_von` matches their username.

**`SUPERADMIN_USERNAME`** — a constant in `config.php`, a comma-separated list. SuperAdmins can disable certain capabilities for ordinary admins (backup, restore, file download).

## 8. Security layers

| Layer | Implementation |
|---|---|
| Passwords | Argon2id via `password_hash()` |
| SQL | prepared statements throughout, `EMULATE_PREPARES = false` |
| CSRF | `Security::getCSRFInput()` in the form, `validateRequest()` in the POST branch |
| Session | `session_regenerate_id(true)` on login, timeout, fingerprint from network prefix + user agent |
| Brute force | `RateLimiter` — 5 failed attempts per IP address, then a 15-minute lockout |
| 2FA | own `TOTP` class per RFC 6238, no third-party library |
| Uploads | MIME check via `finfo`, extension allow-list, re-encoded as JPEG |
| HTTP headers | CSP with no foreign hosts (but `'unsafe-inline'` for scripts and styles), HSTS, `frame-ancestors 'none'`, `form-action 'self'` |
| Directories | `.htaccess` locks `upload/`, `documents/`, config files |

**Caveat:** the `.htaccess` layer does not exist on the NAS instance, which runs Nginx. An equivalent Nginx rule set is still missing.

The session fingerprint deliberately truncates the IP to its network — IPv4 to /24, IPv6 to /64 — so that an address change within the same provider network does not log the user out, while a move to a foreign network does.

## 9. The fleet

*(Host names are replaced by example names in the published version of this document.)*

The genuinely interesting part. Eleven production installations across three hosting providers plus a NAS, managed from `hub.example`.

```
                    hub.example
                  (control hub, not a ValuSafe instance)
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
  version_check.php    file_sync.php        sync.php
   version compare      code distribution    DB / image sync
        │                   │                   │
        └──────────┬────────┴─────────┬─────────┘
                   ▼                  ▼
          version_agent.php     sync_agent.php
          (per instance)        (per instance)
                   │                  │
        ┌──────────┴──────────────────┴──────────┐
        ▼                                        ▼
   master.example          10 further instances
   all-inkl · MariaDB                  Lima-city, Synology NAS
```

**`version_agent.php`** returns MD5, size and mtime of every file with one of nine extensions (`.php`, `.js`, `.css`, `.html`, `.md`, `.json`, `.xml`, `.svg`, `.pdf`) as JSON; content directories such as `upload/` and `vendor/` are skipped. Files meant to differ on one instance are listed in `VERSION_OVERRIDES` in that instance's `config.php`. It is the only fleet file in the published repository; the sync agent and the hub are not.

**`sync_agent.php`** is the workhorse endpoint: `ping`, `list_files`, `read_file`, `write_file`, `delete_file` for code sync, plus `status`, `export`, `import` for the database and `image`/`upload` for pictures. It recognises two separate tokens — one for file operations, one for database operations.

**How a code deployment runs:** `file_sync.php` fetches the master's file list, compares it against the target instance, pulls changed files from the master via `read_file` and writes them to the target via `write_file`. After writing, the MD5 is verified — a file counts as transferred only if it matches.

**Hardening:** since 26 August 2026 every instance has its own random tokens, sourced exclusively from its `config.php`. Previously a shared token was hardcoded in the source. The agent now refuses service when the constants are missing rather than falling back to a default. Since September 2026 the hub sends tokens in a request header rather than in the URL, where every web server would have logged them.

**Two quirks worth knowing:**

`sync_agent.php` refuses to overwrite itself, `version_agent.php` or any `config.php` — those go to each instance over FTP.

Lima-city runs a WAF that blocks POST bodies containing security-related keywords. The sync therefore transmits a list of sensitive files (`security.php`, `LoginAttempts.php`, …) base64-encoded.

## 10. Frontend

Server-rendered HTML with JavaScript sprinkled in where needed. No build step, no bundler, no transpilation — what is in the repository is what runs in the browser.

**Two UI generations.** The older one ("classic") and the newer one ("Next Interface" — 52-pixel icon rail, masonry grid, split-pane detail view, mobile bottom navigation). You can tell them apart by which frame a page includes: 37 files use `header_next_page.php`, only one — `index.php` — still uses the old `header_next.php`. In the backend all 21 pages use `layout/header_next_page.php`.

**Internationalisation.** Nine locales (DE, EN, FR, TR, ES, IT, NL, PL, PT), around 980 keys each, accessed via `t('key')`. When a key is missing, `t()` returns the key itself — so a missing translation surfaces as a raw identifier in the UI rather than as empty text. As of 27 September 2026 no key is missing in any language; `bin/check_lang.py` in the development repository checks this.

**PWA.** Manifest, offline page, service worker with cache-first for static assets and no caching for PHP.

Until 4.3.5 there were *two* service workers here: `footer_next.php` and `footer_next_page.php` register `/sw.js` — the real one; `js/main.js` additionally registered `/service-worker.js`, a 299-byte stub that passed every request straight through. Both claimed the same scope `/`. Since `js/main.js` had not been included by any page since the Next Interface, both files were removed in 4.3.5, leaving `/sw.js`.

## 11. Tests, build, deployment

**Tests:** PHPUnit 10, three files, 38 test methods, kept in the development repository. They cover pure-logic helpers — path normalisation in the sync, the rate limiter, migration helpers. **No test touches authentication, the database or a controller.** Coverage of the business core is zero. This is being addressed.

**Build:** none. `composer install` is needed only for the tests.

**Deployment:** files go to the master via FileZilla, and from there to the fleet via `file_sync.php`. Database changes are versioned migrations in `backend/migrations/`. Since September 2026 the hub runs them on every instance through `migration_agent.php`, with a dry run first; a standalone installation runs them itself with `migrate.php`.

## 12. Known issues

Honest and complete, in descending order of importance:

Honest means current: every item below was re-checked against the source on
25 September 2026, and the ones that had been fixed are marked as such rather
than quietly deleted. A list of known weaknesses that nobody works through is
a list of open doors with directions attached — item 12 below proved exactly
that, four weeks after it was written down. The list was checked again on
27 September 2026.

**Still open**

1. **No test coverage of the business core.** Three test files cover migration helpers, the rate limiter and the sync helpers. Nothing covers items, permissions or exports. The tests are not part of the published repository.
2. **No `.htaccess` equivalent on the NAS instance** (Nginx). Directory protection there rests on the server configuration, not on files shipped with the application.
3. **No CSRF protection on the control hub.** Mitigated by `SameSite=Strict`, but not enforced in code. The hub is not part of the published package.
4. **Two registration sites for one service worker** — `footer_next.php` and `footer_next_page.php` both register `/sw.js`. Not two workers, as this list claimed until 25 September 2026, but a duplication that will diverge sooner or later.

**Fixed since this document was first written**

5. ~~Three orphan tables.~~ `orte`, `standort_raeume` and `standort_positionen` were dropped in 4.3.25 — from `install/schema.sql` and, by migration, from all eleven installations.

6. ~~Three coexisting location models.~~ Since 24 September 2026 every read path resolves `wertsachen.raum_id` against `raeume`. The measurement before the change: no item on any of the eleven installations pointed at a room that `raeume` did not know.
7. ~~Schema not in version control.~~ `install/schema.sql` is versioned and is what both the setup wizard and the Docker build create the database from.
8. ~~Role switching has no effect.~~ `isAdmin()` reads the current role; a separate function reads the original one, for the few places that must stay reachable during a switch.
9. ~~A failed master fetch in the file sync is reported as "everything up to date".~~ Fixed 16 September 2026: a transport error is now reported as one, never as a verdict.
10. ~~TLS certificate verification disabled project-wide.~~ Fixed 15 September 2026 across sixteen call sites. The justification for switching it off — a self-signed certificate on the NAS — turned out to be wrong when measured.
11. ~~An unused second database connection.~~ Removed in 4.3.3.
12. ~~`public.php` sends email without rate limiting, and its POST branch runs before the token check.~~ Fixed 25 September 2026 — four weeks after it was written down here, and only because this document was being prepared for publication. `bin/public_reihenfolge_pruefen.php` now guards the order.

## 13. Where to start reading

For a first pass, in this order:

1. **`db.php`** — bootstrap, auth, permissions, DB wrapper. Understand this file and you understand 80 percent of the conventions.
2. **`index.php`** — the most complex page: filters, sorting, pagination, bulk actions, visibility rules.
3. **`migrate.php`** with **`backend/migration_runner.php`** — how a database change is applied, by one installation itself or across the fleet.
4. **`version_agent.php`** — short and self-contained; the instance side of the fleet's version comparison. The sync agent and the hub are not part of the published repository.
5. **`security.php`** — CSRF, fingerprint, upload validation.

For a critical eye, the most rewarding read is `helpers.php` (1,516 lines, grown organically).

---

*This document describes the state as of 28 September 2026, version 4.3.28. It was reconstructed from the source and checked against it before publication; where claims could not be verified — in particular regarding `config.php`, which is not in the repository — this is marked.*
