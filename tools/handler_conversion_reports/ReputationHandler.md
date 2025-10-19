# ReputationHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - public/reputation.php currently throws a RuntimeException placeholder and lacks the historical SQL payload needed for conversion.
  - Handler now tracks the attempt metadata while continuing to buffer the legacy include.
- TODOs:
  - TODO(2025): locate the original reputation workflow and rehydrate public/reputation.php before converting the handler.
