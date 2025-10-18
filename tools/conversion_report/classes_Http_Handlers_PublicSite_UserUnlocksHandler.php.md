# Conversion attempt: classes/Http/Handlers/PublicSite/UserUnlocksHandler.php

- Outcome: converted
- Legacy source: `public/user_unlocks.php`
- Backup: `classes/Http/Handlers/PublicSite/UserUnlocksHandler.php.preconvert.bak`
- Notes:
  - Ported unlock toggles to use container `Database`, `Cache`, and config services while retaining permission guards.
  - Preserved audit logging for staff changes and simplified checkbox state rendering helpers.
- TODOs recorded in handler:
  - `TODO(2025): add CSRF verification`
