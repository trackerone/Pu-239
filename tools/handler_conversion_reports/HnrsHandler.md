# HnrsHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - Handler still streams legacy public/hnrs.php because the script orchestrates multiple services, fluent queries, and cache invalidations.
  - Added TODO to track future extraction once database workflow is modernized.
- TODOs:
  - TODO(2025): extract complex hit-and-run management workflow from public/hnrs.php:1-340 into dedicated services.
