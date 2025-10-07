# Conversion Report: classes/Http/Handlers/Public/Ajax/CheckportsHandler.php

- **Date**: 2025-10-06
- **Batch**: offset=65 size=5
- **Source legacy script**: public/ajax/checkports.php
- **Summary**:
  - Ported the multi-peer connectivity matrix into the handler, retrieving peers via the Database service and sanitising output.
  - Preserved staff override checks, duplicate suppression, and HTML assembly for each peer connection.
- **Todos**:
  - TODO(2025): csrf.
- **Error handling**: Wrapped execution in try/catch with HTTP 500 fallback as per handler guidelines.
