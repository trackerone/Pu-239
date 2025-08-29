# Static Analysis Baseline (Batch 28)

This batch provides a **drop-in baseline setup** for static analysis in Pu-239 (Laravel 11).  
Place the files in the project root, run the required composer commands (in CI or locally), and you're ready.

## Includes
- `.github/workflows/static-analysis.yml` – CI workflow running Pint + PHPStan
- `phpstan.neon` & `phpstan-baseline.neon` – PHPStan configuration (level=max, empty baseline)
- `pint.json` – Laravel Pint (code style) rules
- `rector.php` – optional Rector configuration for gradual modernization
- `Makefile` – QA shortcuts
- `.editorconfig` / `.gitattributes` – safe defaults
- `snippets/composer.dev.snippet.json` – example dev-deps and composer scripts

## Usage in CI
```bash
composer require --dev laravel/pint phpstan/phpstan nunomaduro/larastan rector/rector --with-all-dependencies
vendor/bin/pint --test
vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G
```

## Notes
- The baseline (`phpstan-baseline.neon`) is empty to **force errors to be visible**.
- If it becomes too strict initially, generate a baseline in CI:
  ```bash
  vendor/bin/phpstan analyse --configuration=phpstan.neon --generate-baseline
  ```

*Generated: 2025-08-29T04:14:22*
