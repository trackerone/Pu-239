# Conversion Report: classes/Http/Handlers/Public/ClearAnnouncementHandler.php

- Legacy source: public/clear_announcement.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: `$site_config['paths']['baseurl']` → `$config->get('paths.baseurl')`
- Database usage: Converted inline `UPDATE` to `$db->run(...)` with bound params
- TODOs introduced: 0
- Notes: Preserved cache row update with config-driven expiry duration.
