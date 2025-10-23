# PU-239 — Modernization 2025 To-Do List

_Revision: 2025-10-23_

---

## 🔹 Active Work

- [x]  Establish modernization branch `modernization-2025`
- [x]  Finalize quarantine rules and disallowed legacy functions
- [x]  Implement Codex-safe handler converter (batch mode)
- [x]  Add state ledger (`tools/handler_convert_state.json`)
- [x]  Add conversion report (`tools/handler_convert_report.csv`)
- [x]  Enforce lint check per file (.bak rollback)
- [x]  Stabilize markers file (`tools/_handler_convert_markers.php`)
- [ ]  Final manual review of SKIPPED_COMPLEX handlers
- [ ]  Rebuild Admin UI fully on DI container
- [ ]  Verify ConfigRepository coverage across all modules
- [ ]  Complete quarantine purge once replacements exist

---

## 🔸 Next Milestone — “Admin Green”

1. Ensure all admin modules run 100 % on the new DB layer (no legacy calls).  
2. Inject ConfigRepository consistently.  
3. Replace global `$db` and `$site_config` usages in admin area.  
4. Pass Rector + PHPStan on the full admin tree.  
5. Tag release `admin-green-2025`.

---

## 🔸 Following Milestone — “Frontend Modern”

- Remove remaining jQuery/CDN assets.
- Add Vite/esbuild build pipeline.
- Introduce modular JS imports and modern CSS.
- Begin replacing inline scripts with ES modules.
- Add ESLint + Prettier for JS consistency.

---

## 🔸 Final Milestone — “Stable 2025 Release”

- [ ]  CI/CD integration (GitHub Actions)
- [ ]  Automated PHPStan + Rector on push
- [ ]  Integration tests for Admin + Core
- [ ]  Performance baseline + load test
- [ ]  Release tag `v2025.0`
- [ ]  Documentation audit + release notes

---

## 🧩 Manual Work Queue

| File | Reason | Action |
|------|---------|--------|
| Various `/classes/Http/Handlers/...` | `SKIPPED_COMPLEX` | Extract legacy flow manually, rebuild under DI. |
| `/public/*` | procedural scripts | Migrate gradually to handler architecture. |
| `/includes/*` | globals/functions | Move into service classes. |
| `/lang/*` | procedural include | Replace with ConfigRepository-driven localization. |

---

## 🧱 Rules Version Control

| Version | Description | Status |
|----------|--------------|---------|
| 2025.10.22 | Safe Codex mode (idempotent) | ✅ Active |
| 2025.11.xx | Next rule set (expanded allowlist) | ⏳ Planned |

---

## 🗃 Artifacts

- `/tools/handler_convert_report.csv` — all per-file results
- `/tools/handler_convert_state.json` — idempotent state ledger
- `/tools/_handler_convert_markers.php` — six marker lines for validation
- `/quarantine/` — legacy backup zone
- `/classes/Http/Handlers/` — modern handler tree

---

## ✅ Definition of Done

- All legacy scripts either modernized or quarantined  
- Admin green milestone reached  
- PHPStan level max passes  
- All handlers self-lint clean  
- CI/CD gates operational  
- `README.md` and `TODO.md` reflect actual status
