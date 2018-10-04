<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Importer
 */
abstract class Epsilon_Importer {
	/**
	 * @var array
	 */
	public $content = array();

	/**
	 * Epsilon_Importer constructor.
	 */
	public function __construct( $args ) {
		$this->content = ! empty( $args['props'] ) ? $args['props'] : array();
	}

	/**
	 * Import function
	 *
	 * @return string
	 */
	public function import() {
		return 'nok';
	}
}
