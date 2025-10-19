# Conversion Report: classes/Http/Handlers/PublicSite/BookmarksHandler.php

- **Date**: 2025-10-19
- **Batch**: offset=265 size=5
- **Source legacy script**: public/bookmarks.php
- **Summary**:
  - Audited the bookmarks page but it relies on nested helper functions and FluentPDO remnants not yet replaced with container services.
  - Deferred rewriting until the underlying query helpers are modernized.
- **Todos**:
  - TODO(2025): extract legacy block from public/bookmarks.php:1-360 (nested helpers + FluentPDO remnants).
- **Error handling**: Conversion deferred; stub maintains buffered inclusion of the legacy script.
