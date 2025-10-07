# AnnouncementHandler Conversion Report
- Source: `public/announcement.php`
- Converted: ❌ No
- Todos: 1
- Notes:
  - Legacy script includes complex `sql_query` + `sqlesc` patterns with dynamic SQL fragments.
  - Marked handler with TODO for manual extraction referencing `public/announcement.php:1-220`.
  - Stub still proxies to legacy file until manual rewrite can safely replace dynamic workflows.
