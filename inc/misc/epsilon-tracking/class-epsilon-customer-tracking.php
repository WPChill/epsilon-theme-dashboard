<?php
/**
 * Epsilon Customer Tracking
 *
 * @package Epsilon Framework
 * @since   1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Customer_Tracking
 */
class Epsilon_Customer_Tracking {
	/**
	 * Allow tracking? Can be "checked off" through the dashboard
	 *
	 * @var bool
	 */
	private $allowed = true;
	/**
	 * URL where we send data
	 *
	 * @var string
	 */
	private $url = 'https://www.tamewp.com/';
	/**
	 * Data array
	 *
	 * @var array
	 */
	private $data = array(
		/**
		 * Epsilon customer tracking
		 */
		'epsilon-customer-tracking' => true,
		/**
		 * Server data
		 */
		'server'                    => array(),
		/**
		 * WordPress data
		 */
		'wordpress'                 => array(),
		/**
		 * User data
		 */
		'user'                      => array(),
		/**
		 * Behavior data
		 */
		'behavior'                  => array(),
	);

	/**
	 * Epsilon_Tracking constructor.
	 *
	 * @param $args array
	 */
	public function __construct( $args = array() ) {
		$this->allowed_tracking();

		if ( isset( $args['url'] ) ) {
			$this->url = $args['url'];
		}

		if ( $this->allowed ) {
			$this->collect_data();
		}

		$this->handle_data();
	}

	/**
	 * Creates an instance of the customer tracking service
	 *
	 * @param array $args
	 *
	 * @return Epsilon_Customer_Tracking
	 */
	public static function get_instance( $args = array() ) {
		static $inst;
		if ( ! $inst ) {
			$inst = new Epsilon_Customer_Tracking( $args );
		}

		return $inst;
	}

	/**
	 * Let's see if we're allowed to track user data
	 */
	public function allowed_tracking() {
		$allowed = get_option( 'epsilon_allowed_tracking', true );

		if ( in_array( $allowed, array( true, 1, '1' ) ) ) {
			$this->allowed = true;
		}

		if ( in_array( $allowed, array( false, 0, '0' ) ) ) {
			$this->allowed = false;
		}
	}

	/**
	 * Collect data
	 */
	private function collect_data() {
		foreach ( $this->data as $key => $arr ) {
			$class = 'Epsilon_' . $key . '_Tracking';
			if ( class_exists( $class ) ) {
				$tracking           = new $class();
				$this->data[ $key ] = $tracking->data;
			}
		}
	}

	/**
	 * Handles data, and sends it to our server
	 */
	private function handle_data() {
		new Epsilon_Request( $this->url, $this->data );
	}
}
