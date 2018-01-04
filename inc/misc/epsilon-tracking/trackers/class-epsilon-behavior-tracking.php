<?php
/**
 * Epsilon Behavior Tracking
 *
 * @package Epsilon Framework
 * @since   1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Behavior_Tracking
 */
class Epsilon_Behavior_Tracking extends Epsilon_Tracking {
	/**
	 * Generate the data array
	 *
	 * @return array
	 */
	public function generate_data() {
		/**
		 * @todo this should be taken from "options"
		 */
		return array(
			'imported_demo'   => true,
			'used_onboarding' => true,
			'privacy'         => array(
				'lite_vs_pro'         => false,
				'recommended_plugins' => true,
				'recommended_actions' => true,
				'theme_upsells'       => true,
			),
		);
	}
}