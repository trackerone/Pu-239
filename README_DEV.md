# PU-239 (Original) – Development README

> ⚡ **Modernizing the PU-239 codebase to 2025 standards**  
> This document tracks the current progress and development guidelines for the ongoing refactoring of the legacy PU-239 tracker codebase.  
> The goal is to transform the existing PHP 5.x/7.x era code into a clean, stable and secure PHP 8.3 platform with proper DI, CI/CD, and modern tooling — while preserving core functionality.

---

## 🚧 Current Progress (October 2025)

The modernization work is organized into **tracks** (“Spor”) and **batches**.  
As of now, the following tracks have been completed:

| Track | Status | Description |
|-------|--------|-------------|
| 1 | ✅ Done | Baseline, dependencies, PHP 8.3, Composer/NPM cleanup |
| 2 | ✅ Done | Database layer (PDO via DI container), mysqli quarantined |
| 3 | ✅ Done | Cleanup scripts, cron/bootstrap fixes, static guards |
| 4 | ✅ Done | Practical database migration & quarantine cleanup |
| 5 | ✅ Done | Admin refactor begins, auto-merge pipelines |
| 6 | ✅ Done | “Admin green” – all critical admin features now use the new DB layer |
| 7 | ✅ Done | Preparation for frontend modernization (Vite/esbuild, no jQuery) |

> 🟡 **Next step:** Track 8 — Quarantine cleanup and module-by-module rebuilds.  
> Focus shifts towards frontend and user-facing modules once admin is stable.

---

## 🧭 Modernization Doctrine (Hard Rules)

The project follows a strict migration strategy:

1. **Legacy → Quarantine**  
   All `mysqli_*`, `sql_query`, `sqlesc`, `function_*.php`, manual `require_once` → moved to `_quarantine/`.

2. **New Code Only**  
   PHP 8.3, Aura/ExtendedPDO via DI container, PSR-12, strict types.  
   No global `$db`. Only prepared statements.

3. **Rebuild > Quickfix**  
   Modules are rebuilt in a modern structure. Once green → legacy files are permanently removed.

4. **Admin First**  
   Admin is the testbed for DI container, permissions, and UI flow.

5. **Frontend Modernization**  
   Vite/esbuild bundling, no jQuery/CDN. Modular JS/CSS.

6. **CI/CD Gates**  
   - ❌ Forbidden: `mysqli_*`, `sql_query`, `sqlesc`, `function_*.php`  
   - ✅ Required: PHPStan, Rector, linting, basic tests must pass before merge.

7. **Workflow**  
   Quarantine → Rebuild → Replace → Delete.  
   The repo may look messy during migration — the end goal is a clean, unified 2025-ready release.

---

## 🛠️ Local Setup / Render Deployment

The project currently runs on Render (container, port 8000).  
For local development:

\`\`\`bash
composer install
npm install
npm run build
cp .env.example .env   # Adjust DB credentials etc.
php -S localhost:8000 -t public
\`\`\`

During deployment, the following scripts handle bootstrapping and caching:

- `detect-root.sh`  
- `ensure-skeleton.php`  
- `entrypoint.sh`  

They ensure `bootstrap/app.php`, `public/index.php`, and writable directories (`storage`, `bootstrap/cache`) are properly set up.

---

## 📋 Notes

- All TODOs and issues are tracked in `todo.md` and GitHub PR comments.  
- Codex is used for auto-merging and lint checks.  
- **BBCode is being fully removed.** All user input fields will support secure Markdown instead.

---

## 🪄 Roadmap (Short)

- [x] Tracks 1–7: Foundation & Admin
- [ ] Tracks 8–10: Module cleanup and frontend modernization
- [ ] Tracks 11+: CI/CD gates, test coverage, release pipeline
- [ ] Stable 2025 release: fully modernized PU-239 tracker

---

## 📅 Last updated
October 3, 2025
