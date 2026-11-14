# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this project is

SBOanalytics is the **frontend/dashboard** half of a two-repo system for budget-friendly
web site and server analytics:

- **SBOanalytics** (this repo) — a PHP web app that reads from a MySQL database and
  renders dashboards, a log viewer, and OS/host metrics for site operators.
- **[SBOLogProcessor](https://github.com/SBOsoft/SBOLogProcessor)** (separate repo, not
  present here) — a backend log-processing daemon that tails Apache/web server access
  logs, computes metrics, and writes rows into the same MySQL database. This repo never
  writes access-log data itself; it only reads what the log processor produced.

There is no separate API backend language/runtime — PHP serves both the HTML/UI and the
JSON API from the same codebase.

## Tech stack (no build step)

- **PHP 8.x**, procedural style, no framework, no Composer, no autoloading.
- **MySQL 8.x** via PDO (`pdo_mysql`), prepared statements throughout.
- **Apache** + `mod_rewrite` for clean URLs (`.htaccess`), `mod_ssl` in the container image.
- **Frontend**: Vue 3 (global/CDN build, not SFCs), ECharts, Bootstrap 5 — all loaded from
  `cdnjs.cloudflare.com` via `<script>`/`<link>` tags in [webapp/DocumentRoot/ui/index.php](webapp/DocumentRoot/ui/index.php).
  Scripts are loaded per page (`$loadVue`/`$loadECharts` in `ui/index.php`): ECharts only on
  pages with charts (dashboard, os-metrics), Vue + `charts.js` on every page except the login form.
  `charts.js` must therefore not use `echarts` or `Vue` at load time, only inside functions.
  There is **no npm/package.json, no bundler, no transpilation step**. Vue components are
  plain JS objects defined in [webapp/DocumentRoot/ui/charts.js](webapp/DocumentRoot/ui/charts.js)
  and registered globally.
- No automated test suite exists in this repo today. Don't assume Jest/PHPUnit/etc. are
  configured — if adding tests, you'll be introducing the harness too.

## Directory layout

```
webapp/DocumentRoot/        # Apache DocumentRoot — this whole tree is web-servable
  .htaccess                 # URL rewrites + commented SetEnv config block (see below)
  common.php                # shared config constants, auth helpers, PDO wrapper functions
  api/
    index.php               # API dispatcher: reads ?act=, requires auth, routes to actions/
    actions/*.php            # one file per API action, included by api/index.php
  ui/
    index.php               # UI dispatcher/layout: reads ?act=, requires auth, includes page partials
    auth-form.php, auth-submit.php, dashboard.php, log-viewer.php, os-metrics.php, settings.php
    admin-auth-submit.php    # verifies the admin secret posted from settings, redirects back (no HTML)
    charts.js                # all Vue components (charts, log/metrics table views) + shared JS helpers
db/
  db-for-new-installations.sql   # consolidated schema for fresh installs
  upgrades/*.sql                  # timestamped migrations, must be run in filename order
containers/                # docker-compose dev environment (NOT for production — see README)
  web/, mysql/
.github/workflows/main.yml # manual-dispatch release workflow: zips DocumentRoot (+db, LICENSE) for GitHub Releases
```

## Request routing model

1. `.htaccess` rewrites clean paths like `ui/dashboard`, `api/metrics`, `ui/logs` into
   `ui/index.php?act=dashboard`, `api/index.php?act=metrics`, etc. If you add a new page
   or API action, you must add a matching `RewriteRule` in
   [webapp/DocumentRoot/.htaccess](webapp/DocumentRoot/.htaccess).
2. `ui/index.php` and `api/index.php` are the only two entry points. Each:
   - `include`s `common.php`, starts the session,
   - calls `SBO_CheckIsAuthenticated(...)` (skipped only for `auth-form`/`auth-submit`),
   - `switch`es on `$_REQUEST['act']` and `include`s the matching partial/action file.
   - In `ui/index.php`, form handlers that send headers or redirects (`auth-submit`,
     `admin-auth-submit`) are included and `return` **before** any HTML is written; page
     partials are included after the layout's `<head>`/nav, so they must not call `header()`.
3. Every partial/action file (in `ui/` and `api/actions/`) starts with:
   ```php
   if(!defined('SBO_FILE_INCLUDED_PROPERLY')){
       http_response_code(404);
       exit;
   }
   ```
   This is the guard that prevents direct browser access to the file, bypassing the
   dispatcher's auth check. **Any new partial/action file must include this guard as its
   first statement.**

