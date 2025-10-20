# TvshowsHandler Conversion Report
- Source: `public/tvshows.php`
- Converted: ❌ No
- Todos: 1
- Notes:
  - Handler remains a legacy proxy because `public/tvshows.php` uses Fluent-style builders, cached poster lookups, and extensive filtering logic that requires careful extraction.
  - TODO(2025): capture the full pagination/search workflow from `public/tvshows.php:1-225` once Fluent queries are ported to container-managed services.
