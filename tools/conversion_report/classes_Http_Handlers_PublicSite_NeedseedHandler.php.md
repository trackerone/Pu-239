# Conversion attempt: classes/Http/Handlers/PublicSite/NeedseedHandler.php

- Outcome: converted
- Legacy source: `public/needseed.php`
- Backup: `classes/Http/Handlers/PublicSite/NeedseedHandler.php.preconvert.bak`
- Notes:
  - Inlined the seeders/leechers dashboards and replaced FluentPDO queries with container-provided `Database` parameter binding.
  - Normalised category lookups and breadcrumb generation using config-derived base URLs.
- TODOs recorded in handler: _none_
