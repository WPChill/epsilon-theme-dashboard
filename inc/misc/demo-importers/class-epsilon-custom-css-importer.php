<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Custom_Css_Importer
 */
class Epsilon_Custom_Css_Importer extends Epsilon_Importer {
	/**
	 * @return string
	 */
	public function import() {
		$theme = wp_get_theme();
		$name  = $theme->get( 'TextDomain' );

		$post_id = get_theme_mod( 'custom_css_post_id', 0 );

		$post = wp_insert_post(
			array(
				'ID'           => $post_id,
				'post_content' => $post_id ? get_post( $post_id )->post_content . "\n" . $this->content['content'] : $this->content['content'],
				'post_title'   => $name,
				'post_name'    => $name,
				'post_status'  => 'publish',
				'post_type'    => 'custom_css',
			)
		);


		if ( is_int( $post ) ) {
			set_theme_mod( 'custom_css_post_id', $post );

			return 'ok';
		}

		return 'nok';
	}
}
