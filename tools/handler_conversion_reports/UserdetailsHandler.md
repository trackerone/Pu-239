# UserdetailsHandler Conversion Report
- Source: `public/userdetails.php`
- Converted: ❌ No
- Todos: 1
- Notes:
  - Legacy profile renderer spans ~1100 lines with multiple includes, raw SQL helpers, and global state; automated extraction deemed too risky.
  - TODO(2025): plan manual rewrite covering stats, achievements, and permission checks noted in `public/userdetails.php:1-1106`.
