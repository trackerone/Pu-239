# Batch 33 – PHP 8.3 Remediation Plan

## Known blockers
- `biblys/isbn 2.x` → bump to ^3.0
- `envms/fluentpdo` → remove/replace with Aura.Sql or PDO
- `umpirsky/composer-permissions-handler` → remove

## Steps
1. Run `php83-blockers-check.yml` → soft report on PRs
2. Run `composer-modernize-pr.yml` (manual) → auto PR:
   - Set PHP ^8.3
   - Set config.platform.php = 8.3.0
   - Bump isbn → ^3
   - Remove permissions-handler
3. Merge PR, re-run Batch 32 diagnostics
4. Replace FluentPDO (see migration guide)
5. Re-run diagnostics → once clean, switch CI from Soft → Hard guard
