A small class for easily adding WP Cron tasks (jobs).

This Class allow you to create WordPress Cron tasks in a quick and simple way. In order not to confuse anything, all settings are specified in the first parameter when calling the class. The class takes care of the entire routine of correctly registering the Cron task and their intervals. The task handler (the function) need to be written separately in PHP!


## Examples of using the Kama_Cron class

```php
new \Kama\WP\Kama_Cron( [
    'wpkama_cron_func' => [
        'callback'      => 'wpkama_cron_func', // PHP function to run on job
        'interval_name' => '10_min',           // you can set already registered interval: hourly, twicedaily, daily
        'interval_desc' => 'Every 10 min',     // no need if already registered interval is set.
    ],
] );
```

```php
new \Kama\WP\Kama_Cron( [
    'single_job' => [
        'callback' => 'single_job_func',
        'start_time' => strtotime( '2021-06-05' ),
    ],
] );
```

```php
new \Kama\WP\Kama_Cron( [
    'id'     => 'my_cron_jobs', // not required param
    'events' => [
        // first task
        'wpkama_cron_func' => [
            'callback'      => 'wpkama_cron_func', // PHP function to run on job
            'interval_name' => '10_min',           // you can set already registered interval: hourly, twicedaily, daily
            'interval_desc' => 'Every 10 min',     // no need if already registered interval is set.
        ],
        // second task
        'wpkama_cron_func_2' => [
            'callback'      => 'wpkama_cron_func_2',
            'start_time'    => time() + DAY_IN_SECONDS, // start in 1 day
            'interval_name' => 'two_hours',
            'interval_sec'  => HOUR_IN_SECONDS 2,
            'interval_desc' => 'Every 2 hours',
        ],
        // third task
        'wpkama_cron_func_3' => [
            'callback'      => 'wpkama_cron_func_3',
            'interval_name' => 'hourly', // this is already a known WP interval
        ],
    ],
] );
```

More See here: https://wp-kama.com/1353/kama_cron
