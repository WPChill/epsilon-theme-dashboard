<?php
/**
 * Epsilon Request
 *
 * @package Epsilon Framework
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Epsilon_Request {
	/**
	 * Url for the request
	 *
	 * @var string
	 */
	private $url = '';
	/**
	 * Keyword for the url
	 *
	 * @var string
	 */
	private $keyword = 'epsilon-customer-tracking';
	/**
	 * Private data
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Epsilon_Request constructor.
	 *
	 * @param string $url
	 * @param array  $data
	 */
	public function __construct( $url = 'https://wwww.machothemes.com', $data = array() ) {
		$this->url  = $url;
		$this->data = $data;

		$this->generate_url();
		$this->schedule_send();
	}

	/**
	 * Generate the url
	 */
	private function generate_url() {
		$this->url = $this->url . '?' . http_build_query( array( $this->keyword => 'data' ) );
	}

	/**
	 * Check database, we need to know if we init the sending now or post pone it
	 */
	private function schedule_send() {
		$last = $this->last_request();
		/**
		 * In case no request has been made, do it now
		 */
		if ( ! $last ) {
			$this->do_it();

			return;
		}

		/**
		 * Get last request, and format it properly
		 */
		$last_request = DateTime::createFromFormat( 'U', $last );
		/**
		 * Get today date
		 */
		$today = new DateTime( 'today' );
		/**
		 * Check how many days passed since the last request
		 */
		$interval = $today->diff( $last_request )->format( '%d' );
		/**
		 * If 7 days or more passed, ping our server
		 */
		if ( 7 <= absint( $interval ) ) {
			$this->do_it();
		}
	}

	/**
	 * Get the last request time
	 */
	private function last_request() {
		return get_option( 'epsilon_customer_tracking_last_request', false );
	}

	/**
	 * Save the current request time
	 */
	private function save_request_time() {
		update_option( 'epsilon_customer_tracking_last_request', time() );
	}

	/**
	 * Make the request
	 *
	 * @return WP_Error | boolean
	 */
	private function do_it() {
		$request = wp_remote_post( $this->url, array(
			'method'      => 'POST',
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'body'        => $this->data,
			'user-agent'  => 'MT/EPSILON-CUSTOMER-TRACKING/' . get_bloginfo( 'url' )
		) );


		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$this->save_request_time();

		return true;
	}
}
