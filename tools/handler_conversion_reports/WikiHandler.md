# WikiHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - public/wiki.php orchestrates validators, session state, audit logging, and media uploads across several services.
  - Automated extraction deemed risky; handler keeps buffered legacy execution with diagnostic logging.
- TODOs:
  - TODO(2025): Manually port public/wiki.php:1-400 into service methods with dedicated request/validation layers.
