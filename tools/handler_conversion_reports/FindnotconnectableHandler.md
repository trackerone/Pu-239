# FindnotconnectableHandler conversion (2025-10-07)

- Status: Deferred (manual extraction required)
- Notes:
  - admin/findnotconnectable.php still depends on mysqli/sql_query patterns and manual HTML loops.
  - Requires bespoke PDO migration and messaging audit before safe inline conversion.
- TODOs:
  - TODO(2025): extract legacy block from admin/findnotconnectable.php:1-220 (legacy mysqli/sql_query flow)
