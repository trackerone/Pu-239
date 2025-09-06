Batch 39 — Admin Leftovers (FluentPDO → ExtendedPdo)
====================================================

What this action does
---------------------
- Scans `admin/` for PHP files and migrates common FluentPDO patterns:
  * Peers/Agents aggregation → pure SQL + $this->db->fetchAll
  * Categories list ordered → SELECT * ORDER BY ordered, id
  * Categories children by parent → SELECT * WHERE parent_id = :pid
  * Delete category by id → explicit DELETE with named param
- Removes `use Envms\FluentPDO\Literal;`
- Replaces `@throws \Envms\FluentPDO\Exception` with `@throws \PDOException`
- Leaves a TODO marker for generic `update('categories')->set($set)...`

How to run
----------
1) Add files:
   - `.github/workflows/batch-39.yml`
   - `tools/batch-39-apply.php`
2) Run the workflow in GitHub Actions.
3) A branch is created automatically using a unique name; a PR is opened if there are changes
   (or if you set `force_pr = true`).

Artifacts
---------
- `batch-39-diff` — the unified diff
- `batch-39-changed-files` — changed.txt + status.txt for transparency
