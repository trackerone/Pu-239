# Conversion Report: classes/Http/Handlers/Admin/ViewPeersHandler.php

- **Date**: 2025-10-11
- **Batch**: offset=125 size=5
- **Source legacy script**: admin/view_peers.php
- **Summary**:
  - Ported the peer listing table, sorting, and pagination logic into the handler while reusing the `Peer` service for data access.
  - Wired the peer deletion link to use container-provided database and session messaging, mirroring the legacy audit logging.
- **Todos**:
  - TODO(2025): add CSRF protection to the peer deletion link.
- **Notes**: Sorting toggles and pager parameters preserve the original request semantics.
