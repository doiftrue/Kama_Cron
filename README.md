A small class for easily adding WP Cron tasks (jobs).

This Class allow you to create WordPress Cron tasks in a quick and simple way. In order not to confuse anything, all settings are specified in the first parameter when calling the class. The class takes care of the entire routine of correctly registering the Cron task and their intervals. The task handler (the function) need to be written separately in PHP!


## Examples of using the Kama_Cron class

Repeatable job:
```php
new \Kama\WP\Kama_Cron( [
    'cron_event_name' => [
        'callback'      => 'wpkama_cron_func', // PHP function to run on job
        'interval_name' => '10 min',           // you can set already registered interval: hourly, twicedaily, daily
    ],
] );
```

Single job:
```php
new \Kama\WP\Kama_Cron( [
    'single_job' => [
        'callback' => 'single_job_func',
        'start_time' => strtotime( 'tomorrow 6am' ),
    ],
] );
```

Register manu jobs at once:
```php
new \Kama\WP\Kama_Cron( [
    'id' => 'my_cron_jobs', // not required param
    'events' => [
        // first task
        'wpkama_cron_func' => [
            'callback'      => 'wpkama_cron_func', // PHP function to run on job
            'interval_name' => '10 min',           // or WP interval: hourly, twicedaily, daily
        ],
        // second task
        'wpkama_cron_func_2' => [
            'callback'      => 'wpkama_cron_func_2',
            'interval_name' => '2 hours',
            'start_time'    => time() + DAY_IN_SECONDS, // start in 1 day
        ],
        // third task
        'wpkama_cron_func_3' => [
            'callback'      => 'wpkama_cron_func_3',
            'interval_name' => 'hourly', // WP interval
        ],
    ],
] );
```

More See here: https://wp-kama.com/1353/kama_cron
