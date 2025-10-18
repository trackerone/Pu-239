# Conversion Report: classes/Http/Handlers/Public/NewAnnouncementHandler.php

- Ported the announcement composer into the handler and replaced raw `sql_query`/`sqlesc` usage with `Database::run` and bound parameters.
- Preserved the legacy preview rendering and validation flow, leaving the existing CSRF TODO in place for manual follow-up.
