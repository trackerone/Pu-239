# ViewAnnounceHistoryHandler conversion (2025-10-20)

- Status: Converted
- Summary:
  - Embedded public/view_announce_history.php display flow, replacing sql_query/mysqli loops with Database::fetchAll bindings.
  - Recreated announcement selection view with safe parameter casting and existing stdhead/stdfoot wrappers.
- TODOs:
  - None
