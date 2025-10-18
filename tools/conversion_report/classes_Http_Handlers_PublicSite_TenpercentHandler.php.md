# Conversion Report: classes/Http/Handlers/PublicSite/TenpercentHandler.php

- Legacy source: public/tenpercent.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Database, Cache, Message
- Config mappings: expires.user_cache
- Database usage: UPDATE users SET uploaded = uploaded * 1.1, tenpercent = :flag WHERE id = :id
- TODOs introduced: 1 (CSRF verification placeholder)
- Notes: Replicated ratio calculations, cache refresh, and staff notification messaging for the 10% upload boost workflow.
