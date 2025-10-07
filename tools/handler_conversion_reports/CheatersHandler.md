# CheatersHandler conversion (2025-10-07)

- Status: Converted
- Notes:
  - Inlined admin/cheaters.php workflow with container-provided config, database, and cache services.
  - Replaced raw IN() string assembly with Database::inClause bindings and preserved tooltip output formatting.
  - Added TODO for CSRF hardening during POST actions.
- TODOs:
  - TODO(2025): csrf hardening for admin cheater actions
