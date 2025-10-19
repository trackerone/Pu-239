# NewAnnouncementHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - Legacy public/new_announcement.php relies on raw SQL via sql_query/sqlesc and dynamic expiry handling that needs manual review.
  - Handler retains the legacy include while capturing output and logs conversion attempt metadata.
- TODOs:
  - TODO(2025): extract and modernize public/new_announcement.php:1-200 once announcement Main insert flow is mapped to Database service.
