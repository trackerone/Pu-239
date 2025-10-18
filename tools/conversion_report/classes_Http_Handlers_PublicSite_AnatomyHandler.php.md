# Conversion attempt: classes/Http/Handlers/PublicSite/AnatomyHandler.php

- Outcome: converted
- Legacy source: `public/anatomy.php`
- Backup: `classes/Http/Handlers/PublicSite/AnatomyHandler.php.preconvert.bak`
- Notes:
  - Migrated static content rendering into handler while preserving translation calls and breadcrumb construction.
  - Injected `ConfigRepository` and `Database` via service container to satisfy legacy helpers that rely on these globals.
- TODOs recorded in handler: _none_
