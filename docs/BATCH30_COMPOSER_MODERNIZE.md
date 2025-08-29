# Batch 30 – Composer modernization (CI-driven)

**Formål:** Få overblik over blockers til PHP 8.3/Laravel 11, uden lokal kørsel.  
Workflowet kan også *forsøge* en `composer update` og åbne en PR **automatisk**, hvis det lykkes.

## Sådan bruges workflowet
1. **Diagnose** (kører automatisk på PRs og kan startes manuelt):
   - Validerer `composer.json`
   - Genererer rapporter:
     - `composer-outdated.json` (direkte afhængigheder)
     - `composer-why-not-php83.txt`
     - `composer-why-not-laravel11.txt`
   - Rapporter uploades som artefakter i Actions.

2. **Opdatering** (manuelt via *Run workflow*):
   - Sæt input `update_lock = true`
   - Kører `composer update --with-all-dependencies --no-scripts --no-plugins`
   - Hvis succes: åbner PR med opdateret `composer.lock` + rapporter
   - Hvis fejl: ingen PR, men artefakter uploades for triage.

## Hvorfor uden scripts/plugins?
- Gamle projekter har ofte skrøbelige installer-scripts eller legacy-plugins.
- Vi fokuserer først på **afhængighedsgrafen** – ikke post-install hooks.

## Næste skridt
- Når blokeringer er identificeret, kan vi *målrettet* løfte de enkelte pakker eller erstatte dem.
- Når `composer update` lykkes, kan `--no-scripts/--no-plugins` gradvis fjernes.
- Når repoet er stabilt på PHP 8.3, skifter vi Soft → Hard guard i Batch 30+ (som aftalt).

*Genereret: 2025-08-29T04:05:34*
