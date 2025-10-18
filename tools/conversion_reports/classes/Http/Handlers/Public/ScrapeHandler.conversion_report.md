# Conversion Report: classes/Http/Handlers/Public/ScrapeHandler.php

- **Date**: 2025-10-17
- **Batch**: offset=170 size=5
- **Source legacy script**: public/scrape.php
- **Summary**:
  - Ported the tracker scrape workflow into the handler, including manual query parsing and torrent lookups through the Torrent service.
  - Reconstructed the legacy bencoded response builder while enforcing container-driven dependency resolution.
- **Todos**:
  - TODO(2025): evaluate replacing the custom $_GET rebuild with a dedicated request wrapper to reduce duplication across tracker endpoints.
- **Notes**: Keep an eye on performance if the number of requested hashes grows; batching through the repository may warrant future caching.
