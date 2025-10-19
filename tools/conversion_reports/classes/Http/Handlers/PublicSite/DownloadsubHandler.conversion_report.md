# Conversion Report: classes/Http/Handlers/PublicSite/DownloadsubHandler.php

- **Date**: 2025-10-19
- **Batch**: offset=260 size=5
- **Source legacy script**: public/downloadsub.php
- **Summary**:
  - Migrated subtitle download handler to `Database::fetch`/`run` APIs with strict parameter binding for subtitle lookups and hit counters.
  - Normalised file handling by generating sanitized filenames, packaging them via `ZipArchive`, and streaming the archive when container helper is unavailable.
  - Preserved permission enforcement, CSRF TODO marker, and error messaging semantics from the legacy script.
- **Todos**: _None_
- **Error handling**: Handler try/catch logs failures and exposes HTTP 500 fallback output.
