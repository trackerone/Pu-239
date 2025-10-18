# Conversion Report: classes/Http/Handlers/Admin/ReputationSettingsHandler.php

- Legacy source: admin/reputation_settings.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php
- Services injected: ConfigRepository
- Config mappings: paths.baseurl → breadcrumbs and redirect target
- Database usage: None (configuration stored in cache file)
- TODOs introduced: 2 (CSRF hardening for POST submission, escape review for redirect HTML shell)
- Notes: Migrated the reputation settings form, cache writer, and templating helpers into the handler and replaced global functions with scoped closures for cache writes and template rendering.
