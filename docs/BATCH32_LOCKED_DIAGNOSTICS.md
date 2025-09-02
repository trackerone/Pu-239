# Batch 32 – Locked Install Diagnostics (PHP 8.3)

**Goal:** Produce a **clean, non-destructive** report of which packages in your **existing lockfile** block PHP 8.3 — all in CI, without touching the repo.

## What it does
- Copies `composer.json` + `composer.lock` to a temporary directory
- Injects a **temporary** `config.platform.php = 8.3.0` (only in the temp copy)
- Runs:
  - `composer why-not php ^8.3 --locked -t`
  - `composer outdated --direct --locked --format=json`
  - `composer install --no-dev --no-scripts --no-plugins` (will fail if blockers exist)
- Uploads artifacts:
  - `composer-why-not-php83-locked.txt`
  - `composer-outdated-locked.json`
  - `install-log.txt`
  - `summary.txt`
  - temp `composer.json` (with platform injected) and `composer.lock`

## How to use
1. Commit the workflow file: `.github/workflows/composer-locked-diagnostics.yml`
2. In GitHub → **Actions** → Run **Composer Locked Install Diagnostics (PHP 8.3)**
3. Download the artifacts to see **exactly which packages** block PHP 8.3.

## Notes
- The repository itself is **not modified**. All edits happen in `ci-tmp/`.
- This avoids local runs and gives you a deterministic blockers list based on the current **lockfile**.
- Once blockers are known, you can:
  - replace or upgrade specific packages, or
  - run a controlled `composer update` PR (see Batch 30).
