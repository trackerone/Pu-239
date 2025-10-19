# InviteHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - Legacy public/invite.php is currently a stub awaiting SQL/data rehydration; handler now responds with HTTP 503.
  - No safe legacy logic available for embedding.
- TODOs:
  - TODO(2025): restore invite issuance workflow from public/invite.php once legacy SQL is recovered.
