# FluentPDO Migration Guide (to Aura.Sql / PDO)

## Select
**FluentPDO**
```php
$fpdo = new \FluentPDO($pdo);
$rows = $fpdo->from('users')->where('status', 1)->fetchAll();
