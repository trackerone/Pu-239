# Conversion Report: classes/Http/Handlers/Admin/FloodlimitHandler.php

- Legacy source: admin/floodlimit.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php
- Services injected: ConfigRepository, Session
- Config mappings: paths.baseurl → breadcrumbs/self links, paths.flood_file → JSON limit storage
- Database usage: None (limits persisted to filesystem)
- TODOs introduced: 1 (retain legacy CSRF reminder)
- Notes: Mirrored legacy table rendering and audit logging while streaming output via stdhead/stdfoot helpers.
