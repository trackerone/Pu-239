# Conversion Report: classes/Http/Handlers/Public/EditHandler.php

- Legacy source: public/edit.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php, PARTIALS_DIR/genres.php
- Config mappings: paths.baseurl, paths.images_baseurl, site.name, allowed.torrents_disable_comments
- Database usage: Database::fetch for torrent row hydration; cache service retains edit locks
- TODOs introduced: 1 (map legacy $site_config['expires']['ismoddin'] to ConfigRepository equivalent)
- Notes: Handler now renders the edit form, permission checks, and delete form inline while maintaining cache-based edit locking and staff workflows.
