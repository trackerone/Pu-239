# Conversion Report: classes/Http/Handlers/Public/StaffHandler.php

- **Date**: 2025-10-18
- **Batch**: offset=175 size=5
- **Source legacy script**: public/staff.php
- **Summary**:
  - Inlined the staff roster generation, fetching support and class-specific staff lists via `Pu239\Database` with prepared selects.
  - Recreated the `DoStaff` helper as a private renderer to keep the table markup reusable inside the handler body.
  - Normalised config usage to pull base URLs and image paths from `ConfigRepository` rather than raw globals.
- **Todos**:
  - TODO(2025): Confirm that `get_anonymous()` usage for online indicators aligns with desired privacy behaviour for hidden staff.
- **Notes**: Support listings now reuse the placeholder image path for lazy-loaded flag assets to mirror the legacy template expectations.
