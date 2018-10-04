<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Options_Importer
 */
class Epsilon_Options_Importer extends Epsilon_Importer {
	/**
	 * Epsilon_Option_Importer constructor.
	 *
	 * @param $args
	 */
	public function __construct( $args ) {
		parent::__construct( $args );
	}

	/**
	 * @return string
	 */
	public function import() {
		$import = array();
		foreach ( $this->content['content'] as $k => $v ) {
			if ( 'frontpage' === $k ) {
				$this->_check_static_page();
				continue;
			}

			if ( 'logo' === $k ) {
				$this->_upload_logo( $this->content['content'][ $k ]['content'] );
				continue;
			}

			if ( 'blogpage' === $k ) {
				$this->_check_blog_page();
				continue;
			}

			$import[ $this->content['content'][ $k ]['setting'] ] = $this->content['content'][ $k ]['content'];
		}

		foreach ( $import as $k => $v ) {
			set_theme_mod( $k, $v );
		}

		return 'ok';
	}

	/**
	 * Check if we have a static page
	 */
	private function _check_static_page() {
		$front = get_option( 'show_on_front' );
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
		}

		return 'ok';
	}

	/**
	 * Check if we have a blog page, if not add it
	 */
	private function _check_blog_page() {
		$front = get_option( 'show_on_front' );
		if ( 'posts' === $front ) {
			return 'ok';
		}

		$id = wp_insert_post(
			array(
				'post_title'  => __( 'Blog', 'epsilon-framework' ),
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $id, 'epsilon_post_backed_up', true );
		update_option( 'page_for_posts', $id );

		return 'ok';
	}

	/**
	 * Upload custom logo image
	 *
	 * @param $image
	 *
	 * @return int|object|mixed
	 */
	private function _upload_logo( $image ) {
		$logo = get_theme_mod( 'custom_logo', false );
		/**
		 * If there is a logo, don`t overwrite it
		 */
		if ( false !== $logo ) {
			return false;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/post.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$tmp  = download_url( $image );
		$file = array(
			'name'     => basename( 'website-logo' . rand( 1, 123123123 ) ) . '.png',
			'tmp_name' => $tmp,
		);
		if ( is_wp_error( $tmp ) ) {
			unlink( $file['tmp_name'] );

			return $tmp;
		}
		$id = media_handle_sideload( $file, 0, 'Custom Logo' );
		if ( is_wp_error( $id ) ) {
			unlink( $file['tmp_name'] );

			return $id;
		}
		set_theme_mod( 'custom_logo', $id );
	}
}
