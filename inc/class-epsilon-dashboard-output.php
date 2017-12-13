<?php
/**
 * Epsilon Backend Page
 *
 * @package Epsilon Framework
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Dashboard_Output
 */
class Epsilon_Dashboard_Output {
	/**
	 * @var array
	 */
	protected $theme = array();
	/**
	 * @var array
	 */
	protected $actions = array();
	/**
	 * @var array
	 */
	protected $plugins = array();
	/**
	 * @var array
	 */
	protected $tabs = array();

	/**
	 * Epsilon_Dashboard_Output constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $k => $v ) {

			if ( ! in_array(
				$k,
				array(
					'theme',
					'actions',
					'tabs',
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
		add_action( 'admin_menu', array( $this, 'dashboard_menu' ) );

		if ( ! empty( $_GET ) && isset( $_GET['page'] ) && $this->theme['theme-slug'] . '-dashboard' === $_GET['page'] ) {
			/**
			 * Admin enqueue script
			 */
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		}
	}

	public function dashboard_menu() {
		/* Translators: Menu Title */
		$title = sprintf( esc_html__( 'About %1$s', 'epsilon-framework' ), esc_html( $this->theme['theme-name'] ) );

		if ( 0 < count( $this->actions ) ) {
			$title .= '<span class="badge-action-count">' . absint( count( $this->actions ) ) . '</span>';
		}

		add_theme_page(
			$this->theme['theme-name'],
			$title,
			'edit_theme_options',
			$this->theme['theme-slug'] . '-dashboard',
			array(
				$this,
				'render_app_container',
			)
		);
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function enqueue() {
		wp_enqueue_style(
			'epsilon-dashboard',
			get_template_directory_uri() . '/inc/libraries/epsilon-theme-dashboard/assets/css/dashboard.css'

		);
		wp_enqueue_script(
			'epsilon-dashboard',
			get_template_directory_uri() . '/inc/libraries/epsilon-theme-dashboard/assets/js/epsilon-dashboard.js',
			array(),
			false,
			true
		);

		wp_enqueue_style( 'plugin-install' );
		wp_enqueue_script( 'plugin-install' );
		wp_enqueue_script( 'updates' );

		/**
		 * Use the localize script to send data from the backend to our app
		 */
		wp_localize_script( 'epsilon-dashboard', 'EpsilonDashboard', $this->epsilon_dashboard_setup() );
	}

	/**
	 * Filter the dashboard setup so we can access it in theme
	 *
	 * @return mixed
	 */
	public function epsilon_dashboard_setup() {
		return apply_filters(
			'epsilon-dashboard-setup',
			array(
				'ajax_nonce'   => wp_create_nonce( 'epsilon_dashboard_nonce' ),
				'theme'        => $this->theme,
				'logo'         => esc_url( get_template_directory_uri() . '/inc/libraries/epsilon-theme-dashboard/assets/images/macho-themes-logo.png' ),
				/* Translators: Dashboard Header Title. */
				'header'       => sprintf( esc_html__( 'Welcome to %1$s - v', 'epsilon-framework' ), esc_html( $this->theme['theme-name'] ) ) . esc_html( $this->theme['theme-version'] ),
				/* Translators: Dashboard Header Intro. */
				'intro'        => sprintf( esc_html__( '%1$s is now installed and ready to use! Get ready to build something beautiful. We hope you enjoy it! We want to make sure you have the best experience using %1$s and that is why we gathered here all the necessary information for you. We hope you will enjoy using %1$s, as much as we enjoy creating great products.', 'epsilon-framework' ), esc_html( $this->theme['theme-name'] ) ),
				'translations' => array(
					'select'          => esc_html__( 'Select', 'epsilon-framework' ),
					'import'          => esc_html__( 'Import', 'epsilon-framework' ),
					'cancel'          => esc_html__( 'Cancel', 'epsilon-framework' ),
					'selectImport'    => esc_html__( 'Select what you want to install', 'epsilon-framework' ),
					'waitImport'      => esc_html__( 'Please wait while we’re installing!', 'epsilon-framework' ),
					'contentImported' => esc_html__( 'Content imported!', 'epsilon-framework' ),
					'waiting'         => esc_html__( 'Waiting', 'epsilon-framework' ),
					'installing'      => esc_html__( 'Installing', 'epsilon-framework' ),
					'activating'      => esc_html__( 'Activating', 'epsilon-framework' ),
					'completePlugin'  => esc_html__( 'Installed and Activated', 'epsilon-framework' ),
					'pluginsFinished' => esc_html__( 'Plugins installed', 'epsilon-framework' ),
					'noActionsLeft'   => esc_html__( 'Hooray! There are no required actions for you right now.', 'epsilon-framework' ),
					'skipAction'      => esc_html__( 'Skip Action', 'epsilon-framework' ),
				),
				'plugins'      => $this->plugins,
				'actions'      => $this->actions,
				'tabs'         => $this->tabs,
			)
		);
	}

	/**
	 * Render the app's container
	 */
	public function render_app_container() {
		echo '<div id="epsilon-dashboard-app"></div>';
	}
}
