# UserhistoryHandler Conversion Report
- Source: `public/userhistory.php`
- Converted: ❌ No
- Todos: 1
- Notes:
  - Script still issues raw `sql_query` calls with manual pagination across forum posts and torrent comments; depends on global helpers.
  - TODO(2025): manual extraction required for the history views described in `public/userhistory.php:1-173`, including pager + permission logic.
