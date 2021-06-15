<?php

/**
 * Convenient way to add cron tasks in WordPress.
 *
 * Changelog: https://github.com/doiftrue/Kama_Cron/blob/master/changelog.md
 *
 * @author Kama (wp-kama.com)
 *
 * @version 0.4.5
 */
class Kama_Cron {

	static $DEBUG = 0; // must be 0 on production. For debug go to: http://mysite.com/wp-cron.php

	static $opts;

	protected $id; // internal (not used for cron)

	/**
	 * Constructor.
	 *
	 * @param array $args {
	 *     Args.
	 *
	 *     @type string $id             A unique identifier that can then be used to access the settings.
	 *                                  Default: keys of the $events parameter.
	 *     @type bool   $auto_activate  true - automatically creates the specified event when visiting the admin panel.
	 *                                  In this case, you do not need to call the activate() method separately.
	 *     @type array  $events         {
	 *        An array of events to add to the crown. The element key will be used in the cron hook.
	 *        The element value is an array of event parameters that can contain the following keys:
	 *
	 *        @type callable  $callback       The name of the crown task function.
	 *        @type mixed     $args           What parameters should be passed to the crown task function.
	 *        @type string    $interval_name  The name of the interval, for example: 'half_an_hover'.
	 *                                        You can specify such name `N_(min|hour|day|month)`: 10_min, 2_hours, 5_days, 2_month,
	 *                                        then the number will be taken as interval time.
	 *                                        You can specify an existing WP interval: hourly, twicedaily, daily,
	 *                                        then the interval time should not be set.
	 *        @type int       $interval_sec   Interval time, for example HOUR_IN_SECONDS / 2. You don't need to specify when
	 *                                        $interval_name = N_(min|hour|day|month), hourly, twicedaily, daily.
	 *        @type string    $interval_desc  Description of the interval, for example, 'Every half hour'. You don't need to specify when
	 *                                        $interval_name = hourly, twicedaily, daily.
	 *        @type int       $start_time     UNIX timestamp. When to start the event. By default: time().
	 *     }
	 *
	 * }
	 */
	public function __construct( $args ){

		if( empty( $args['events'] ) ){
			wp_die( 'ERROR: Kama_Cron `events` parameter not set. ' . print_r( debug_backtrace(), 1 ) );
		}

		$args_def = [
			'id' => implode( '--', array_keys( $args['events'] ) ),

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

		$event_def = $args_def['events']['hook_name'];
		unset( $args_def['events'] );

		// complete parameters using defaults
		$args = array_merge( $args_def, $args );
		foreach( $args['events'] as & $events ){
			$events = array_merge( $event_def, $events );
		}
		unset( $events );

		$args = (object) $args;

		$this->id = $args->id;

		self::$opts[ $this->id ] = $args;

		// after self::$opts
		add_filter( 'cron_schedules', [ $this, 'add_intervals' ] );

		// after 'cron_schedules'
		if( ! empty( $args->auto_activate ) && is_admin() ){
			self::activate( $this->id );
		}

		foreach( $args->events as $hook => $data ){
			add_action( $hook, $data['callback'] );
		}

		if( self::$DEBUG && defined( 'DOING_CRON' ) && DOING_CRON ){

			add_action( 'wp_loaded', function(){
				echo 'Current time: ' . time() . "\n\n\n" . 'Existing Intervals:' . "\n" .
				     print_r( wp_get_schedules(), 1 ) . "\n\n\n" . print_r( _get_cron_array(), 1 );
			} );
		}
	}

	public function add_intervals( $schedules ){

		foreach( self::$opts[ $this->id ]->events as $hook => $data ){
			$_name = $data['interval_name'];

			if( isset( $schedules[ $_name ] ) || in_array( $_name, [ 'hourly', 'twicedaily', 'daily' ] ) ){
				continue;
			}

			// allow set only `interval_name` parameter like: 10_min, 2_hours, 5_days, 2_month
			if( empty( $data['interval_sec'] ) ){

				if( preg_match( '/(\d+)[_-](min|hour|day|month)s?/', $_name, $mm ) ){
					$min = 60;
					$hour = $min * 60;
					$day = $hour * 24;
					$month = $day * 30;
					$data['interval_sec'] = $mm[1] * ${$mm[2]};
				}
				else{
					wp_die( 'ERROR: Kama_Cron required event parameter `interval_sec` not set. ' . print_r( debug_backtrace(), 1 ) );
				}
			}

			$schedules[ $_name ] = [
				'interval' => $data['interval_sec'],
				'display'  => $data['interval_desc'],
			];
		}

		return $schedules;
	}

	/**
	 * Add cron task.
	 * Should be called on plugin activation. Can be called somewhere else, for example, when updating the settings.
	 *
	 * @param string $id
	 */
	static function activate( $id = '' ){

		$opts = $id ? [ $id => self::$opts[ $id ] ] : self::$opts;

		foreach( $opts as $opt ){

			foreach( $opt->events as $hook => $data ){

				if( ! wp_next_scheduled( $hook, $data['args'] ) ){
					wp_schedule_event( ( $data['start_time'] ?: time() ), $data['interval_name'], $hook, $data['args'] );
				}
			}
		}
	}

	/**
	 * Removes cron task.
	 * Should be called on plugin deactivation.
	 *
	 * @param string $id
	 */
	static function deactivate( $id = '' ){
		
		$opts = $id ? [ $id => self::$opts[ $id ] ] : self::$opts;

		foreach( $opts as $opt ){
			
			foreach( $opt->events as $hook => $data ){
				wp_clear_scheduled_hook( $hook, $data['args'] );
			}
		}
	}

	static function default_callback(){
		
		echo "ERROR: One of Kama_Cron callback function not set.\n\nKama_Cron::\$opts = " .
		     print_r( self::$opts, 1 ) . "\n\n\n\n_get_cron_array() =" .
		     print_r( _get_cron_array(), 1 );
	}

}

