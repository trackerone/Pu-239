# UsersHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - public/users.php currently throws a RuntimeException placeholder referencing tools/rehydrate_v3_manifest.csv and lacks the legacy body to extract.
  - Handler retains buffered require semantics and records the conversion attempt.
- TODOs:
  - TODO(2025): Restore legacy public/users.php implementation (lines 1-10) or supply modern equivalent before refactoring handler logic.
