# Conversion Report: classes/Http/Handlers/Public/BjstatsHandler.php

- **Date**: 2025-10-07
- **Batch**: offset=70 size=5
- **Source legacy script**: public/bjstats.php
- **Summary**:
  - Ported blackjack statistics rendering into the handler with container-managed config and database services.
  - Replaced legacy `$fluent` chains with parameterised `Database::fetchAll` queries, including helper rendering for ranking tables.
  - Preserved permission checks, breadcrumbs, and table layout consistent with the original script.
- **Todos**: _None_
- **Error handling**: Retains handler-level try/catch with HTTP 500 fallback.
