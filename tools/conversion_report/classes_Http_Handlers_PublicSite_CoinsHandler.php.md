# Conversion attempt: classes/Http/Handlers/PublicSite/CoinsHandler.php

- Outcome: converted
- Legacy source: `public/coins.php`
- Backup: `classes/Http/Handlers/PublicSite/CoinsHandler.php.preconvert.bak`
- Notes:
  - Handler now emits a 503 response explaining the legacy coin ledger requires restored SQL before reactivation.
- TODOs recorded in handler:
  - TODO(2025): port coin ledger logic from public/coins.php:1-10 after legacy queries are restored.
