# Conversion attempt: classes/Http/Handlers/PublicSite/ViewSqlHandler.php

- Outcome: converted
- Legacy source: `public/view_sql.php`
- Backup: `classes/Http/Handlers/PublicSite/ViewSqlHandler.php.bak`
- Notes:
  - Enforced the original access checks and logging before redirecting unauthorized users away from Adminer.
  - Rendered the Adminer iframe with the expected database/user parameters and retained the iframe resize assets.
- TODOs recorded in handler: _none_
