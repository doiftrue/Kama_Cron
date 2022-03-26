<?php

/**
 * Convenient way to add cron tasks in WordPress.
 *
 * Usage Example:
 *
 * ```
 * new Kama_Cron( [
 *     'wpkama_cron_func' => [
 *         'callback'      => 'wpkama_cron_func', // PHP function to run on job
 *         'interval_name' => '10_min',           // you can set already registered interval: hourly, twicedaily, daily
 *         'interval_desc' => 'Every 10 min',     // no need if already registered interval is set.
 *     ],
 * ] );
 *
 * new Kama_Cron( [
 *     'single_job' => [
 *         'callback' => 'single_job_func',
 *         'start_time' => strtotime( '2021-06-05' ),
 *     ],
 * ] );
 *
 * new Kama_Cron( [
 *     'id'     => 'my_cron_jobs', // not required param
 *     'events' => [
 *         // first task
 *         'wpkama_cron_func' => [
 *             'callback'      => 'wpkama_cron_func', // PHP function to run on job
 *             'interval_name' => '10_min',           // you can set already registered interval: hourly, twicedaily, daily
 *             'interval_desc' => 'Every 10 min',     // no need if already registered interval is set.
 *         ],
 *         // second task
 *         'wpkama_cron_func_2' => [
 *             'callback'      => 'wpkama_cron_func_2',
 *             'start_time'    => time() + DAY_IN_SECONDS, // start in 1 day
 *             'interval_name' => 'two_hours',
 *             'interval_sec'  => HOUR_IN_SECONDS * 2,
 *             'interval_desc' => 'Every 2 hours',
 *         ],
 *         // third task
 *         'wpkama_cron_func_3' => [
 *             'callback'      => 'wpkama_cron_func_3',
 *             'interval_name' => 'hourly', // this is already a known WP interval
 *         ],
 *     ],
 * ] );
 * ```
 *
 * @changelog: https://github.com/doiftrue/Kama_Cron/blob/master/changelog.md
 *
 * @author Kama (wp-kama.com)
 *
 * @version 1.0
 */
class Kama_Cron {

	/**
	 * Allowed arguments for constructor.
	 *
	 * @see __construct
	 * @var array
	 */
	protected static $default_args = [
		'id' => '',
		'auto_activate' => true,
		'events' => [
			'hook_name' => [
				'callback'      => [ __CLASS__, 'default_callback' ],
				'args'          => [],
				'interval_name' => '',
				'interval_sec'  => 0,
				'interval_desc' => '',
				'start_time'    => 0,
			],
		],
	];

	/**
	 * Collects every cron options called with this class.
	 * It may be useful for any external cases.
	 *
	 * @var array
	 */
	public static $instances = [];

	/**
	 * Must be 0 on production.
	 * For debugging go to: http://site.com/wp-cron.php
	 */
	public const DEBUG = false;

	/**
	 * ID cron args. Internal - not uses for cron.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Current instance args.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @param array     $args {
	 *     Args.
	 *
	 *     @type string $id             A unique identifier that can then be used to access the settings externally.
	 *                                  Default: keys of the $events parameter.
	 *     @type bool   $auto_activate  true - automatically creates the specified event when visiting the admin panel.
	 *                                  In this case, you do not need to call {@see self::activate} method separately.
	 *     @type array  $events {
	 *        An array of events to add to the crown. The element key will be used in the cron hook.
	 *        The element value is an array of event parameters that can contain the following keys:
	 *
	 *        @type callable  $callback       The name of the cron task function.
	 *        @type mixed     $args           What parameters should be passed to the cron task function.
	 *        @type string    $interval_name  The name of the interval, for example: 'half_an_hover'.
	 *                                        You can specify the name in the following format:
	 *                                        `N_(min|hour|day|month)` — 10_min, 2_hours, 5_days, 2_month,
	 *                                        then the number will be taken as interval time.
	 *                                        You can specify an existing WP interval: hourly, twicedaily, daily,
	 *                                        then the interval time should not be set.
	 *                                        Omite this parameter to register single cron job.
	 *        @type int       $interval_sec   Interval time, for example HOUR_IN_SECONDS / 2.
	 *                                        You don't need to specify this papameter when $interval_name one of:
	 *                                        N_(min|hour|day|month), hourly, twicedaily, daily.
	 *        @type string    $interval_desc  Description of the interval, for example, 'Every half hour'.
	 *                                        You don't need to specify this param when $interval_name one of:
	 *                                        hourly, twicedaily, daily.
	 *        @type int       $start_time     UNIX timestamp. When to start the event. Default: time().
	 *     }
	 *
	 * }
	 */
	public function __construct( array $args ){

		// if passed simple array of events
		if( empty( $args['events'] ) ){
			$args = [ 'events' => $args ];
		}

		// complete passed args using defaults
		$args += [
			'id' => implode( '|', array_keys( $args['events'] ) ),
			'auto_activate' => true,
		];

		// complete each passed 'event' using defaults
		foreach( $args['events'] as $indx => $_event ){
			$args['events'][ $indx ] += self::$default_args['events']['hook_name'];
		}

		$this->args = $args;

		self::$instances[ $this->args['id'] ] = $this;

		// after self::$opts
		add_filter( 'cron_schedules', [ $this, '_add_intervals' ] );

		// after 'cron_schedules'
		// activate only in: admin | WP_CLI | DOING_CRON
		if( $args['auto_activate'] && ( is_admin() || defined( 'WP_CLI' ) || defined( 'DOING_CRON' ) ) ){
			$this->activate();
		}

		// add cron hooks
		foreach( $args['events'] as $hook_name => $task_data ){
			add_action( $hook_name, $task_data['callback'], 10, count( $task_data['args'] ) );
		}

		self::debug_info();
	}

