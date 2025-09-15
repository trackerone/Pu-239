## cleanup

### Files
- `cleanup/optimizedb.php`: removed concatenated SQL, bound `:minwaste`, and backticked table names for `OPTIMIZE TABLE`.
- `cleanup/processkill_update.php`: replaced concatenated `KILL` query with bound `:id` parameter.

### Removed legacy patterns
| pattern | before | after |
|---|---|---|
| `mysqli_*` | 0 | 0 |
| `sql_query()` | 0 | 0 |
| `sqlesc()` | 0 | 0 |
| `mysqli_fetch_*` | 0 | 0 |
| `mysqli_num_rows` | 0 | 0 |
| `mysqli_insert_id` | 0 | 0 |

### Transactions
- none

### COUNT/LIKE/LIMIT/IN bindings
- none

### Verification
```bash
$ rg "mysqli_|sql_query\\(|sqlesc\\(|mysqli_fetch|mysqli_num_rows|mysqli_insert_id" cleanup --glob '!cleanup/_quarantine/**'
```
No matches found.
