Batch 38 — Admin • Categories (FluentPDO → ExtendedPdo)
======================================================

What this action does
---------------------
- Scans `admin/` for PHP files.
- Removes `use Envms\FluentPDO\Literal;` and `@throws \Envms\FluentPDO\Exception`.
- Rewrites common FluentPDO patterns for **categories** and **peers/agents** to Aura ExtendedPdo:
  * COUNT checks for categories
  * Fetch category by id
  * Guard: torrents referencing category
  * Parent list (top-level categories)
  * Reorder categories iteration
  * Peers → agents aggregation
- Leaves a clear TODO marker where a generic `update('categories')->set($set)...` is found.

How to run
----------
1) Commit these files to your repository:
   - `.github/workflows/batch-38.yml`
   - `tools/batch-38-apply.php`

2) In GitHub → Actions → "Batch 38 - Admin Categories", click **Run workflow**.

3) The action will:
   - Run the transformer
   - Create a branch `batch-38-admin-categories`
   - Commit and push changes (if any)
   - Open a Pull Request
   - Upload a `batch-38.diff` artifact

Notes
-----
- The script is conservative and only changes known, high-signal patterns.
- Review the PR; adjust UPDATE statements where TODO markers were inserted.
- All new code comments and messages are in English.
