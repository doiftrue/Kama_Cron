1.1 (2022-04-28)
- Refactor & bug fixes.

0.4.6
- Single event registration support.
- Passing arguments simplified method.

0.4.3 (2018-12-27)
- ADD: Проверка обязательного параметра `interval_sec`. Класс выведет wp_die ошибку, если он вызыван неправильно.
- ADD: Возможность не указывать параметр `interval_sec`, а указать только параметр `interval_name` в виде `N_(min|hour|day|month)`: 10_min, 2_hours, 5_days, 2_month.

0.4.2 (2018-12-08)
- FIX: Удалил случайно определение `$this->id`.

0.4.1 (2018-12-03)
- FIX: Мелкие правки кода.
