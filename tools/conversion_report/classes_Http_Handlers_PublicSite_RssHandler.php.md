# Conversion attempt: classes/Http/Handlers/PublicSite/RssHandler.php

- Outcome: converted
- Legacy source: `public/rss.php`
- Backup: `classes/Http/Handlers/PublicSite/RssHandler.php.bak`
- Notes:
  - Rebuilt the authenticated RSS endpoint with validator-driven input checks, cache-backed torrent queries, and PDO-bound SQL.
  - Inlined the legacy RSS formatter as a private method while preserving feed/URL construction semantics and error messaging.
- TODOs recorded in handler: `// TODO(2025): review escaping strategy for $rss output`
