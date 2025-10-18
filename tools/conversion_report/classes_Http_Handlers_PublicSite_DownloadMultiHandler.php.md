# Conversion attempt: classes/Http/Handlers/PublicSite/DownloadMultiHandler.php

- Outcome: converted
- Legacy source: `public/download_multi.php`
- Backup: `classes/Http/Handlers/PublicSite/DownloadMultiHandler.php.preconvert.bak`
- Notes:
  - Ported bulk torrent packaging flow, wiring `ConfigRepository`, `Session`, `User`, `Torrent`, and `Phpzip` services via the container.
  - Preserved announce URL selection logic and ensured generated archives are cleaned up after download.
- TODOs recorded in handler:
  - `TODO(2025): confirm legacy $row['owner'] mapping for uploaded torrents`
