# Conversion Report: classes/Http/Handlers/PublicSite/ChatHandler.php

- Legacy source: public/chat.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')` for breadcrumb links
- Database usage: none (display-only handler)
- TODOs introduced: 0
- Notes: Rendered static IRC information panel with existing layout helpers and retained guest nick generation fallback.
