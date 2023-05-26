A small class for easy adding WP Cron tasks (jobs).

This Class allow you to create WordPress Cron tasks in a quick and simple way. In order not to confuse anything, all tasks settings are specified in the first parameter when crating the class instance. The class takes care of all the routine for proper registration of Cron tasks and their intervals. The task handler (callback function) must already exist in PHP or need to be written separately!


Requirements
------------

- PHP: >=7.1
- WordPress: >=5.0 



Examples
--------

> By default, the tasks are registered automatically (it works very fast) when you visit the admin panel OR at WP_CLI request OR at any cron request. If automatic registration is not needed, specify parameter ``auto_activate' => false`` and activate tasks manually using method ``activate()``. See the example below.

> INFO: You can call ``Kama_Cron`` at the earliest stage of loading WP, starting from the earliest `muplugins_loaded` hook.

> IMPORTANT: The ``Kama_Cron'' code should also work in cron requests, because it registers the necessary WP hooks that will be executed during cron requests. In other words, you CANNOT register a cron job with this code and delete it.


### Repeatable job

#### Use the known WP interval (hourly):

```php
new \Kama\WP\Kama_Cron( [
	'wpkama_core_data_check_update' => [
		'callback'      => 'wpkama_core_data_check_update',
		'interval_name' => 'hourly',
	]
] );

/**
 * Cron callback (handler) function.
 */
function wpkama_core_data_check_update(){
	// your code to do the cron job
}
```

``wpkama_core_data_check_update`` - it's an internal name of WP hook you don't need to use it anywhere in your code - just specify unique understandable name (I think it's a good idea to name it same as callback function).

#### Use the unknown WP interval (10 minutes):

```php
new \Kama\WP\Kama_Cron( [
	'wpkama_cron_hook' => [
		'callback'      => 'wpkama_cron_func',
		'interval_name' => '10 minutes',
	],
] );

function wpkama_cron_func(){
	// your code to do the cron job
}
```

> In this case the class will parse the string ``10 minutes`` and fill in the ``interval_sec`` and ``interval_desc`` parameters itself.

> In ``interval_name`` you can specify the name in the following format: `N (min|minutes|hour|day|month)s` — ``10 minutes``, ``2 hours``, ``5 days``, ``2 months``, then the number will be taken to 'interval_sec' parameter. OR you can specify an existing WP interval: ``hourly``, ``twicedaily``, ``daily``.



### Single job

#### Single job (once):

```php
new \Kama\WP\Kama_Cron( [
    'single_job' => [
        'callback' => 'single_job_func',
        'start_time' => 1679205600, //= strtotime('tomorrow 6am') - (int) get_option('gtm_offset'),
    ],
] );
```

#### Repeatable Single job (once at time):

```php
new \Kama\WP\Kama_Cron( [
    'single_job' => [
        'callback' => 'single_job_func',
        // start event every day at 6am by site time
        'start_time' => strtotime('tomorrow 6am') - (int) get_option('gtm_offset'),
    ],
] );
```


### Register more than one task at once:

Let's create 4 task with different intervals. Tasks are registered automatically (it works very fast) when you visit admin panel OR from CLI OR from Cron request. 

Add following code anywhere, for example in `functions.php` OR in plugin.

```php
new \Kama\WP\Kama_Cron( [
	'id'     => 'my_cron_jobs',
	'events' => [
		// first task
		'wpkama_cron_func' => [
			'callback'      => [ MyCronCallbacks::class, 'wpkama_cron_func' ],
			'interval_name' => '10 min',
		],
		// 
		'wpkama_cron_func_2' => [
			'callback'      => [ MyCronCallbacks::class, 'wpkama_cron_func_2' ],
			'interval_name' => '2 hours',
			'start_time'    => 1679205600, // start at specified UNIX time
		],
		// second task
		'wpkama_cron_func_3' => [
			'callback'      => [ MyCronCallbacks::class, 'wpkama_cron_func_3' ],
			'interval_name' => '2 hours',
			'start_time'    => strtotime('tomorrow 6am'), // run at 6 a.m. (site time will be added to this time)
		],
		// 
		'wpkama_cron_func_4' => [
			'callback'      => [ MyCronCallbacks::class, 'wpkama_cron_func_4' ],
			'interval_name' => 'hourly', // this is already a known WP interval
		],
	],
] );

class MyCronCallbacks {

	public static function wpkama_cron_func(){
		$file = dirname( ABSPATH ) .'/__cron_check.txt';
		$content = current_time('mysql') ."\n";
		file_put_contents( $file, $content, FILE_APPEND );
	}
	
	public static function wpkama_cron_func_2(){
		// do something
	}
	
	public static function wpkama_cron_func_3(){
		// do something
	}
	
	public static function wpkama_cron_func_4(){
		// do something
	}
}
```



### Register tasks when activating the plugin

The code below shows how to activate and deactivate tasks customly - when activating/deactivating the plugin.

IMPORTANT: in this case the parameter auto_activate must be false: `'auto_activate' => false`!

```php
// Example of activation and deactivation when the `auto_activate = false`
register_activation_hook( __FILE__, function(){
	\Kama\WP\Kama_Cron::get( 'my_cron_jobs_2' )->activate();
} );

register_deactivation_hook( __FILE__, function(){
	\Kama\WP\Kama_Cron::get( 'my_cron_jobs_2' )->deactivate();
} );

new \Kama\WP\Kama_Cron( [
	'id' => 'my_cron_jobs_2',
	'auto_activate' => false, // !IMPORTANT
	'events' => [
		'wpkama_cron_func_4' => [
			'callback'      => 'wpkama_cron_func_4',
			'interval_name' => 'twicedaily',
		],
		'wpkama_cron_func_5' => [
			'callback'      => 'wpkama_cron_func_5',
			'interval_name' => '2 hours',
		],
	],
] );

function wpkama_cron_func_4(){
	// code here
}

function wpkama_cron_func_5(){
	// code here
}
```

> INFO: deactivate() method will deactivate all the jobs from current pack (in the example above these are two jobs).

--

Plugin page: https://wp-kama.com/1353/kama_cron
