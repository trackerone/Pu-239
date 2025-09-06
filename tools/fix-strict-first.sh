#!/usr/bin/env bash
set -euo pipefail

ROOT="$(pwd)"
DIR="$ROOT/admin"

if [[ ! -d "$DIR" ]]; then
  echo "admin/ directory not found" >&2
  exit 0
fi

scanned=0
changed=0

find "$DIR" -type f -name '*.php' | while read -r file; do
  scanned=$((scanned+1))
  tmp="$(mktemp)"
  awk '
    BEGIN {
      in_first_php = 0
      first_php_done = 0
      declare_inserted = 0
    }
    function ltrim(s) { sub(/^[ \t\r\n]+/, "", s); return s }
    function rtrim(s) { sub(/[ \t\r\n]+$/, "", s); return s }
    function trim(s)  { return rtrim(ltrim(s)); }

    # Case: first time we see "<?php"
    /^<\?php/ && first_php_done == 0 && in_first_php == 0 {
      print $0
      print "declare(strict_types=1);"
      print ""  # blank line for readability
      in_first_php = 1
      declare_inserted = 1
      next
    }

    # Inside first PHP block: skip any existing declare(strict_types=1);
    in_first_php == 1 {
      # End of first PHP block?
      if ($0 ~ /\?\>/) {
        print $0
        in_first_php = 0
        first_php_done = 1
        next
      }
      # Skip any declare(strict_types=1); inside first block (we already inserted it)
      if ($0 ~ /declare[[:space:]]*\([[:space:]]*strict_types[[:space:]]*=[[:space:]]*1[[:space:]]*\)[[:space:]]*;/i) {
        next
      }
      print $0
      next
    }

    # Default: outside first PHP block (or after it)
    { print $0 }
  ' "$file" > "$tmp"

  if ! cmp -s "$file" "$tmp"; then
    mv "$tmp" "$file"
    changed=$((changed+1))
  else
    rm -f "$tmp"
  fi
done

echo "fix-strict-first.sh: scanned=${scanned}, changed=${changed}"
