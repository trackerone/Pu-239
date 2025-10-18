# Conversion Report: classes/Http/Handlers/PublicSite/LogoutHandler.php

- Legacy source: public/logout.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')` for redirect target
- Database usage: none (delegated to User service logout call)
- TODOs introduced: 0
- Notes: Wired Auth + User services through container and mirrored legacy logout redirect with error handling wrapper.
