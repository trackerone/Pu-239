# Conversion Report: classes/Http/Handlers/Public/Ajax/RatingHandler.php

- **Date**: 2025-10-06
- **Batch**: offset=60 size=5
- **Source legacy script**: public/ajax/rating.php
- **Summary**:
  - Migrated full rating flow including transaction, cache updates, and bonus adjustments into handler.
  - Retrieved `ConfigRepository`, `Database`, and `Cache` services from the container and preserved helper bootstrap.
  - Normalized HTML sanitization closure and JSON response parity with legacy script.
- **Todos**:
  - TODO(2025): csrf.
- **Error handling**: Protected execution with try/catch; rating transaction keeps rollback semantics on failure.
