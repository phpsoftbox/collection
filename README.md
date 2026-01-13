# PhpSoftBox Collection

Строго типизированная коллекция для работы с массивами в стиле fluent API: выборка (`only/except`), трансформации (`map/filter/reduce`), dot-нотация для вложенных структур (`getPath/setPath`), сортировка, чанки, слияние.

## Установка
```bash
composer require phpsoftbox/collection
```

## QuickStart
```php
use PhpSoftBox\Collection\Collection;

$users = Collection::from([
    ['id' => 1, 'name' => 'Alice', 'active' => true],
    ['id' => 2, 'name' => 'Bob',   'active' => false],
]);

$names = $users
    ->filter(fn (array $u) => $u['active'])
    ->map(fn (array $u) => $u['name'])
    ->values()
    ->all();

// ['Alice']

$config = Collection::from([])
    ->setPath('db.host', 'localhost')
    ->setPath('db.port', 3306)
    ->all();

// ['db' => ['host' => 'localhost', 'port' => 3306]]
```

## Документация
Справочник по всем методам: [`docs/index.md`](docs/index.md)

## Лицензия
MIT

