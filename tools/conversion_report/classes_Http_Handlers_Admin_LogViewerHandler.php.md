# Conversion Report: classes/Http/Handlers/Admin/LogViewerHandler.php

- Legacy source: admin/log_viewer.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php
- Services injected: ConfigRepository
- Config mappings: paths.baseurl → pager URLs and breadcrumbs, paths.log_viewer → directory scan roots
- Database usage: None (file system driven viewer)
- TODOs introduced: 1 (CSRF hardening for delete POST)
- Notes: Ported the log viewer's parsing modes, pagination, and deletion audit trail into the handler while preserving the recursive directory scan and existing UI helpers.
