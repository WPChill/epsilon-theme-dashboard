<?php
/**
 * Epsilon Onboarding Ajax Handler
 *
 * @package Epsilon Framework
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Epsilon_Dashboard_Ajax {
	/**
	 * Epsilon_Onboarding_Ajax_Handler constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_epsilon_dashboard_ajax_callback', array(
			$this,
			'epsilon_dashboard_ajax_callback',
		) );
		
		add_action( 'wp_ajax_nopriv_epsilon_dashboard_ajax_callback', array(
			$this,
			'not_logged_notice',
		) );
	}

	/**
	 * AJAX Handler
	 */
	public function epsilon_dashboard_ajax_callback() {
		if ( is_string( $_POST['args'] ) ) {
			$_POST['args'] = json_decode( wp_unslash( $_POST['args'] ), true );
		}
		$this->_check_nonce();
		$this->_check_user_role();
		$args_action = array_map( 'sanitize_text_field', wp_unslash( $_POST['args']['action'] ) );
		$this->_check_structure( $args_action );
		$this->_check_class( $args_action[0] );

		$class  = $args_action[0];
		$method = $args_action[1];
		$args   = array();

		if ( is_array( $_POST['args']['args'] ) ) {
			$args = Epsilon_Sanitizers::array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['args']['args'] ) );
		}

		$response = $class::$method( $args );

		if ( is_array( $response ) ) {
			$this->send_response( $response );
		}

		if ( 'ok' === $response ) {
			$this->send_response(
				array(
					'status'  => true,
					'message' => 'ok',
				)
			);
		}

		$this->send_response(
			array(
				'status'  => false,
				'message' => 'nok',
			)
		);
	}

	/**
	 * Not logged in
	 *
	 * @return string
	 */
	public static function not_logged_notice() {
		return esc_html__( 'You must be logged in to do that!', 'epsilon-framework' );
	}

	/**
	 * Checks nonce
	 *
	 * @return bool
	 */
	private function _check_nonce() {
		if ( isset( $_POST['args'], $_POST['args']['nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['args']['nonce'] ), 'epsilon_dashboard_nonce' ) ) {

			$this->send_response(
				array(
					'status' => false,
					'error'  => esc_html__( 'Not allowed', 'epsilon-framework' ),
				)
			);
		}

		return true;
	}

	/**
	 * Check if class exists
	 *
	 * @param string $class
	 *
	 * @return bool
	 */
	private function _check_class( $class = '' ) {
		if ( ! class_exists( $class ) ) {
			$this->send_response(
				array(
					'status' => false,
					'error'  => esc_html__( 'Class does not exist', 'epsilon-framework' ),
				)
			);
		}

		return true;
	}

	/**
	 * Check array structure
	 *
	 * @param $action
	 *
	 * @return bool
	 */
	private function _check_structure( $action ) {
		if ( count( $action ) !== 2 ) {
			$this->send_response(
				array(
					'status' => false,
					'error'  => esc_html__( 'Not allowed', 'epsilon-framework' ),
				)
			);
		}

		return true;
	}

	/**
	 * Check user role
	 */
	private function _check_user_role() {
		$super_admin = is_super_admin( get_current_user_id() );
		if ( ! $super_admin ) {
			$this->send_response(
				array(
					'status' => false,
					'error'  => esc_html__( 'You must be a Super User!', 'epsilon-framework' ),
				)
			);
		}

		return true;
	}

	/**
	 * Send response
	 *
	 * @param array $args
	 */
	public function send_response( $args = array() ) {
		wp_die( wp_json_encode( $args ) );
	}
}