## Configuration

All configuration is via environment variables, read with `getenv()` in `common.php` and
exposed as PHP constants. They're normally set via Apache `SetEnv`/`PassEnv` (see
`.htaccess` and `containers/web/sboanalytics.conf`), not committed secrets.

| Variable | Purpose |
|---|---|
| `SBO_AUTH_TYPE` | `none` (no auth) or `single` (one hardcoded user) |
| `SBO_DB_HOST`, `SBO_DB_NAME`, `SBO_DB_USER`, `SBO_DB_PASSWORD` | MySQL connection |
| `SBO_AUTH_SINGLE_USER`, `SBO_AUTH_SINGLE_PWD` | credentials when `SBO_AUTH_TYPE=single` |
| `SBO_ADMIN_SECRET` | separate shared secret gating `ui/settings.php` (domain/host admin) |

Docker dev environment (`containers/`) defaults to `SBO_AUTH_TYPE=none` — README explicitly
warns this setup is **not for production use**.

## Auth model

- Session-based (`$_SESSION`), two independent layers:
  1. **General auth** (`SBO_CheckIsAuthenticated`) — gates every UI page and every API
     action. Controlled by `SBO_AUTH_TYPE`.
  2. **Admin gate** (`SBO_CheckIfUserIsAdmin` + `SBO_CheckAdminCSRF`) — gates only
     `ui/settings.php` and the `admin-domain-save`/`admin-host-save` API actions,
     independent of `SBO_AUTH_TYPE`. Requires `SBO_ADMIN_SECRET` entered once per session,
     plus a per-session CSRF token (`$_SESSION['adminCSRFToken']`) on mutating POSTs.
- There is no per-user accounts system in the app layer today — `sbo_users` exists in the
  DB schema (with `password_hash`) but **no code in this repo queries it**. Treat it as
  reserved for a future multi-user feature, not as dead code to delete casually.

## Database conventions

- All tables are prefixed `sbo_`.
- PHP always talks to MySQL through the wrapper functions in `common.php`:
  `SBO_DB_Query($sql, $params, $limit, &$hasMore)` (returns rows as an array),
  `SBO_DB_InsertUpdateDelete($sql, $params, $returnLastInsertId=false)` and the lower level
  `SBO_DB_Execute($sql, $params)`. All use PDO prepared statements with named parameters
  — **never string-concatenate user input into SQL**. When a SQL fragment must vary by user
  input (e.g. `groupBy`, `keyName`), the established pattern is a `switch` on the input that
  appends **hardcoded** SQL fragments per case and rejects unknown values with a 400 (see
  `api/actions/metrics.php`, `logs.php`, `os-metrics.php`) — the raw input value itself is
  never interpolated into the query string, only bound as a parameter. For LIKE filters,
  escape the bound value with `SBO_DB_EscapeLike()` and use `LIKE :p ESCAPE '!'`.
- Database errors are thrown (PDO `ERRMODE_EXCEPTION`; an unreachable database throws a
  `RuntimeException` with code `SBO_ERROR_DB_UNAVAILABLE`). Don't check return values for
  `false`; catch only what you handle (e.g. `SBO_DB_IsDuplicateKeyError($e)` → 409) and
  rethrow the rest.
- Paged list endpoints respond via `SBO_API_StreamQuery($sql, $params, $limit, $page)` in
  `api/index.php`; the SQL must end with `LIMIT :limit OFFSET :offset` (values are set by the
  helper). It executes the query **before** writing any output, then streams
  `{"limit","page","data":[...rows, null],"hasMoreResults"}`. The data array always ends
  with a spurious trailing `null` by design; client JS in `charts.js` already accounts for
  this (`if(!row) continue;`).
- Results that charts or paging depend on must have an explicit `ORDER BY` — MySQL 8 does
  not sort `GROUP BY` results implicitly.
- `metric_type` in `sbo_metrics` is a numeric code whose meaning is defined by the
  **SBOLogProcessor** project, not documented in this repo's schema. Known codes in use by
  this app's UI (from `charts.js`/`dashboard.php`): `1`=total requests, `2`=bytes sent,
  `3`=status code, `5`=HTTP method, `6`=referer, `7`=path, `11`=UA family, `12`=UA OS,
  `13`=device type, `14`=is-human, `15`=request intent. Treat this mapping as convention,
  not schema-enforced — confirm against SBOLogProcessor before adding new codes.
