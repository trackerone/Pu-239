# ViewSqlHandler conversion (2025-10-09)

- Status: Converted
- Notes:
  - Embedded `public/ajax/view_sql.php` into the handler while preserving Adminer plugin bootstrapping.
  - Wrapped `adminer_object()` definition in a guard to avoid redeclaration when the handler executes multiple times.
- TODOs:
  - None.
