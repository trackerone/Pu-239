# DeathrowHandler conversion (2025-10-07)

- Status: Deferred (manual extraction required)
- Notes:
  - admin/deathrow.php mixes inline function declarations, $fluent ORM remnants, and multi-branch notification flows.
  - Conversion postponed pending targeted refactor of notifier helpers and transaction boundaries.
- TODOs:
  - TODO(2025): extract legacy block from admin/deathrow.php:1-240 (custom helpers, $fluent usage, nested functions)
