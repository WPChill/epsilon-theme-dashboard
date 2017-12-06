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
 * Entry point
 *
 * Class Epsilon_Dashboard
 */
class Epsilon_Dashboard {
	/**
	 * Recommended actions
	 *
	 * @var array
	 */
	public $actions = array();
	/**
	 * Recommended plugins
	 *
	 * @var array
	 */
	public $plugins = array();
	/**
	 * Demos that can be imported
	 *
	 * @var array
	 */
	public $demos = array();
	/**
	 * Does the theme support "onboarding" ?
	 *
	 * @var bool
	 */
	protected $onboarding = true;
	/**
	 * Theme
	 *
	 * @var array
	 */
	protected $theme = array();

	/**
	 * Class constructor
	 *
	 * Epsilon_Dashboard constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $k => $v ) {

			if ( ! in_array(
				$k,
				array(
					'actions',
					'plugins',
					'demos',
					'theme',
					'onboarding'
				)
			)
			) {
				continue;
			}

			$this->$k = $v;
		}

		$theme       = wp_get_theme();
		$arr         = array(
			'theme-name'    => $theme->get( 'Name' ),
			'theme-slug'    => $theme->get( 'TextDomain' ),
			'theme-version' => $theme->get( 'Version' ),
		);
		$this->theme = wp_parse_args( $this->theme, $arr );


		$this->init_dashboard();

		if ( $this->onboarding ) {
			$this->init_onboarding();
		}

		/**
		 * Ajax callbacks
		 */
		add_action( 'wp_ajax_epsilon_dashboard_ajax_callback', array(
			$this,
			'epsilon_dashboard_ajax_callback',
		) );
		add_action( 'wp_ajax_nopriv_epsilon_dashboard_ajax_callback', array(
			$this,
			'epsilon_dashboard_ajax_callback',
		) );
	}

	/**
	 * Instance creator
	 *
	 * @param array $args
	 *
	 * @return Epsilon_Dashboard
	 */
	public static function get_instance( $args = array() ) {
		static $inst;
		if ( ! $inst ) {
			$inst = new Epsilon_Dashboard( $args );
		}

		return $inst;
	}

	/**
	 * Init the dashboard
	 */
	public function init_dashboard() {
		new Epsilon_Dashboard_Output(
			array(
				'theme'   => $this->theme,
				'actions' => $this->actions,
			)
		);
	}

	/**
	 * Start onboarding process
	 */
	public function init_onboarding() {
		if ( ! empty( $_GET ) && isset( $_GET['page'] ) && 'epsilon-onboarding' === $_GET['page'] ) {
			new Epsilon_Onboarding(
				array(
					'plugins' => $this->plugins,
					'actions' => $this->actions,
					'steps'   => array(
						array(
							'id'       => 'landing',
							'title'    => __( 'Getting Started', 'epsilon-framework' ),
							'contents' => __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla blandit ac sem et ultricies. In hac habitasse platea dictumst. Suspendisse potenti.', 'epsilon-framework' ),
							'progress' => __( 'Getting Started', 'epsilon-framework' ),
							'buttons'  => array(
								'next' => array(
									'action' => 'next',
									'label'  => __( 'Let\'s go <span class="dashicons dashicons-arrow-right-alt2"></span>', 'epsilon-framework' ),
								),
							),
						),
						array(
							'id'       => 'plugins',
							'title'    => __( 'Recommended Plugins', 'epsilon-framework' ),
							'contents' => __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.Nulla blandit ac sem et ultricies. In hac habitasse platea dictumst. Suspendisse potenti.', 'epsilon-framework' ),
							'progress' => __( 'Plugins', 'epsilon-framework' ),
							'buttons'  => array(
								'prev' => array(
									'action' => 'back',
									'label'  => __( '<span class="dashicons dashicons-arrow-left-alt2"></span> Back', 'epsilon-framework' ),
								),
								'next' => array(
									'action' => 'next',
									'label'  => __( 'Next <span class="dashicons dashicons-arrow-right-alt2"></span>', 'epsilon-framework' ),
								),
							),
						),
						array(
							'id'       => 'demo',
							'title'    => __( 'Import Demo Content', 'epsilon-framework' ),
							'contents' => __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.Nulla blandit ac sem et ultricies. In hac habitasse platea dictumst. Suspendisse potenti.', 'epsilon-framework' ),
							'progress' => __( 'Demos', 'epsilon-framework' ),
							'buttons'  => array(
								'prev' => array(
									'action' => 'back',
									'label'  => __( '<span class="dashicons dashicons-arrow-left-alt2"></span> Back', 'epsilon-framework' ),
								),
								'next' => array(
									'action' => 'next',
									'label'  => __( 'Next <span class="dashicons dashicons-arrow-right-alt2"></span>', 'epsilon-framework' ),
								),
							),
						),
						array(
							'id'       => 'enjoy',
							'title'    => __( 'Enjoy your theme!', 'epsilon-framework' ),
							'contents' => __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla blandit ac sem et ultricies.In hac habitasse platea dictumst. Suspendisse potenti.', 'epsilon-framework' ),
							'progress' => __( 'Step Three', 'epsilon-framework' ),
							'buttons'  => array(
								'prev' => array(
									'action' => 'back',
									'label'  => __( '<span class="dashicons dashicons-arrow-left-alt2"></span> Back', 'epsilon-framework' ),
								),
							),
						),
					),
				)
			);
		}
	}

	/**
	 * AJAX Handler
	 */
	public function epsilon_dashboard_ajax_callback() {
		if ( is_string( $_POST['args'] ) ) {
			$_POST['args'] = json_decode( wp_unslash( $_POST['args'] ), true );
		}

		if ( isset( $_POST['args'], $_POST['args']['nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['args']['nonce'] ), 'epsilon_dashboard_nonce' ) ) {
			wp_die(
				wp_json_encode(
					array(
						'status' => false,
						'error'  => esc_html__( 'Not allowed', 'epsilon-framework' ),
					)
				)
			);
		}

		$args_action = array_map( 'sanitize_text_field', wp_unslash( $_POST['args']['action'] ) );

		if ( count( $args_action ) !== 2 ) {
			wp_die(
				wp_json_encode(
					array(
						'status' => false,
						'error'  => esc_html__( 'Not allowed', 'epsilon-framework' ),
					)
				)
			);
		}

		if ( ! class_exists( $args_action[0] ) ) {
			wp_die(
				wp_json_encode(
					array(
						'status' => false,
						'error'  => esc_html__( 'Class does not exist', 'epsilon-framework' ),
					)
				)
			);
		}

		$class  = $args_action[0];
		$method = $args_action[1];
		$args   = array();

		if ( is_array( $_POST['args']['args'] ) ) {
			$args = Epsilon_Sanitizers::array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['args']['args'] ) );
		}

		$response = $class::$method( $args );

		if ( is_array( $response ) ) {
			wp_die( wp_json_encode( $response ) );
		}

		if ( 'ok' === $response ) {
			wp_die(
				wp_json_encode(
					array(
						'status'  => true,
						'message' => 'ok',
					)
				)
			);
		}

		wp_die(
			wp_json_encode(
				array(
					'status'  => false,
					'message' => 'nok',
				)
			)
		);
	}
}
