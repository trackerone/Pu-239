# Conversion Report: classes/Http/Handlers/Public/DownloadsubHandler.php

- **Date**: 2025-10-16
- **Batch**: offset=150 size=5
- **Source legacy script**: public/downloadsub.php
- **Summary**:
  - Wrapped the subtitle download workflow inside the handler with Database-bound lookups and safer parameter casting.
  - Retained the legacy archive creation flow while guarding file access failures and enforcing the original permission check.
- **Todos**:
  - TODO(2025): add CSRF verification to the downloadsub POST action.
  - TODO(2025): verify container binding for ZipArchive::class still provides force_download().
  - TODO(2025): supply fallback when ZipArchive binding lacks force_download().
  - TODO(2025): confirm legacy unlink without UPLOADSUB_DIR prefix remains intentional.
- **Notes**: Consider replacing the ad-hoc zip handling with the Phpzip helper in a follow-up refactor.
