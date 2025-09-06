Batch 40 — Full Repo FluentPDO Sweep
====================================

What this action does
---------------------
- Scans the entire repository (excluding vendor/node_modules/.git/storage/uploads/cache) for FluentPDO usage.
- Writes a machine-readable report to `tools/reports/batch40-fluentpdo-report.ndjson` and a human summary to `tools/reports/batch40-summary.txt`.
- Optionally applies **conservative fixes** to obvious patterns (imports, docblocks, container `$fluent` hints, and specific admin patterns).

Inputs
------
- `apply_conservative_fixes` (default: false) — when true, applies safe, high-signal replacements.
- `force_pr` (default: true) — ensures a PR is opened by committing the generated report even if no code lines changed.

How to run
----------
1) Add files:
   - `.github/workflows/batch-40.yml`
   - `tools/batch-40-sweep.php`
2) Run workflow in GitHub Actions.
3) Check the PR for the report and any optional code changes.
4) Use the report to plan/execute more targeted migrations in later batches.

Notes
-----
- All replacements are conservative. Complex query chains are **not** auto-rewritten; they are only reported.
- Keep using `$this->db` (Aura ExtendedPdo) for manual rewrites in follow-up batches.
