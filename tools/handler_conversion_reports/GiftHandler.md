# GiftHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - Legacy public/gift.php remains a stub that raises RuntimeException; handler now returns HTTP 503 with a placeholder message.
  - Awaiting rehydration of the original SQL-driven gift workflow before full conversion.
- TODOs:
  - TODO(2025): rehydrate gift workflow from public/gift.php once data layer is restored.
