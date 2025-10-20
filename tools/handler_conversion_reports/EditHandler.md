# EditHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - public/edit.php coordinates torrent metadata edits, BBCode rendering, and validation helpers with legacy globals.
  - Multi-branch form handling exceeds safe auto-conversion heuristics.
- TODOs:
  - TODO(2025): Manually port public/edit.php lines 1-270 into service classes and update handler accordingly.
