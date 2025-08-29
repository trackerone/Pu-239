# Batch 31 – PHP 8.3 Platform Alignment (Soft)

**Goal:** Provide a CI-only, non-blocking check that your repository is aligned with **PHP 8.3**.  
This makes blockers visible early without breaking pull requests.

## What’s included
- `.github/workflows/composer-platform-check.yml`  
  Runs on PRs and on manual dispatch. It validates composer metadata, runs a light platform check script, and collects diagnostics:
  - `composer validate`
  - `php tools/check_platform.php` → `platform-check-report.txt`
  - `composer why-not php ^8.3 -t` → `composer-why-not-php83.txt`
  Artifacts are uploaded for inspection. All steps are **non-blocking**.

- `tools/check_platform.php`  
  Prints detected PHP constraints (from `require.php` and `config.platform.php`) and gives a soft verdict + suggestions.  
  Note: this is a **heuristic**; the authoritative dependency check is still `composer why-not php ^8.3`.

- `snippets/composer.platform.snippet.json`  
  A ready-made snippet you can merge into `composer.json` to pin the project to PHP 8.3.

## How to use
1. Drop these files into the repository root and commit.
2. Open a PR or manually run the workflow from the Actions tab.
3. Download the artifacts to see whether your constraints permit PHP 8.3 and which packages (if any) block the upgrade.

## Next steps
- If constraints don’t allow 8.3: update `require.php` or set `config.platform.php` to `8.3.0`.
- Use the `why-not` report to upgrade or replace blockers.
- When the repo is clean on 8.3, we can switch CI checks from **Soft** to **Hard Guard** (fail the PR on violations).

*Generated: 2025-08-29T04:22:38*
