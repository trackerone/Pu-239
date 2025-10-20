# IndexHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - staffpanel/index.php drives a multi-tool dashboard with AuthZ checks, cache interactions, and dynamic module loading.
  - Automatic inlining risks breaking admin workflows; handler logs the attempt and continues buffering the legacy page.
- TODOs:
  - TODO(2025): Decompose staffpanel/index.php:1-400 into discrete controllers/services before migrating handler logic.
