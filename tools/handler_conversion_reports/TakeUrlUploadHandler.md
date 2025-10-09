# TakeUrlUploadHandler conversion (2025-10-09)

- Status: Converted
- Notes:
  - Inlined `public/ajax/take_url_upload.php` workflow into the handler using ImageProxy and configuration salt values.
  - Retained validation and optimized file handling for remote image ingestion.
- TODOs:
  - TODO(2025): csrf validation for remote URL submissions.
