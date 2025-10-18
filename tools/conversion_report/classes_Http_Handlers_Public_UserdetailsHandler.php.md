# Conversion attempt: classes/Http/Handlers/Public/UserdetailsHandler.php

- Outcome: deferred (manual intervention required)
- Legacy source: `public/userdetails.php`
- Backup: `classes/Http/Handlers/Public/UserdetailsHandler.php.preconvert.bak`
- Notes:
  - Legacy script wires multiple includes (`bittorrent.php`, user option classes) and hydrates `$stdhead/$stdfoot`, form helpers, and session state.
  - Numerous helper calls and domain services exceed safe auto-conversion heuristics; manual audit needed to isolate responsibilities.
- TODOs recorded in handler:
  1. `TODO(2025): extract legacy block from public/userdetails.php:1-400 – complex includes, session state, and helper functions require manual port.`
