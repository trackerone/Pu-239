# Conversion Report: classes/Http/Handlers/Admin/AllagentsHandler.php

- Legacy source: admin/allagents.php
- Container/bootstrap dependencies: bootstrap_web.php
- Services injected: ConfigRepository, Database
- Config mappings: paths.baseurl → breadcrumb and link generation
- Database usage: Database::fetchAll for agent/peer identifier listing
- TODOs introduced: 1 (reconcile legacy AuthZ conflict markers from admin/allagents.php)
- Notes: Wrapped the peer client summary table in the handler with sanitized breadcrumbs and wrapper output while preserving the legacy formatting helpers.
