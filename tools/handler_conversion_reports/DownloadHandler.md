# DownloadHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - public/download.php mixes torrent pass validation, freeleech slot handling, and ExtendedPDO TODO markers across ~200 lines.
  - Additional includes (class.bencdec, audit helpers) complicate automated extraction.
- TODOs:
  - TODO(2025): Reconstruct public/download.php lines 1-201 within a dedicated service before promoting handler.
