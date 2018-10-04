<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Menu_Importer
 */
class Epsilon_Menu_Importer extends Epsilon_Importer {
	/**
	 * @return string
	 */
	public function import() {
		$ref = $this->content['content'];
		foreach ( $ref as $menu ) {
			$menu_exists = wp_get_nav_menu_object( $menu['label'] );
			if ( ! $menu_exists ) {
				$menu_id = wp_create_nav_menu( $menu['label'] );
				if ( 'primary' === $menu['id'] ) {
					wp_update_nav_menu_item( $menu_id, 0, array(
						'menu-item-title'   => esc_html__( 'Home', 'epsilon-framework' ),
						'menu-item-classes' => 'home',
						'menu-item-url'     => home_url( '/' ),
						'menu-item-status'  => 'publish',
					) );

					$page_for_posts = get_option( 'page_for_posts', false );
					if ( $page_for_posts ) {
						wp_update_nav_menu_item( $menu_id, 0, array(
							'menu-item-title'   => esc_html__( 'Blog', 'epsilon-framework' ),
							'menu-item-classes' => 'blog',
							'menu-item-url'     => home_url( '/?page_id=' . get_option( 'page_for_posts' ) ),
							'menu-item-status'  => 'publish',
						) );
					}
				}
				$arr = $menu['menu'];
				foreach ( $arr as $item ) {
					$this->_add_menu_items( $menu_id, $item );
				}
				$this->_check_imported_pages( $menu_id );
				$menus                = get_theme_mod( 'nav_menu_locations', array() );
				$menus[ $menu['id'] ] = $menu_id;


				set_theme_mod( 'nav_menu_locations', $menus );

			}
		}

		return 'ok';
	}

	/**
	 * Adds menu item
	 *
	 * @param $id
	 * @param $item
	 * @param $parent
	 */
	private function _add_menu_items( $id, $item, $parent = false ) {
		$item_id = wp_update_nav_menu_item( $id, 0, array(
			'menu-item-title'     => $item['label'],
			'menu-item-classes'   => $item['label'],
			'menu-item-url'       => $item['href'],
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent ? $parent : 0,
		) );
		if ( isset( $item['submenus'] ) ) {
			foreach ( $item['submenus'] as $child ) {
				$this->_add_menu_items( $id, $child, $item_id );
			}
		}
	}

	/**
	 * Checks the imported pages and adds them to the menu
	 */
	private function _check_imported_pages( $menu_id ) {
		$epsilon = $this->get_all_epsilon_pages();

		foreach ( $epsilon as $id ) {
			$post = get_post( $id );
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'  => $post->post_title,
				'menu-item-url'    => get_permalink( $post ),
				'menu-item-status' => 'publish',
			) );
		}
	}

	/**
	 * Grab all epsilon pages
	 */
	public function get_all_epsilon_pages() {
		$ids = array();

		$pages = new WP_Query(
			array(
				'post_type'        => 'page',
				'nopaging'         => true,
				'suppress_filters' => true,
				'post__not_in'     => array(
					Epsilon_Content_Backup::get_instance()->setting_page,
					absint( get_option( 'page_on_front', 0 ) ),
				),
			)
		);

		if ( $pages->have_posts() ) {
			foreach ( $pages->posts as $page ) {
				if ( $this->was_build_with_epsilon( $page ) ) {
					$ids[] = $page->ID;
				}

			}
		}

		wp_reset_postdata();

		return $ids;
	}

	/**
	 * Is a page built with epsilon
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function was_build_with_epsilon( $post ) {
		$slug                   = get_stylesheet();
		$was_built_with_epsilon = get_post_meta( $post->ID, $slug . '_frontpage_sections_' . $post->ID, true );

		if ( is_array( $was_built_with_epsilon ) && array_key_exists( $slug . '_frontpage_sections_' . $post->ID, $was_built_with_epsilon ) ) {
			return true;
		}

		return false;
	}
}
