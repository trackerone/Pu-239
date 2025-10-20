# FriendsHandler conversion (2025-10-20)

- Status: Converted
- Notes:
  - Embedded the lightweight public/friends.php stub inside the handler with routing guards.
  - Maintains the RuntimeException placeholder until the friends SQL routines are restored.
- TODOs:
  - TODO(2025): rebuild friends management workflow from public/friends.php legacy stub
