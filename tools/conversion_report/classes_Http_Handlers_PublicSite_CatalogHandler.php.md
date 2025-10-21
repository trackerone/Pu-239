# Conversion Report: classes/Http/Handlers/PublicSite/CatalogHandler.php

- Legacy source: public/catalog.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: Deferred; catalog renderer pulls base paths and image hosts from ConfigRepository
- Database usage: Deferred; relies on Fluent-style queries for torrent listings and peer lookups
- TODOs introduced: 1 (manual extraction follow-up)
- Notes: Deferred because the catalog page embeds helper functions, peer list formatting, and pagination logic not safe for automated extraction.
