# Conversion Report: classes/Http/Handlers/Public/Ajax/RequestNotifyHandler.php

- **Date**: 2025-10-06
- **Batch**: offset=60 size=5
- **Source legacy script**: public/ajax/request_notify.php
- **Summary**:
  - Ported notification toggle logic for requests, sourcing `Database` from the container.
  - Preserved boolean validation semantics and JSON payload structure.
  - Ensured helper bootstrap remains to provide global functions and constants.
- **Todos**:
  - TODO(2025): csrf.
- **Error handling**: Surrounds execution with try/catch to surface 500 response on unexpected failures.
