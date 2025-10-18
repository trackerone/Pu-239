# Conversion Report: classes/Http/Handlers/Admin/ManageImagesHandler.php

- Legacy source: admin/manage_images.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php
- Services injected: ConfigRepository, Image, Session
- Config mappings: paths.baseurl → breadcrumbs/forms
- Database usage: not required (Image service encapsulates persistence)
- TODOs introduced: 2 (AuthZ conflict marker reconciliation, add CSRF for deletions)
- Notes: Ported the image management search and deletion workflows, including proxy cache cleanup and pager handling, while preserving legacy helper usage for tables and feedback messages.
