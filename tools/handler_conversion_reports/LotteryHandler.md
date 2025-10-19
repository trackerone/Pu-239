# LotteryHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - Handler still buffers legacy public/lottery.php because the script chains secondary includes (lottery/*.php) and mysqli-based queries.
  - Added TODO pointing to the section that requires manual extraction and PDO migration.
- TODOs:
  - TODO(2025): refactor lottery controller + sub-actions from public/lottery.php:1-200 into dedicated services with Database::run().
