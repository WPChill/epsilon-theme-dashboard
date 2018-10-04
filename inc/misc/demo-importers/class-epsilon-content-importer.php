<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Content_Importer
 */
class Epsilon_Content_Importer extends Epsilon_Importer {
	/**
	 * @var array
	 */
	public $content = array();

	/**
	 * @return string
	 */
	public function import() {
		$import = array();
		foreach ( $this->content['content'] as $c_id ) {
			$import[ $c_id['setting'] ] = $this->search_for_images( $c_id['content'] );
		}

		/**
		 * Determine if we're saving theme options in post meta or in theme mods
		 */
		foreach ( $import as $k => $v ) {
			update_post_meta(
				Epsilon_Content_Backup::get_instance()->setting_page,
				$k,
				array(
					$k => $v,
				)
			);
		}

		return 'ok';
	}

	/**
	 * @param $content
	 *
	 * @return mixed
	 */
	public function search_for_images( $content ) {
		foreach ( $content as $index => $block ) {
			foreach ( $block as $name => $value ) {
				if ( is_array( $value ) ) {
					continue;
				}

				if ( strpos( $value, 's3.amazonaws.com' ) !== false ) {
					$generator = Epsilon_Static_Image_Generator::get_instance();
					$generator->add_url( $value );
					$content[ $index ][ $name ] = $generator->get_image();
				}
			}
		}

		return $content;
	}
}
