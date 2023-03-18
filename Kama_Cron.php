<?php

namespace Kama\WP;

/**
 * Convenient way to add cron tasks in WordPress.
 *
 * INFO: For debugging go to: http://site.com/wp-cron.php
 *
 * Usage Example:
 *
 * ```php
 * new \Kama\WP\Kama_Cron( [
 *     'wpkama_cron_func' => [
 *         'callback'      => 'wpkama_cron_func', // PHP function to run on job
 *         'interval_name' => '10 min',           // you can set already registered interval: hourly, twicedaily, daily
 *     ],
 * ] );
 *
 * new \Kama\WP\Kama_Cron( [
 *     'single_job' => [
 *         'callback' => 'single_job_func',
 *         'start_time' => strtotime( '2021-06-05' ),
 *     ],
 * ] );
 *
 * new \Kama\WP\Kama_Cron( [
 *     'id'     => 'my_cron_jobs', // not required param
 *     'events' => [
 *         // first task
 *         'wpkama_cron_func' => [
 *             'callback'      => 'wpkama_cron_func', // PHP function to run on job
 *             'interval_name' => '10 minutes',       // you can set already registered interval: hourly, twicedaily, daily
 *         ],
 *         // second task
 *         'wpkama_cron_func_2' => [
 *             'callback'      => 'wpkama_cron_func_2',
 *             'interval_name' => '2 hours',
 *             'start_time'    => strtotime('tomorrow 6am'), // start tomorrow at 6:00am + site gtm_offset
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
 * @version 1.4
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
	 * Current instance args.
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * Container for every instance.
	 * To have acces to instance use `Kama_Cron::get()` method.
	 *
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * ID cron args. Internal - not uses for cron.
	 *
	 * @var string
	 */
	protected $id = '';

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
	 *                                        `N (min|hour|day|month)s` — 10 minutes, 2 hours, 5 days, 2 months,
	 *                                        then the number will be taken to 'interval_sec' parameter.
	 *                                        You can specify an existing WP interval: hourly, twicedaily, daily.
	 *                                        Omite this parameter to register single cron job.
	 *        @type int       $interval_sec   Interval time, for example HOUR_IN_SECONDS / 2.
	 *                                        You don't need to specify this papameter when $interval_name one of:
	 *                                        N (min|hour|day|month)s, hourly, twicedaily, daily.
	 *        @type string    $interval_desc  Description of the interval, for example, 'Every half hour'.
	 *                                        You don't need to specify this param when $interval_name one of:
	 *                                        N (min|hour|day|month)s, hourly, twicedaily, daily.
	 *        @type int       $start_time     UNIX timestamp. When to start the event. Default: time(). If you need to start event
	 *                                        at, for example, tomorrow 6 AM (with site time), you must get timestamp and fix
	 *                                        it with site gtm_offset: `strtotime('tomorrow 6am') - (int) get_option('gtm_offset')`.
	 *     }
	 *
	 * }
	 */
	public function __construct( array $args ){

		$this->set_args( $args );
		$this->init();

		self::$instances[ $this->args['id'] ] = $this;
	}

	/**
	 * Gets instance by id.
	 */
	public static function get( string $instance_id ): self {

		return self::$instances[ $instance_id ] ?? new self( [ 'id' => 'stub', 'events' => [] ] );
	}

	protected function set_args( array $args ): void {

		// if direct events data passed
		if( ! isset( $args['events'] ) ){
			$args = [ 'events' => $args ];
		}

		// add default values to $args
		$args += [
			'id' => implode( '|', array_keys( $args['events'] ) ),
			'auto_activate' => self::$default_args['auto_activate'],
		];

		// add default values to each "event"
		foreach( $args['events'] as $indx => $_event ){
			$args['events'][ $indx ] += self::$default_args['events']['hook_name'];
		}

		$this->args = $args;
	}

	protected function init(): void {

		if( ! $this->args['events'] ){
			return;
		}

		add_filter( 'cron_schedules', [ $this, 'add_intervals_callback' ] );

		// add cron hooks
		foreach( $this->args['events'] as $hook_name => $task_data ){
			add_action( $hook_name, $task_data['callback'], 10, count( $task_data['args'] ) );
		}

		// after 'cron_schedules'
		if( $this->args['auto_activate'] && ( is_admin() || defined( 'WP_CLI' ) || defined( 'DOING_CRON' ) ) ){
			$this->activate();
		}
	}

	/**
	 * Removes all cron tasks of current instance.
	 * Should be called on plugin deactivation.
	 */
	public function deactivate(): void {

		foreach( $this->args['events'] as $hook => $data ){
			wp_clear_scheduled_hook( $hook, $data['args'] );
		}
	}

	/**
	 * Add all cron tasks of current instance.
	 * Should be called on plugin activation.
	 * Can be called somewhere else, for example, when updating the settings.
	 */
	public function activate(): void {

		foreach( $this->args['events'] as $hook => $data ){

			if( wp_next_scheduled( $hook, $data['args'] ) ){
				continue;
			}

			if( $data['interval_name'] ){
				$wp_error = wp_schedule_event( $data['start_time'] ?: time(), $data['interval_name'], $hook, $data['args'], true );
			}
			// single event
			elseif( ! $data['start_time'] ){
				$msg = "ERROR: nor `interval_name` OR `start_time` was not set for the Kama Cron event `$hook`.";
				_doing_it_wrong( __METHOD__, $msg, '' );
			}
			elseif( $data['start_time'] > time() ){
				$wp_error = wp_schedule_single_event( $data['start_time'], $hook, $data['args'], true );
			}

			if ( is_wp_error( $wp_error ?? null ) ) {
				trigger_error( __METHOD__ . ': ' . $wp_error->get_error_message() );
			}
		}
	}

	/**
	 * @private
	 */
	public function add_intervals_callback( $schedules ){

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

				if( preg_match( '/^(\d+)[ _-](min(?:ute)?|hour|day|month)s?/', $interval_name, $mm ) ){
					$min = $minute = 60;
					$hour = $min * 60;
					$day = $hour * 24;
					$month = $day * 30;

					$data['interval_sec'] = $mm[1] * ${ $mm[2] };
				}
				else {
					echo 'ERROR: Kama_Cron required `interval_sec` parameter not set.';
					/** @noinspection ForgottenDebugOutputInspection */
					echo "\n\n". debug_print_backtrace();
					die();
				}
			}

			$schedules[ $interval_name ] = [
				'interval' => $data['interval_sec'],
				'display'  => $data['interval_desc'] ?: $data['interval_name'],
			];
		}

		return $schedules;
	}

	public static function default_callback(): void {

		echo 'ERROR: One of Kama_Cron callback function not set.';
		echo "\n\nKama_Cron::\$instance = " . print_r( self::$instances, true );
		echo "\n\n\n\n_get_cron_array() =" . print_r( _get_cron_array(), true );
	}

}
