<?php

/**
 * Class ActionScheduler
 * @codeCoverageIgnore
 */
abstract class ActionScheduler {
	private static $plugin_file = '';
	/** @var ActionScheduler_ActionFactory */
	private static $factory = NULL;

	public static function factory() {
		if ( !isset(self::$factory) ) {
			self::$factory = new ActionScheduler_ActionFactory();
		}
		return self::$factory;
	}

	public static function store() {
		return ActionScheduler_Store::instance();
	}

	public static function logger() {
		return ActionScheduler_Logger::instance();
	}

	public static function runner() {
		return ActionScheduler_QueueRunner::instance();
	}

	public static function admin_view() {
		return ActionScheduler_AdminView::instance();
	}

	public static function get_datetime_object( $when ) {
		$when = empty($when) ? time() : $when;
		if ( is_object($when) && $when instanceof DateTime ) {
			$date = $when;
		} elseif ( is_numeric( $when ) ) {
			$date = new DateTime( '@'.$when );
		} else {
			$date = new DateTime( $when );
		}
		return $date;
	}

	/**
	 * Get the absolute system path to the plugin directory, or a file therein
	 * @static
	 * @param string $path
	 * @return string
	 */
	public static function plugin_path( $path ) {
		$base = dirname(self::$plugin_file);
		if ( $path ) {
			return trailingslashit($base).$path;
		} else {
			return untrailingslashit($base);
		}
	}

	/**
	 * Get the absolute URL to the plugin directory, or a file therein
	 * @static
	 * @param string $path
	 * @return string
	 */
	public static function plugin_url( $path ) {
		return plugins_url($path, self::$plugin_file);
	}

	public static function autoload( $class ) {
		$d = DIRECTORY_SEPARATOR;
		if ( strpos( $class, 'ActionScheduler' ) === 0 ) {
			$dir = self::plugin_path('classes'.$d);
		} elseif ( strpos( $class, 'CronExpression' ) === 0 ) {
			$dir = self::plugin_path('lib'.$d.'cron-expression'.$d);
		} else {
			return;
		}

		if ( file_exists( $dir.$class.'.php' ) ) {
			include( $dir.$class.'.php' );
			return;
		}
	}

	/**
	 * Initialize the plugin
	 *
	 * @static
	 * @param string $plugin_file
	 * @return void
	 */
	public static function init( $plugin_file ) {
		self::$plugin_file = $plugin_file;
		spl_autoload_register( array( __CLASS__, 'autoload' ) );

		$store = self::store();
		add_action( 'init', array( $store, 'init' ), 1, 0 );

		$logger = self::logger();
		add_action( 'init', array( $logger, 'init' ), 1, 0 );

		$runner = self::runner();
		add_action( 'init', array( $runner, 'init' ), 1, 0 );

		$admin_view = self::admin_view();
		add_action( 'init', array( $admin_view, 'init' ), 0, 0 ); // run before $store::init()

		require_once( self::plugin_path('functions.php') );
	}


	final public function __clone() {
		trigger_error("Singleton. No cloning allowed!", E_USER_ERROR);
	}

	final public function __wakeup() {
		trigger_error("Singleton. No serialization allowed!", E_USER_ERROR);
	}

	final private function __construct() {}
}
 