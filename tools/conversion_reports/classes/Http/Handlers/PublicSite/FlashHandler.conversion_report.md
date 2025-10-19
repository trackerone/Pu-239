# Conversion Report: classes/Http/Handlers/PublicSite/FlashHandler.php

- **Date**: 2025-10-19
- **Batch**: offset=260 size=5
- **Source legacy script**: public/flash.php
- **Summary**:
  - Conversion deferred; legacy script still relies on inline `mysqli`/`sql_query` chains and mutable globals that exceed safe automation heuristics.
  - Handler retains buffered legacy inclusion while documenting the outstanding TODO for manual rewrite.
- **Todos**:
  - TODO(2025): extract legacy block from public/flash.php:1-200 – requires replacing raw SQL helpers and integrating ZipArchive streaming service.
- **Error handling**: Buffered legacy execution guarded by handler-level try/catch for stability.
