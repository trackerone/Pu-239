# Conversion attempt: classes/Http/Handlers/Public/WikiHandler.php

- Outcome: deferred (manual extraction required)
- Legacy source: `public/wiki.php`
- Backup: `classes/Http/Handlers/Public/WikiHandler.php.preconvert.bak`
- Notes:
  - Script registers local helper functions (`navmenu`, `wikimenu`), resolves services via container, and manages validator/image pipelines.
  - Multiple embedded closures and translation helpers complicate automated scoping; manual review recommended to segment responsibilities.
- TODOs recorded in handler:
  1. `TODO(2025): extract legacy block from public/wiki.php:1-360 – embedded helpers, validator usage, and image pipelines exceed safe auto-conversion heuristics.`
