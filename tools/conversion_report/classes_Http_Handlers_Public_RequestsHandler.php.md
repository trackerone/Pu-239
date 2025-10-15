# Conversion Report: classes/Http/Handlers/Public/RequestsHandler.php

- Legacy source: public/requests.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: Deferred; numerous config lookups drive category, path, and bounty behaviour
- Database usage: Deferred; requires orchestrating Request, Comment, Torrent, and Bounty services
- TODOs introduced: 1 (manual extraction follow-up)
- Notes: Stub left unchanged because the requests module manages multi-step actions, validation, and role checks.