- Migrations are plain numbered SQL files under `db/upgrades/`, named
  `YYYYMMDDNNNN-description.sql`. There is no migration-runner tool; operators run new
  files by hand, in filename order, after their currently-installed version. When adding a
  schema change, add a new upgrade file **and** update `db/db-for-new-installations.sql` to
  keep the consolidated fresh-install schema in sync.

## Coding conventions to follow

- 4-space indentation, procedural PHP (no classes), GPL-3.0 header comment block at the
  top of every source file (PHP and JS) — copy the existing header verbatim into new files.
- HTML output escaping: use `SBO_HtmlEncode()` (wraps `htmlspecialchars`) for any PHP value
  echoed into HTML outside of Vue templates. Values handed to inline `<script>` blocks for
  Vue's initial `data()` go through `SBO_JsonEncode()` (wraps `json_encode`). Inside Vue
  templates, prefer `{{ }}` text interpolation (auto-escaped) over `v-html`.
- New admin-mutating API actions must call both `SBO_CheckIfUserIsAdmin()` and
  `SBO_CheckAdminCSRF()` before touching the database, matching
  `admin-domain-save.php`/`admin-host-save.php`.
- API actions (`api/actions/*.php`):
  - Read paging with `SBO_API_GetPaging($defaultLimit)` (clamps limit/page to 1..1000) and
    time ranges with `SBO_API_GetDateTimeParam($name, $isEnd, $default)` +
    `SBO_API_CheckTimeRange()`. Invalid input → `SBO_API_ExitWithError(400, ...)`.
    Error bodies are always `{"error": ..., "description": ...}`.
  - `api/index.php` closes the session (`session_write_close()`) right after starting it so
    parallel requests don't serialize on the session lock. `$_SESSION` is still readable,
    but **API actions must not write to the session**.
  - `display_errors` is off for the API and uncaught exceptions become a generic JSON
    500/503 (`SBO_API_ExceptionHandler`); details go to the server error log only.
  - Allowed `timeWindowSizeMinutes` values are `SBO_ALLOWED_TIME_WINDOW_SIZES`
    (1, 5, 10, 15, 30, 60 — the set SBOLogProcessor supports); keep the UI `<select>` in sync.
- Frontend JS has no module system — everything is a global (`SBOBarChart`, `SBOPieChart`,
  `SBOLineChart`, `SBODetailedMetricsView`, `SBOLogsView`, `SBOChartMixin`, plus free
  functions prefixed `SBO_`). Keep new components/functions in `charts.js` following the
  same naming.
- ECharts components use `mixins: [SBOChartMixin]`: declare a `targetElementId` prop, put
  `ref="chartElem"` on the chart container and implement `buildOption(chartData)`. The mixin
  handles init, resize (ResizeObserver), disposal, `showChart()` and `showError()` — don't
  call `echarts.init` or `setOption` directly in components.
- Fetch API JSON with `SBO_FetchJson(url, fetchOptions)` (also for POSTs) and handle failures in
  its `.catch()` (it rejects on non-2xx and non-JSON responses, using the API's
  `error`/`description` as the message). Load the domain/host lists with
  `SBO_FetchList('../api/domains', 'domainId')`. Never call bare `fetch()` without error handling.
- Page filters live in the URL query string (`SBO_ReadUrlState` with validators /
  `SBO_WriteUrlState`), so views can be bookmarked and survive a refresh.
- Byte/minute values: `SBO_FormatBytes`/`SBO_FormatMinutes`; charts take `value-format="bytes"`.
  Values such as referers, paths and user agents are attacker-controlled: if you ever write an
  ECharts tooltip/label **function** formatter that returns HTML, escape them (string-template
  formatters and `valueFormatter` are escaped by ECharts).
- Time window values (`tw`, like `202505230729`) are server wall-clock times; convert them with
  `SBO_TimeWindowToDate`/`SBO_FormatDateAsTS`, which work in UTC so browser time zone/DST
  never shifts them. Don't mix in local-time `Date` getters/setters.

## Review artifacts

- [docs/security-review.md](docs/security-review.md) — security findings and recommendations from a full source review.
- [docs/performance-review.md](docs/performance-review.md) — performance/scalability findings and recommendations.

Both were produced from a static read-through of the codebase (no dynamic testing/pentest
was performed) as of 2026-07-08. Re-review after significant changes to `common.php`,
`api/`, `ui/`, or `db/`.
