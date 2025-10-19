# OffersHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - public/offers.php coordinates Offer, Comment, Image, and Session services with validator-driven branching that exceeds the safe auto-convert heuristics.
  - Handler keeps the buffered legacy include and documents the outstanding extraction work.
- TODOs:
  - TODO(2025): perform manual extraction of public/offers.php:1-420 covering CRUD operations and validation paths.
