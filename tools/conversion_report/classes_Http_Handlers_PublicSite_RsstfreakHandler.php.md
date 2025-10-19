# Conversion attempt: classes/Http/Handlers/PublicSite/RsstfreakHandler.php

- Outcome: converted
- Legacy source: `public/rsstfreak.php`
- Backup: `classes/Http/Handlers/PublicSite/RsstfreakHandler.php.bak`
- Notes:
  - Ported the TorrentFreak RSS ingestion logic with container-managed cache reuse and DOM parsing safeguards.
  - Preserved link anonymisation, proxy rewriting, and legacy HTML post-processing for feed items.
- TODOs recorded in handler: _none_
