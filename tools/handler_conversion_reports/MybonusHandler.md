# MybonusHandler conversion (2025-10-20)

- Status: Deferred
- Notes:
  - public/mybonus.php implements the karma bonus store with nested transactions, validator usage, and ExtendedPDO placeholders.
  - Significant POST branching and side effects require manual review.
- TODOs:
  - TODO(2025): Extract public/mybonus.php lines 1-841 into dedicated bonus services prior to handler modernization.
