# PU-239 (Original) — Modernization 2025

> **Status:** Ongoing refactor of the legacy PU-239 codebase into a 2025-compliant, secure, and maintainable system.  
> **Branch:** `modernization-2025`

---

## 🎯 Goal

This branch aims to modernize the *original* PU-239 tracker codebase — not to rebuild functionality, but to re-engineer it with clean, modern PHP 8.3 code while preserving all behavior and data.

The modernization is done **incrementally**, file by file, using deterministic conversion rules and automated tooling (Codex-safe handler converter, Rector, PHPStan, and lint gates).

---

## 🧩 Core Principles

1. **Legacy quarantine**
   - All procedural code using `mysqli_*`, `sql_query`, `sqlesc`, or manual `require_once` is isolated to `/quarantine/`.
   - No legacy code is executed directly after conversion; it remains as historical reference only.

2. **Strict PHP 8.3 / PSR-12**
   - All new code must declare `strict_types=1`.
   - Namespaces follow PSR-4 with `PU239\…`.
   - Dependency injection replaces globals.

3. **Database layer**
   - Rebuilt around `Aura\ExtendedPDO` (through DI container).
   - No manual string concatenation — only prepared statements.
   - Legacy `$db` and `$site_config` replaced with `ConfigRepository` and container services.

4. **Admin first**
   - Admin area serves as testbed for the new container, permissions, and UI flow.
   - “Admin green” milestone marks readiness for broader migration.

5. **Handlers modernization**
   - Every legacy handler stub (previously calling `require public/*.php`) is refactored into a class-based handler.
   - Each handler now has:
     ```php
     public function handle(array $meta = []): void
     ```
     with structured config and error guards.
   - Safe mappings are inserted; ambiguous logic remains TODO-flagged.

6. **Frontend modernization**
   - Bundling via **Vite / esbuild** only.
   - jQuery, CDN assets, and inline scripts are being phased out.

7. **CI/CD gates**
   - Build fails if legacy functions appear outside quarantine.
   - Rector + PHPStan enforce PSR-12 and strict typing.
   - Automated PHP-lint and minimal unit test suite as final gate.

---

## 🗂 Conversion tooling

**Handler Converter (Codex Safe Mode)**
- Converts verified stubs into modern handlers.
- Runs in small batches (`batch_size=5`) to limit blast radius.
- Creates `.bak` backups, performs self-lint, appends results to:
  - `tools/handler_convert_report.csv`
  - `tools/handler_convert_state.json`
- Maintains exact markers in `tools/_handler_convert_markers.php`.

All runs are **idempotent**: a file stamped with  
`// AUTO_CONVERT_ATTEMPTED: … rules=2025.10.22`  
will never be processed again in this rules version.

---

## 🧱 Roadmap Milestones

| Phase | Focus | Status |
|-------|--------|--------|
| **1. Baseline & Dependencies** | PHP 8.3, Composer cleanup, container bootstrap | ✅ Done |
| **2. Database Migration** | mysqli → ExtendedPDO, ConfigRepository injection | 🟡 In progress |
| **3. Handlers Refactor** | Codex batch conversion of stubs | 🟢 Active |
| **4. Quarantine Cleanup** | Remove legacy once replacements are verified | ⏳ Next |
| **5. Frontend Modernization** | Replace jQuery/CDN, add Vite/esbuild | ⏳ Planned |
| **6. CI/CD & Testing** | Rector/PHPStan/test gates | ⏳ Planned |

---

## 🧰 Tech Stack

- **Language:** PHP 8.3  
- **Database:** MySQL 8 (strict mode)  
- **ORM/DB:** Aura ExtendedPDO  
- **Config:** PU239\Config\ConfigRepository  
- **Frontend:** Vite + esbuild  
- **Lint/Analysis:** PHPStan (high), Rector, PSR-12 style  
- **Hosting:** Render.com (port 8000)

---

## ⚙️ Developer Notes

- Always run conversions in small batches (`batch_size = 5`).
- Never modify `.bak` files manually — they serve as rollback snapshots.
- Only commit when `php -l` passes for all changed files.
- When starting a new conversion rule set, bump the `rules_version` (e.g. `2025.11.05`) and set `REWORK_MODE=true`.

---

## 🧾 License

This modernization work inherits the original PU-239 license.  
All newly written code is © 2025 Thomas Højegaard / EL-TECH / PU-239 team.
