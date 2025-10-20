# BrowseHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - Legacy public/browse.php performs category filtering, search facets, and cached pager composition via FluentPDO.
  - Handler remains in buffered require mode pending manual extraction.
- TODOs:
  - TODO(2025): Extract public/browse.php lines 1-570 into structured services before refactoring handler logic.
