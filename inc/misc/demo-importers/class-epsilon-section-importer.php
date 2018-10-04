<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Section_Importer
 */
class Epsilon_Section_Importer extends Epsilon_Importer {
	/**
	 * @var bool
	 */
	public $multiple = false;
	/**
	 * @var array
	 */
	public $content = array();

	/**
	 * Epsilon_Section_Importer constructor.
	 *
	 * @param $args
	 */
	public function __construct( $args ) {
		parent::__construct( $args );
		$this->multiple = isset( $args['multiple'] );
	}

	/**
	 * Imports sections
	 *
	 * @return string
	 */
	public function import() {
		if ( empty( $this->content ) ) {
			return 'nok';
		}

		return $this->multiple ? $this->_import_multiple_pages() : $this->_import_single_page();
	}

	/**
	 * Imports multiple pages
	 */
	private function _import_multiple_pages() {
		$import = $this->collect_data();
		foreach ( $import as $id => $should_import ) {
			update_post_meta(
				$id,
				$should_import['setting'],
				array(
					$should_import['setting'] => $should_import['content']
				)
			);
		}

		return 'ok';
	}

	/**
	 * Imports a single page
	 */
	private function _import_single_page() {
		$import  = array();
		$setting = '';
		foreach ( $this->content['content'] as $s_id => $values ) {
			$import[] = $this->search_for_images_in_section( $values['content'] );
			$setting  = $values['setting'];
		}

		$fp = $this->check_static_page();

		update_post_meta(
			null === $fp ? Epsilon_Content_Backup::get_instance()->setting_page : $fp,
			$setting . '_' . $fp,
			array(
				$setting . '_' . $fp => $import,
			)
		);

		return 'ok';
	}

	/**
	 * Collects data for import
	 *
	 * @return array
	 */
	public function collect_data() {
		$import = array();
		foreach ( $this->content['content'] as $pid => $page ) {
			$id = wp_insert_post(
				array(
					'post_title'  => $page['page_title'],
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);

			if ( is_wp_error( $id ) ) {
				continue;
			}


			if ( isset( $page['frontpage'] ) && $page['frontpage'] ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $id );

			}
			$content = array();
			foreach ( $page['content'] as $s_id => $values ) {
				$content[] = $this->search_for_images_in_section( $values['content'] );
			}

			$import[ $id ] = array(
				'id'      => $id,
				'setting' => $this->content['setting'] . '_' . $id,
				'content' => $content
			);
		}
		return $import;
	}

	/**
	 * @param $content
	 *
	 * @return mixed
	 */
	public function search_for_images_in_section( $content ) {
		foreach ( $content as $name => $value ) {
			if ( is_array( $value ) ) {
				continue;
			}

			if ( strpos( $value, 's3.amazonaws.com' ) !== false ) {
				$generator = Epsilon_Static_Image_Generator::get_instance();
				$generator->add_url( $value );
				$content[ $name ] = $generator->get_image();
			}
		}

		return $content;
	}

	/**
	 * Check if we have a static page
	 */
	public function check_static_page() {
		$front = get_option( 'show_on_front' );
		$id    = null;

		if ( 'posts' === $front ) {
			update_option( 'show_on_front', 'page' );
			$id = wp_insert_post(
				array(
					'post_title'  => __( 'Homepage', 'epsilon-framework' ),
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);
			update_post_meta( $id, 'epsilon_post_backed_up', true );
			update_option( 'page_on_front', $id );
		} else {
			$id = get_option( 'page_on_front' );
		}

		return $id;
	}
}
