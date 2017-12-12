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
		 * @Todo, this should be filtered. comes from the theme and use wp_parse_args to populate it
		 */
		wp_localize_script( 'epsilon-dashboard', 'EpsilonDashboard', array(
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
			),
			'actions'      => $this->actions,
			'tabs'         => array(
				array(
					'id'      => 'epsilon-demo',
					'title'   => 'Demo Content',
					'type'    => 'demos',
					'content' => array(
						'title'     => esc_html__( 'Install your demo content', 'epsilon-framework' ),
						'paragraph' => esc_html__( 'Since Portum Pro is a versatile theme we provided a few sample demo styles for you, please choose one from the following pages so you will have to work as little as you should. Click on the style and press next!', 'epsilon-framework' ),
						'demos'     => get_template_directory() . '/inc/libraries/epsilon-theme-dashboard/assets/data/demo.json',
					),
				),
				array(
					'id'      => 'epsilon-getting-started',
					'title'   => 'Getting Started',
					'type'    => 'info',
					'content' => array(
						array(
							'title'     => esc_html__( 'Step 1 - Implement recommended actions', 'epsilon-framework' ),
							'paragraph' => esc_html__( 'We compiled a list of steps for you, to take make sure the experience you will have using one of our products is very easy to follow.', 'epsilon-framework' ),
							'action'    => $this->check_actions(),
						),
						array(
							'title'     => esc_html__( 'Step 2 - Check our documentation', 'epsilon-framework' ),
							'paragraph' => esc_html__( 'Even if you are a long-time WordPress user, we still believe you should give our documentation a very quick Read.', 'epsilon-framework' ),
							'action'    => '<a target="_blank" href="http://docs.machothemes.com">' . __( 'Full documentation', 'epsilon-framework' ) . '</a>',
						),
						array(
							'title'     => esc_html__( 'Step 3 - Implement recommended actions', 'epsilon-framework' ),
							'paragraph' => esc_html__( 'Using the WordPress Customizer you can easily customize every aspect of the theme.', 'epsilon-framework' ),
							'action'    => '<a target="_blank" href="' . esc_url( admin_url() . 'customize.php' ) . '" class="button button-primary">' . esc_html__( 'Go to Customizer', 'epsilon-framework' ) . '</a>',
						),
						array(
							'title'     => esc_html__( 'Lend a hand and share your thoughts', 'epsilon-framework' ),
							'paragraph' => vsprintf(
							// Translators: 1 is Theme Name, 2 is opening Anchor, 3 is closing.
								__( 'We worked hard on making %1$s the best one out there. We are interested in hearing your thoughts about %1$s and what we could do to make it even better.<br/> <br/>', 'epsilon-framework' ),
								array(
									$this->theme['theme-name'],
								)
							),
							'action'    => '<a class="button button-feedback" target="_blank" href="https://bit.ly/' . $this->theme['theme-slug'] . '-feedback">Have your say</a><br/> <br/> <em>Note: A 10%% discount coupon will be emailed to you after form submission. Please use a valid email address.</em>',
							'type'      => 'standout',
						),
					),
				),
				array(
					'id'      => 'epsilon-actions',
					'title'   => 'Actions',
					'type'    => 'actions',
					'content' => $this->actions,
				),

			),
		) );
	}

	/**
	 * Render the app's container
	 */
	public function render_app_container() {
		echo '<div id="epsilon-dashboard-app"></div>';
	}

	/**
	 * Check actions
	 *
	 * @return string;
	 */
	public function check_actions() {
		$html = '<span class="dashicons dashicons-no-alt"></span> <a href="#actions">' . esc_html__( 'Check Recommended Actions', 'epsilon-framework' ) . '</a>';

		if ( 0 === count( $this->actions ) ) {
			$html = '<span class="dashicons dashicons-yes"></span> <a href="#actions">' . esc_html__( 'No recommended actions left to perform', 'epsilon-framework' ) . '</a>';
		}

		return $html;
	}
}
