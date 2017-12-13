<?php
/**
 * Epsilon Onboarding
 *
 * @package Epsilon Framework
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Onboarding
 */
class Epsilon_Onboarding {
	/**
	 * Recommended plugins
	 *
	 * @var array
	 */
	public $plugins = array();
	/**
	 * Steps
	 *
	 * @var array
	 */
	public $steps = array();

	/**
	 * Epsilon_Onboarding constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $k => $v ) {

			if ( ! in_array(
				$k,
				array(
					'steps',
					'plugins',
				)
			)
			) {
				continue;
			}

			$this->$k = $v;
		}
		/**
		 * Create the dashboard page
		 */
		add_action( 'admin_menu', array( $this, 'onboarding_menu' ) );

		/**
		 * Load the welcome screen styles and scripts
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Onboarding menu
	 */
	public function onboarding_menu() {
		add_submenu_page(
			null,
			'epsilon-onboarding',
			__( 'Onboarding', 'epsilon-framework' ),
			'edit_theme_options',
			'epsilon-onboarding',
			array(
				$this,
				'render_onboarding',
			)
		);
	}

	/**
	 * Enqueue function
	 */
	public function enqueue() {
		wp_enqueue_style(
			'epsilon-onboarding',
			get_template_directory_uri() . '/inc/libraries/epsilon-theme-dashboard/assets/css/onboarding.css'

		);
		wp_enqueue_script(
			'epsilon-onboarding',
			get_template_directory_uri() . '/inc/libraries/epsilon-theme-dashboard/assets/js/epsilon-onboarding.js',
			array(),
			false,
			true
		);

		/**
		 * Use the localize script to send data from the backend to our app
		 */
		wp_localize_script( 'epsilon-onboarding', 'EpsilonOnboarding', $this->epsilon_onboarding_setup() );
	}

	/**
	 * Filter the on boarding setup so we can access it in theme
	 *
	 * @return mixed
	 */
	public function epsilon_onboarding_setup() {
		return apply_filters(
			'epsilon-onboarding-setup',
			array(
				'ajax_nonce' => wp_create_nonce( 'epsilon_dashboard_nonce' ),
				'steps'      => $this->steps,
			)
		);
	}

	/**
	 * Render onboarding
	 */
	public function render_onboarding() {
		echo '<div id="epsilon-onboarding-app"></div>';
	}
}
