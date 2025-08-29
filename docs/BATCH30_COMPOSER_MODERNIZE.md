# Batch 30 – Composer Modernization (CI-driven)

**Purpose:** Gain visibility into blockers for PHP 8.3/Laravel 11 without any local runs.  
The workflow can also *attempt* a `composer update` and automatically open a PR if successful.

## Workflow jobs
1. **Diagnose** (runs automatically on PRs, or manually via *Run workflow*):
   - Validates `composer.json`
   - Generates reports:
     - `composer-outdated.json` (direct dependencies)
     - `composer-why-not-php83.txt`
     - `composer-why-not-laravel11.txt`
   - Reports are uploaded as artifacts in GitHub Actions.

2. **Update** (manual trigger with input `update_lock=true`):
   - Runs `composer update --with-all-dependencies --no-scripts --no-plugins`
   - If successful: opens a PR with the updated `composer.lock` and reports
   - If failed: no PR is created, but logs are uploaded as artifacts

## Why without scripts/plugins?
Legacy projects often have fragile install scripts or outdated plugins.  
This workflow focuses on the **dependency graph first** – not post-install hooks.

## Next steps
- Use diagnosis to identify blockers and plan package upgrades or replacements.
- Once `composer update` succeeds, gradually remove `--no-scripts/--no-plugins`.
- When the repo is stable on PHP 8.3, switch from **Soft Guard** → **Hard Guard** in CI (as agreed).

*Generated: 2025-08-29T04:19:06*
