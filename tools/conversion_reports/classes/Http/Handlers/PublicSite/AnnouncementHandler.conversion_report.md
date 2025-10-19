# Conversion Report: classes/Http/Handlers/PublicSite/AnnouncementHandler.php

- **Date**: 2025-10-19
- **Batch**: offset=265 size=5
- **Source legacy script**: public/announcement.php
- **Summary**:
  - Attempted to inline the announcements workflow but detected tightly-coupled mysqli/sql_query logic with dynamic SQL assembly.
  - Left the handler as a buffered stub with a TODO for manual extraction once the DB layer is modernized.
- **Todos**:
  - TODO(2025): extract legacy block from public/announcement.php:1-220 (complex mysqli/sql_query workflow with dynamic SQL).
- **Error handling**: Conversion deferred; existing stub remains in place pending manual port.
