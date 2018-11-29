# Kama_Cron
Класс для удобного добавления крон событий в WordPress

Класс позволяет удобно создавать задачи крон, чтобы не перепутать ничего, все настройки указываются в параметре при вызове класса, остальную рутину выполняет класс. Функции-обработчики задач нужно писать отдельно!

## Примеры использования класса Kama_Cron

Инициализация класса. Добавляем код куда-нибудь в functions.php

```php
new Kama_Cron([
	'id'            => 'my_cron_jobs',
	'auto_activate' => true, // false (или удалить) чтобы повесить активацию задачи на register_activation_hook()
	'events' => array(
		// первая задача
		'wpkama_cron_func' => array(
			'callback'      => 'wpkama_cron_func', // название функции крон-задачи
			'interval_name' => '10_min',           // можно указать уже имеющийся интервал: hourly, twicedaily, daily
			'interval_sec'  => 10 * 60,            // не нужно указываеть, если задан уже имеющийся интервал
			'interval_desc' => 'Каждые 10 минут',  // не нужно указываеть, если задан уже имеющийся интервал
		),
		// вторая задача
		'wpkama_cron_func_2' => array(
			'callback'      => 'wpkama_cron_func_2',
			'start_time'    => time() + DAY_IN_SECONDS, // начать через 1 день
			'interval_name' => 'two_hours',
			'interval_sec'  => HOUR_IN_SECONDS * 2,
			'interval_desc' => 'Каждые 2 часа',
		),
		// третья задача
		'wpkama_cron_func_3' => array(
			'callback'      => 'wpkama_cron_func_3',
			'interval_name' => 'hourly', // это уже известный ВП интервал
		),
	),
]);
//Kama_Cron::$DEBUG = 1;                 // для дебага
//Kama_Cron::deactivate('my_cron_jobs'); // для удаления

// Функция для крона
function wpkama_cron_func(){
	file_put_contents( dirname(ABSPATH) .'/cron_check.txt', current_time('mysql') ."\n", FILE_APPEND );
}

function wpkama_cron_func_2(){
	// операции
}

function wpkama_cron_func_3(){
	// операции
}
```


*Еще один вызов класса* с другими задачами и активацией через хуки для плагина:

```php
// Пример активации и деактивации, если не указан параметр auto_activate
register_activation_hook( __FILE__, function(){
	Kama_Cron::activation('my_cron_jobs_2');
} );

register_deactivation_hook( __FILE__, function(){
	Kama_Cron::deactivation('my_cron_jobs_2');
} );

new Kama_Cron([
	'id'     => 'my_cron_jobs_2',
	'events' => array(
		// первая задача
		'wpkama_cron_func_4' => array(
			'callback'      => 'wpkama_cron_func_4', // название функции крон-задачи
			'interval_name' => 'twicedaily', // можно указать уже имеющийся интервал: hourly, twicedaily, daily
		),
		// вторая задача
		'wpkama_cron_func_5' => array(
			'callback'      => 'wpkama_cron_func_5',
			'interval_name' => 'two_hours',
			'interval_sec'  => HOUR_IN_SECONDS * 2,
			'interval_desc' => 'Каждые 2 часа',
		),
	),
]);

function wpkama_cron_func_4(){
	// операции
}

function wpkama_cron_func_5(){
	// операции
}
```
