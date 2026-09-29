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

## Соглашения
- Селекторы `$by`/`$key` (`unique`, `duplicates`, `indexBy`, `sortBy`, `sum`, `avg`, `median`, `percentile`,
  `percentage`, `min`, `max`, `ArrayHelper::keyBy/partition`): строка — всегда путь к полю, вычисление — только
  `Closure`. `sum('count')` суммирует поле `count`, а не вызывает функцию `count()`.
- `map()`, `filter()` и `where*()` сохраняют ключи; для списка 0..N вызывайте `values()`.
- `get($key, $default)` возвращает `$default` только при отсутствии ключа; сохранённый `null` остаётся `null`.
- `dot()` сохраняет пустые массивы как значения.

## Документация
Справочник по всем методам: [`docs/index.md`](docs/index.md)

## Лицензия
MIT

