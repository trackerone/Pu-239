#!/usr/bin/env bash
set -euo pipefail

# Exclusions: do not scan these paths
exclude=( -g '!vendor/**' -g '!node_modules/**' -g '!_quarantine/**' -g '!.git/**' )

fail=0

echo "==> Scan: mysqli_ / sql_query() / sqlesc() (forbidden)"
rg -n --no-heading "${exclude[@]}" -e '\bmysqli_' -e '\bsql_query\s*\(' -e '\bsqlesc\s*\(' | tee /tmp/legacy_hits.txt || true
if [[ -s /tmp/legacy_hits.txt ]]; then
  echo "::error::Forbidden legacy calls found (mysqli_ / sql_query / sqlesc)"
  fail=1
fi

echo "==> Scan: FluentPDO remnants (forbidden)"
rg -n --no-heading "${exclude[@]}" -e '\bFluentPDO\b' -e '\$fluent\s*->' | tee /tmp/fluent_hits.txt || true
if [[ -s /tmp/fluent_hits.txt ]]; then
  echo "::error::FluentPDO references are not allowed"
  fail=1
fi

echo "==> Scan: manual require_once outside bootstrap/runtime_safe (forbidden)"
rg -n --no-heading "${exclude[@]}" -e '\brequire_once\b' \
   -g '!include/bootstrap_pdo.php' \
   -g '!include/runtime_safe.php' \
   | tee /tmp/req_hits.txt || true
if [[ -s /tmp/req_hits.txt ]]; then
  echo "::error::Manual require_once outside bootstrap/runtime_safe found"
  fail=1
fi

echo "==> Scan: function_*.php files in active tree (forbidden)"
find . -type f -name 'function_*.php' \
  -not -path "./vendor/*" -not -path "./node_modules/*" -not -path "./_quarantine/*" \
  > /tmp/function_files.txt || true
if [[ -s /tmp/function_files.txt ]]; then
  echo "::error::function_*.php files present in active tree"
  cat /tmp/function_files.txt
  fail=1
fi

echo "==> Scan: global \$db usage (forbidden — use DI)"
rg -n --no-heading "${exclude[@]}" -e '\bglobal\s+\$db\b' -e '\$GLOBALS\[\s*[\'"]db[\'"]\s*\]' \
  | tee /tmp/globaldb_hits.txt || true
if [[ -s /tmp/globaldb_hits.txt ]]; then
  echo "::error::Global \$db usage found. Use DI container (Pu239\\Database) instead."
  fail=1
fi

echo "==> Scan: external CDN/jQuery (forbidden — use Vite/esbuild)"
rg -n --no-heading "${exclude[@]}" -e 'https?://(code\.jquery\.com|ajax\.googleapis\.com|cdnjs\.cloudflare\.com|cdn\.jsdelivr\.net|unpkg\.com)/' \
  | tee /tmp/cdn_hits.txt || true
if [[ -s /tmp/cdn_hits.txt ]]; then
  echo "::error::External CDN assets detected (bundle locally via Vite/esbuild)."
  fail=1
fi

echo "==> Hint: SELECT * (warning only)"
rg -n --no-heading "${exclude[@]}" -e 'SELECT\s+\*' | tee /tmp/select_star.txt || true
if [[ -s /tmp/select_star.txt ]]; then
  echo "::notice::SELECT * found (prefer explicit columns)."
fi

exit $fail
