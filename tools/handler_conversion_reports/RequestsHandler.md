# RequestsHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - public/requests.php drives a multi-action request marketplace with Validator, Session, Bonus, and Torrent services.
  - Legacy flow includes TODO markers, redirects, and complex permission checks unsuitable for automated extraction.
- TODOs:
  - TODO(2025): Decompose public/requests.php lines 1-598 into modular services and revisit handler conversion.
