# Conversion Report: classes/Http/Handlers/Public/LogoutHandler.php

- Legacy source: public/logout.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$site_config['paths']['baseurl']` → `$config->get('paths.baseurl')`
- Database usage: none
- TODOs introduced: 0
- Notes: Handler now resolves Auth and User services via the container before redirecting.
