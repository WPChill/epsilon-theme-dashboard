<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Posts_Importer
 */
class Epsilon_Posts_Importer extends Epsilon_Importer {
	/**
	 * @return string
	 */
	public function import() {
		$class  = $this->_check_importer( $this->content['content'], 'class' );
		$method = $this->_check_importer( $this->content['content'], 'method' );
		$args = $this->_post_defaults( $this->content['content'] );

		$importer = new $class( $args );
		$importer->$method();

		return 'ok';
	}

	/**
	 * @param $args
	 * @param $key
	 *
	 * @return mixed
	 */
	private function _check_importer( $args, $key ) {
		$arr = array(
			'class'  => 'Epsilon_Post_Generator',
			'method' => 'add_posts',
		);

		if ( ! isset( $args['importer'] ) ) {
			return $arr[ $key ];
		}

		$arr['class']  = isset( $args['importer']['class'] ) ? $args['importer']['class'] : 'Epsilon_Post_Generator';
		$arr['method'] = isset( $args['importer']['method'] ) ? $args['importer']['method'] : 'add_posts';

		return $arr[ $key ];
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	private function _post_defaults( $args ) {
		$defaults = array(
			'post_count'      => 4,
			'image_size'      => array(),
			'image_category'  => array(),
			'specific_images' => array(),
		);

		return wp_parse_args( $args, $defaults );
	}
}
