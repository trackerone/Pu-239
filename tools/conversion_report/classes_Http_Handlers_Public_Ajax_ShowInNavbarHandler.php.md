# Conversion Report: classes/Http/Handlers/Public/Ajax/ShowInNavbarHandler.php

- Legacy source: public/ajax/show_in_navbar.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: none
- Database usage: Injected Cache + Database services; converted navbar toggle update to bound parameters with audit + cache busting.
- TODOs introduced: 1 (csrf follow-up for POST)
- Notes: Mirrors legacy permission checks and returns with early exits; retains audit logging semantics.
