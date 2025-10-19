# Conversion attempt: classes/Http/Handlers/PublicSite/RestoreclassHandler.php

- Outcome: converted
- Legacy source: `public/restoreclass.php`
- Backup: `classes/Http/Handlers/PublicSite/RestoreclassHandler.php.bak`
- Notes:
  - Restored user override class and mirrored cache/database cleanup with injected `User`, `Database`, and `Cache` services.
  - Logged role change via `Audit::log` to preserve traceability.
- TODOs recorded in handler: _none_
