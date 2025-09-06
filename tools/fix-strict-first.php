name: Batch 43.7A - Admin strict_types first

on:
  workflow_dispatch:

jobs:
  run-batch-43_7A:
    runs-on: ubuntu-latest
    permissions:
      contents: write
      pull-requests: write
    env:
      BASE_BRANCH: ${{ github.event.repository.default_branch }}
      UNIQUE_BRANCH: batch-43_7A-${{ github.run_id }}-${{ github.run_attempt }}
      GH_REPO: ${{ github.repository }}
      GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}
    steps:
      - name: Checkout
        uses: actions/checkout@v4
        with: { fetch-depth: 0 }

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Fix strict_types first in admin/*.php
        run: php ./tools/fix-strict-first.php

      - name: Commit & push branch
        run: |
          set -euxo pipefail
          git config user.name "batch-bot"
          git config user.email "batch-bot@users.noreply.github.com"
          git checkout -B "$UNIQUE_BRANCH" "origin/$BASE_BRANCH"
          git add admin/*.php tools/reports/fix-strict-summary.txt || true
          git commit -m "batch43.7A: enforce strict_types at line 2 in admin/" || true
          git push --set-upstream origin "$UNIQUE_BRANCH"

      - name: Create PR via API
        run: |
          set -euxo pipefail
          API="https://api.github.com/repos/${GH_REPO}/pulls"
          DATA=$(jq -n \
            --arg title "Batch 43.7A: Admin strict_types first" \
            --arg head  "$UNIQUE_BRANCH" \
            --arg base  "$BASE_BRANCH" \
            --arg body  "Ensure all admin/*.php have <?php on line 1 and declare(strict_types=1); on line 2. Remove any duplicate declare lines. See tools/reports/fix-strict-summary.txt for details." \
            '{title:$title, head:$head, base:$base, body:$body, maintainer_can_modify:true, draft:false}')
          curl -sS -X POST -H "Authorization: Bearer ${GH_TOKEN}" -H "Accept: application/vnd.github+json" \
               "${API}" -d "${DATA}" | jq .
