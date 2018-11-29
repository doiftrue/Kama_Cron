<?php

/**
 * Удобное добавление крон задач.
 *
 * Можно использовать параметр 'auto_activate'. Или добавить/удалить задача через:
 * - Kama_Cron::activate()   при активации плагина, при обновлении настроек.
 * - Kama_Cron::deactivate() при деактивации плагина.
 *
 * @version: 0.4
 */
class Kama_Cron {

	static $DEBUG = 0; // в рабочем режиме должно быть 0.
					   // Для дебага переходим на http://mysite.com/wp-cron.php
	static $opts;

	protected $id; // внутренняя переменная (для крон задач не используется)

	function __construct( $args ){

		if( empty($args['events']) )
			wp_die( 'ERROR: Kama_Cron events parametr not set. '. print_r(debug_backtrace(), 1) );

		$args_def = [
			'id' => implode( '--', array_keys($args['events']) ), // уникальный идентификатор по которому потом можно обращаться к настройкам

			'auto_activate' => true, // true - автоматически создаст указанное событие, при посещении админ-панели.
									 // в этом случае отдельно вызывать метод activate() не нужно.
			'events' => [
				'hook_name' => [
					'start_time'    => 0,       // с какого момента начать событие. 0 - time()
					'args'          => array(), // какие параметры передать в фукнцию крон-задачи
					'callback'      => [ __CLASS__, 'default_callback' ], // название функции крон-задачи
					'interval_name' => '',      // 'half_an_hover' можно указать уже имеющийся интервал: hourly, twicedaily, daily
					'interval_sec'  => 0,       // HOUR_IN_SECONDS / 2 (не нужно указывать, если задан уже имеющийся интервал)
					'interval_desc' => '',      // 'Каждые пол часа' (не нужно указывать, если задан уже имеющийся интервал)
				],
			],
		];

		$event_def = $args_def['events']['hook_name'];
		unset( $args_def['events'] );

		// дополним параметами класса по умолчанию
		$args = array_merge( $args_def, $args );
		foreach( $args['events'] as & $events )
			$events = array_merge( $event_def, $events );
		unset( $events );

		$args = (object) $args;

		if( ! $this->id = $args->id )
			wp_die( 'ERROR: Kama_Cron wrong init: id not set. '. print_r($args, 1) );

		self::$opts[ $this->id ] = $args;

		// after 'self::$opts' set
		add_filter( 'cron_schedules', [ $this, 'add_intervals' ] );

		// after 'cron_schedules'
		if( !empty($args->auto_activate) && is_admin() )
			self::activate( $this->id );

		foreach( $args->events as $hook => $data )
			add_action( $hook, $data['callback'] );

		if( self::$DEBUG && defined('DOING_CRON') && DOING_CRON ){
			add_action( 'wp_loaded', function(){
				echo 'Current time: '. time() ."\n\n\n".'Existing Intervals:'."\n". print_r( wp_get_schedules(), 1 ) ."\n\n\n". print_r( _get_cron_array(), 1 );
			} );
		}

	}

	function add_intervals( $schedules ){
		foreach( self::$opts[ $this->id ]->events as $hook => $data ){
			if( ! $data['interval_sec'] || isset($schedules[ $data['interval_name'] ]) )
				continue;

			$schedules[ $data['interval_name'] ] = array(
				'interval' => $data['interval_sec'],
				'display'  => $data['interval_desc'],
			);
		}

		return $schedules;
	}

	## Добавляет крон задачу.
	## Вызывается при активации плагина, можно гдето еще например на обновлении настроек.
	static function activate( $id = '' ){
		$opts = $id ? array($id => self::$opts[ $id ]) : self::$opts;

		foreach( $opts as $opt ){
			foreach( $opt->events as $hook => $data ){
				if( ! wp_next_scheduled( $hook, $data['args'] ) ){
					wp_schedule_event( ( $data['start_time'] ?: time() ), $data['interval_name'], $hook, $data['args'] );
				}
			}
		}
	}

	## Удаляет крон задачу.
	## Вызывается при дезактивации плагина.
	static function deactivate( $id = '' ){
		$opts = $id ? array($id => self::$opts[ $id ]) : self::$opts;

		foreach( $opts as $opt ){
			foreach( $opt->events as $hook => $data )
				wp_clear_scheduled_hook( $hook, $data['args'] );
		}
	}

	### Функция по умолчанию для параметра $data['callback']
	static function default_callback(){
		echo "ERROR: One of Kama_Cron callback function not set.\n\nKama_Cron::\$opts - ". print_r(self::$opts, 1) ."\n\n\n\n". print_r( _get_cron_array(), 1 );
	}

}
