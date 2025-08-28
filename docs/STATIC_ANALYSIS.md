# Static Analysis Baseline (Batch 28)

Dette batch leverer et **klar-til-brug** baseline-setup for statisk analyse i Pu‑239 (Laravel 11). Drop filerne i repoets rod, kør `composer require`-kommandoerne nedenfor, og du er i gang.

## Indeholder
- `.github/workflows/static-analysis.yml` – CI-workflow der kører Pint + PHPStan.
- `phpstan.neon` & `phpstan-baseline.neon` – PHPStan niveau **max** med tom baseline.
- `pint.json` – Laravel Pint (code style) regler.
- `rector.php` – (valgfrit) Rector-konfig til gradvis modernisering.
- `Makefile` – hurtige QA-kommandoer.

## Installation (lokalt)
```bash
composer require --dev laravel/pint phpstan/phpstan nunomaduro/larastan rector/rector --with-all-dependencies
php artisan vendor:publish --tag=pint-config --force || true
vendor/bin/pint --version
vendor/bin/phpstan --version
```

## Kørsel
```bash
# Code style check (ikke auto-fix i CI)
vendor/bin/pint --test

# Statisk analyse
vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G

# Rector (dry-run)
vendor/bin/rector process --dry-run
```

## Noter
- `phpstan-baseline.neon` er tom fra start for at **tvinge fejl frem** og holde kvaliteten skarp.
- Hvis det bliver for stramt på eksisterende kode, kør:
  ```bash
  vendor/bin/phpstan analyse --configuration=phpstan.neon --generate-baseline
  ```
  og commit ændringerne – men begræns brugen og ryd gradvist op.

*Batch genereret: 2025-08-28T03:42:49*
