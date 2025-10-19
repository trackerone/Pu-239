# ReportHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - public/report.php mixes Fluent placeholders with direct Database usage and needs a careful rewrite of the insert + cache invalidation logic.
  - Handler retains legacy buffering and flags the outstanding translation to Database::run semantics.
- TODOs:
  - TODO(2025): extract public/report.php:1-180, replace sql placeholders, and integrate cache/session side effects safely.
