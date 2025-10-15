# Conversion Report: classes/Http/Handlers/Public/DeleteHandler.php

- Legacy source: public/delete.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php, class_user_options_2.php
- Config mappings: site.name, paths.baseurl, bonus.per_delete, bonus.on
- Database usage: Database::fetch for torrent metadata and joins via Database service
- TODOs introduced: 1 (CSRF verification for delete workflow)
- Notes: Handler now performs torrent removal, logging, bonus rollback, and optional PM notifications using service container dependencies.
