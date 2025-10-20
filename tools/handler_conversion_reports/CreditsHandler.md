# CreditsHandler conversion (2025-10-20)

- Status: Converted
- Notes:
  - Ported the public/credits.php stub flow into the handler with the standard bootstrap guard.
  - Retained the RuntimeException placeholder while the legacy credit SQL remains unavailable.
- TODOs:
  - TODO(2025): rehydrate credits view from public/credits.php once SQL definitions return
