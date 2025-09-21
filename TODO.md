# PU239\_original — TODO 2025 (Modernized from 2018 legacy list)

*Status*: September 21, 2025  • Principles: **Modernization Dogma (hardcore)** 2025‑09‑19.

---

## 0) Approach to the 2018 TODO

The 2018 list is treated as **historical context only**. Each item has been triaged as **KEEP**, **REPLACE**, **DROP**, or **DECIDE**. All new work must follow the modernization dogma (PHP 8.3, Aura/ExtendedPDO, DI, no legacy globals, CI gates).

Legend:

* **KEEP**: Still relevant – implement with a modern solution.
* **REPLACE**: Same intent, but updated technology/flow in 2025.
* **DROP**: Out of scope or obsolete.
* **DECIDE**: Requires product/feature decision.

---

## 1) Triage Matrix

### Tracker Core & Correctness

* **Peers speed shows 0** → **KEEP** (announce/scrape parsing + DB writes; no cache on peers).
* **Remove all caching from peers table** → **KEEP** (write‑hot table, direct DB writes only).
* **Announce times\_completed increment** → **KEEP** (validate state machine).
* **Thanks check / merge thanks & thankyou** → **KEEP** (schema unification + bonus logic).
* **Freetorrent/doubletorrent flags** → **KEEP** (single source in metadata).
* **Torrent client ban pages** → **KEEP** (admin UI + enforcement).
* **Cheater‑check script (random chunk validation)** → **DECIDE** (needs feasibility check; heuristic alternative possible).
* **xbt / ocelot** → **DECIDE** (decide strategy: support one, drop both, or replace with lightweight tracker module).

### Auth, Sessions, Security, Compliance

* **Replace authentication system** → **REPLACE** (modern password hashing, optional 2FA, hardened sessions).
* **Replace session handler** → **REPLACE** (secure cookies, SameSite, redis‑backed store).
* **IP login/seedbox restrictions** → **KEEP** (policy + UI + logging).
* **Cookie consent lifetime bug** → **REPLACE** (GDPR‑compliant CMP with persistence).
* **Remove global \$mysqli / \$CURUSER** → **KEEP** (already enforced; DI + request‑scoped context).
* **CSRF & redirect headers** → **KEEP** (unified redirects, CSRF protection for all POST/PUT/DELETE).

### Accounts, Users & UI

* **Update users "Pending" state** → **KEEP** (activation flow).
* **Replace homespun user CRUD** → **REPLACE** (service layer + transactions).
* **User paranoia info** → **KEEP** (granular visibility controls).
* **User avatar block only in userdetails** → **DECIDE** (extend to other areas?).
* **Breadcrumbs** → **KEEP** (componentized).
* **Replace page refresh with AJAX** → **REPLACE** (progressive enhancement via HTMX/fetch).
* **Add live search (typeahead)** → **REPLACE** (Meilisearch/OpenSearch backend).
* **Top 10 stats daily/weekly/monthly** → **KEEP** (new aggregations + caching).
* **Forum topic rating** → **DECIDE** (check relevance in 2025).

### Content, Media & APIs

* **Lyrics API (Musixmatch), Music API (Spotify/Last.fm)** → **DECIDE** (licensing & usefulness).
* **Replace PayPal with Stripe** → **DECIDE** (only if payments are in scope).

### Admin & Maintenance

* **Report comments bug** → **KEEP**.
* **Fix iphistory.php** → **KEEP** (rewrite admin tool).
* **Update admin/shit\_list.php** → **KEEP**.
* **Finish offers, requests, upcoming, bot replies** → **DECIDE** (scope review).
* **Finish inbox/messages cache** → **KEEP** (fix invalidation).
* **Fix birthday cleanup / karma cleanup** → **KEEP** (cron tasks).
* **Blocks: cooker/recipes/notifications** → **DROP** (legacy artifacts).
* **Parallax scrolling in Firefox** → **DROP** (not relevant; modern CSS fallback if needed).

### Search

* **Replace MySQL full‑text with Elasticsearch** → **REPLACE** (Meilisearch or OpenSearch).

### Performance & Cache

* **Update queries for user: cache strategy** → **REPLACE** (read via cache layer, write through DB + invalidate).
* **Replace cache deletes with targeted updates** → **KEEP**.
* **Document cache userstatus** → **KEEP** (simplify & standardize).

---

## 2) 30/60/90 Day Roadmap

### Days 0–30: Foundation & correctness

1. **Config & DB consolidation**

   * Integrate dynamic settings (bonus.on, expires.user\_cache) into ConfigRepository.
   * Remove all `$mysqli`/`$CURUSER` globals.
2. **Tracker correctness package**

   * Fix peers speed = 0, remove peers cache reads.
   * Validate announce/scrape state (`times_completed++`, flags).
   * Merge thanks/thankyou + bonus pipeline.
3. **Security hardening**

   * Sessions (httponly, samesite, secure, rotation).
   * CSRF enforcement everywhere.
   * Redirect consistency.
4. **Admin bugfix trio**: iphistory.php, report comments, shit\_list.

### Days 31–60: Auth/UX/Cache

5. **Auth refresh**

   * Argon2id hashing, rehash on login, optional TOTP.
   * Redis session store.
6. **Cache layer**

   * Document `userstatus`.
   * Targeted cache invalidation strategy.
7. **UI improvements**

   * Breadcrumb component.
   * Progressive enhancement for clickable actions.
   * Top10 stats with multiple timeframes.

### Days 61–90: Search/Notifications/Policies

8. **Search upgrade**

   * Meilisearch POC (index torrents + users).
   * Live typeahead API.