	/**
	 * Removes cron task.
	 * Should be called on plugin deactivation.
	 *
	 * @param string $id
	 */
	public function deactivate(){

		foreach( $this->args['events'] as $hook => $data ){
			wp_clear_scheduled_hook( $hook, $data['args'] );
		}
	}

	/**
	 * Add cron task.
	 * Should be called on plugin activation. Can be called somewhere else, for example, when updating the settings.
	 *
	 * @param string $id
	 */
	private function activate(){

		foreach( $this->args['events'] as $hook => $data ){

			if( wp_next_scheduled( $hook, $data['args'] ) ){
				continue;
			}

			if( $data['interval_name'] ){
				wp_schedule_event( $data['start_time'] ?: time(), $data['interval_name'], $hook, $data['args'] );
			}
			// single event
			elseif( ! $data['start_time'] ){
				trigger_error( __CLASS__ . ' ERROR: Start time not specified for single event' );
			}
			elseif( $data['start_time'] > time() ){
				wp_schedule_single_event( $data['start_time'], $hook, $data['args'] );
			}

		}
	}

	public function _add_intervals( $schedules ){

		foreach( $this->args['events'] as $data ){

			$interval_name = $data['interval_name'];

			if(
				// it is a single event.
				! $interval_name
				// already exists
				|| isset( $schedules[ $interval_name ] )
				// internal WP intervals
				|| in_array( $interval_name, [ 'hourly', 'twicedaily', 'daily' ] )
			){
				continue;
			}

			// allow set only `interval_name` parameter like: 10_min, 2_hours, 5_days, 2_month
			if( ! $data['interval_sec'] ){

				if( preg_match( '/(\d+)[_-](min|hour|day|month)s?/', $interval_name, $mm ) ){
					$min = 60;
					$hour = $min * 60;
					$day = $hour * 24;
					$month = $day * 30;

					$data['interval_sec'] = $mm[1] * ${ $mm[2] };
				}
				else {
					/** @noinspection ForgottenDebugOutputInspection */
					wp_die( 'ERROR: Kama_Cron required event parameter `interval_sec` not set. ' . print_r( debug_backtrace(), 1 ) );
				}
			}

			$schedules[ $interval_name ] = [
				'interval' => $data['interval_sec'],
				'display'  => $data['interval_desc'] ?: $data['interval_name'],
			];
		}

		return $schedules;
	}

	public static function default_callback(){

		echo "ERROR: One of Kama_Cron callback function not set.\n\nKama_Cron::\$opts = " .
		     print_r( self::$instances, 1 ) . "\n\n\n\n_get_cron_array() =" .
		     print_r( _get_cron_array(), 1 );
	}

	private static function debug_info(): void {

		if( ! ( self::DEBUG && defined( 'DOING_CRON' ) ) ){
			return;
		}

		add_action( 'wp_loaded', function() {

			echo sprintf( "Current time: %s\n\n\nExisting Intervals:\n%s\n\n\n%s",
				time(), print_r( wp_get_schedules(), 1 ), print_r( _get_cron_array(), 1 )
			);
		} );
	}

}