9. **Notifications & messaging**

   * Finish inbox/messages cache.
   * Central notifications bus.
10. **Policy & compliance**

* GDPR cookie consent persistence.
* IP/seedbox restrictions.

---

## 3) Work Packages

**WP‑01 Tracker peers & completion**

* Remove peers cache reads; direct DB writes.
* Fix speed parsing.
* Integration tests for `times_completed` and edge cases.

**WP‑02 Thanks/bonus consolidation**

* Merge tables, migrate data.
* Fix config lookups.
* Add audit log + metrics.

**WP‑03 Auth & session**

* Argon2id hashing + TOTP.
* Redis sessions with rotation.

**WP‑04 Admin fixes**

* Rewrite iphistory.php, report comments, shit\_list using service layer.

**WP‑05 Search & typeahead**

* Deploy Meilisearch/OpenSearch, index data.
* UI typeahead integration.

**WP‑06 Notifications & messaging**

* Event‑based notifications system.
* Cache invalidation documented.

**WP‑07 Compliance**

* GDPR‑compliant cookie consent.
* IP/seedbox policy enforcement.

---

## 4) Explicit DROPs

* Parallax fix in Firefox.
* Cooker/recipes features.
* Stripe integration (only if payments become scope).

---

## 5) DECIDE Items

* Anti‑cheat: random chunk vs heuristic.
* XBT vs Ocelot strategy.
* Lyrics/music API (value vs licensing).
* Forum topic rating (do we need it?).
* Avatar block expansion beyond userdetails.

---

## 6) Core Rules

* **Peers table**: no cache reads, DB writes only, events exported to metrics.
* **Cache invalidation**: targeted only, no blanket deletes, documented TTL per namespace.
* **Config**: all config via ConfigRepository, no raw `$site_config`.
* **Globals**: forbidden (`$mysqli`, `$CURUSER`).
* **Security**: CSRF required for all state‑changing actions, strict cookies, uniform redirects.

---

## 7) Changelog (ongoing)

* [ ] (to be filled as PRs merge)

---

# TODOs — PU239 Modernization 2025

Consolidated overview of known TODOs, regressions, and migration issues. Updated continuously as new findings are reported.

---

## ConfigRepository regression

**Date:** 2025-09-21
**Source:** \[P1] Bonus awards

### Problem

* `ConfigRepository` only loads static files from `config/*.php`.
* DB‑based settings from `Settings::get_settings()` are missing.
* Current lookups return `null`:

  * `bonus.on`
  * `bonus.per_thanks`
  * `expires.user_cache`

### Impact

* `(bool) null → false` → seed bonus on thanks is always **disabled**.
* `(int) null → 0` → user cache TTL becomes **0** (no caching).

### Resolution options

1. Extend `ConfigRepository` to merge in DB settings.
2. Define missing keys in `config/*.php` with defaults.
3. Add feature‑level fallback when `null` is returned.

---

## ConfigRepository lacks HnR settings data

**Date:** 2025-09-21
**Source:** \[P1] Hit‑and‑Run thresholds

### Problem

* Refactor now pulls HnR thresholds from `ConfigRepository` (e.g. `foreach ($config->get('hnr_config') …)`).
* `ConfigRepository` only has static config from `config/*.php`.
* DB‑backed `hnr_config` entries that `Settings::get_settings()` merged into `$site_config` are missing.

### Impact

* `get('hnr_config')` returns null → loop never persists updates.
* Form shows empty values and cannot save.
* All lookups return null/false, breaking feature.

### Resolution options

1. Continue reading from `$site_config` or `Settings` service for this feature.
2. Extend `ConfigRepository` to hydrate with DB‑backed `hnr_config` data.

---

## ConfigRepository lookup bypasses blackjack access control

**Date:** 2025-09-21
**Source:** \[P0] Game access guard

### Problem

* Guard now uses `$config->get('allowed.play')` and `$config->get('class_names')`.
* `ConfigRepository` lacks these dynamic arrays (they are populated in `$site_config` by `include/bittorrent.php`).

### Impact

* `get('allowed.play')` returns null → check becomes `$user['class'] < 0`, never triggers.
* Any user can play blackjack regardless of class.
* `get('class_names')` returns null → indexing could fatal.

### Resolution options

1. Keep reading from `$site_config` for dynamic arrays.
2. Extend `ConfigRepository` to include dynamic arrays from bootstrap.

---

## Initialize ConfigRepository after bootstrapping container

**Date:** 2025-09-21
**Source:** \[P1] Fatal ordering issue

### Problem

* Code calls `$config = $container->get(ConfigRepository::class);` **before** including `bootstrap_web.php`.
* `$container` is created inside the bootstrap.

### Impact

* `$container` is undefined → fatal error on page load.

### Resolution

* Move bootstrap include before ConfigRepository call.
* Or delay lookup until after bootstrap executes.

---

## Bootstrap before using DI container

**Date:** 2025-09-21
**Source:** \[P1] Undefined container usage

### Problem

* `$config = $container->get(ConfigRepository::class);` runs before bootstrap.
* `$container` is created in bootstrap, so call fails.

### Impact

* Fatal error as soon as page executes.

### Resolution

* Always include bootstrap before container lookups.

---

## Initialize ConfigRepository after bootstrap

**Date:** 2025-09-21
**Source:** \[P1] Admin load page

### Problem

* Page grabs `$config = $container->get(ConfigRepository::class)` before bootstrap.
* Container is created inside bootstrap.

### Impact

* Undefined variable `$container` → fatal.

### Resolution

* Include bootstrap before accessing ConfigRepository.
* Ensure admin load pages don’t access container prematurely.

---

*(more items will be added here as they are discovered)*
