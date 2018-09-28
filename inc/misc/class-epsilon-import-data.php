<?php
/**
 * Epsilon Import Data Class
 *
 * @package Epsilon Framework
 * @since   1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Import_Data
 */
class Epsilon_Import_Data {
	/**
	 * JS Demos ( for template )
	 *
	 * @var array
	 */
	public $demos_js = array();
	/**
	 * Path to json
	 *
	 * @var mixed|string
	 */
	public $path = '';
	/**
	 * Save an index of the front page
	 *
	 * @var null
	 */
	public $front_page = null;
	/**
	 * @var array
	 */
	public $uploaded = array();

	/**
	 * @var null
	 */
	public $content_json = 'empty';

	/**
	 * Epsilon_Import_Data constructor.
	 */
	public function __construct( $args = array() ) {
		if ( ! empty( $args['path'] ) ) {
			$this->path = $args['path'];
		}
	}

	/**
	 * @param array $args
	 *
	 * @return Epsilon_Import_Data
	 */
	public static function get_instance( $args = array() ) {
		static $inst;
		if ( ! $inst ) {
			$inst = new Epsilon_Import_Data( $args );
		}

		return $inst;
	}

	/**
	 * Sets demos to this instance
	 */
	public function set_demos() {
		$this->handle_json();
	}

	/**
	 * Get the JSON, Parse IT and figure out content
	 *
	 * @return void
	 */
	public function handle_json() {
		$json = wp_remote_get( $this->path );
		if ( is_wp_error( $json ) ) {
			return;
		}

		$json = json_decode( $json['body'], true );

		if ( null === $json ) {
			return;
		}

		$this->_parse_json_js( $json );
	}

	/**
	 * Parses a json for frontend rendering
	 *
	 * @param $json
	 *
	 */
	private function _parse_json_js( $json ) {
		foreach ( $json['collection'] as $k => &$v ) {
			foreach ( $v['content'] as $key => &$value ) {
				if ( 'plugins' === $key ) {
					foreach ( $value['additional'] as $index => &$plugin ) {
						$plugin['installed'] = Epsilon_Notify_System::check_plugin_is_installed( $plugin['slug'] );
						$plugin['active']    = Epsilon_Notify_System::check_plugin_is_active( $plugin['slug'] );
					}
				}
			}
		}

		$this->demos_js = $json;
	}

	/**
	 * Import all content
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function import_all_content( $args = array() ) {
		$content = array(
			'id'      => 'standard',
			'content' => array(
				'menus'      => true,
				'posts'      => true,
				'options'    => true,
				'widgets'    => true,
				'content'    => true,
				'sections'   => true,
				'custom_css' => true,
			)
		);

		$status = self::import_selective_data( $content );

		$theme = wp_get_theme();
		set_theme_mod( $theme->get( 'TextDomain' ) . '_content_imported', true );

		return $status;
	}

	/**
	 * Import selective data
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function import_selective_data( $args = array() ) {
		$instance = self::get_instance();
		$status   = 'nok';
		if ( empty( $args['path'] ) ) {
			return $status;
		}

		if ( $instance->content_json === 'empty' ) {
			$json = wp_remote_get( $args['path'] );
			if ( is_wp_error( $json ) ) {
				return $status;
			}
			$json                   = json_decode( $json['body'], true );
			$instance->content_json = $json;
		}

		if ( $instance->content_json === null ) {
			return $status;
		}

		foreach ( $args['content'] as $type => $value ) {
			if ( method_exists( $instance, 'import_' . $type ) ) {
				$method = 'import_' . $type;
				$status = $instance->$method( $type );
			}
		}

		return $status;
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public function import_options( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		$import = array();
		foreach ( $this->content_json[ $type ]['content'] as $k => $v ) {
			if ( 'frontpage' === $k ) {
				$this->check_static_page();
				continue;
			}

			if ( 'logo' === $k ) {
				$this->upload_logo( $this->content_json[ $type ]['content'][ $k ]['content'] );
				continue;
			}

			if ( 'blogpage' === $k ) {
				$this->check_blog_page();
				continue;
			}

			$import[ $this->content_json[ $type ]['content'][ $k ]['setting'] ] = $this->content_json[ $type ]['content'][ $k ]['content'];
		}

		foreach ( $import as $k => $v ) {
			set_theme_mod( $k, $v );
		}

		return 'ok';
	}

	/**
	 * Import sections
	 */
	public function import_posts( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		$class  = $this->check_importer( $this->content_json[ $type ]['content'], 'class' );
		$method = $this->check_importer( $this->content_json[ $type ]['content'], 'method' );

		$args = $this->post_defaults( $this->content_json[ $type ]['content'] );

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
	public function check_importer( $args, $key ) {
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
	public function post_defaults( $args ) {
		$defaults = array(
			'post_count'      => 4,
			'image_size'      => array(),
			'image_category'  => array(),
			'specific_images' => array(),
		);

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Imports sections
	 *
	 * @param string $type
	 *
	 * @return string;
	 */
	public function import_sections( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		$import  = array();
		$setting = '';
		foreach ( $this->content_json[ $type ]['content'] as $s_id => $values ) {
			$import[] = $this->search_for_images_in_section( $values['content'] );
			$setting  = $values['setting'];
		}

		$fp = $this->check_static_page();

		if ( 'ok' !== $fp ) {
			return 'nok';
		}

		update_post_meta(
			null === $this->front_page ? Epsilon_Content_Backup::get_instance()->setting_page : $this->front_page,
			$setting . '_' . $this->front_page, array(
				$setting . '_' . $this->front_page => $import,
			)
		);

		return 'ok';
	}

	/**
	 * Import content
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function import_content( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		$import = array();
		foreach ( $this->content_json[ $type ]['content'] as $c_id ) {
			$import[ $c_id['setting'] ] = $this->search_for_images( $c_id['content'] );
		}

		/**
		 * Determine if we're saving theme options in post meta or in theme mods
		 */
		foreach ( $import as $k => $v ) {
			update_post_meta(
				Epsilon_Content_Backup::get_instance()->setting_page,
				$k, array(
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

	/**
	 * Imports Menus
	 *
	 * @param string $type
	 *
	 * @return string;
	 */
	public function import_menus( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		$ref = $this->content_json[ $type ]['content'];
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
				$menus                = get_theme_mod( 'nav_menu_locations', array() );
				$menus[ $menu['id'] ] = $menu_id;
				set_theme_mod( 'nav_menu_locations', $menus );
			}
		}

		return 'ok';
	}

	/**
	 * @param $id
	 * @param $type
	 *
	 * @return string
	 */
	public function import_custom_css( $type ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		if ( empty( $this->content_json[ $type ]['content'] ) ) {
			return 'ok';
		}

		$theme = wp_get_theme();
		$name  = $theme->get( 'TextDomain' );

		$post_id = get_theme_mod( 'custom_css_post_id', 0 );

		$post = wp_insert_post(
			array(
				'ID'           => $post_id,
				'post_content' => $post_id ? get_post( $post_id )->post_content . "\n" . $this->content_json[ $type ]['content'] : $this->content_json[ $type ]['content'],
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
	 * @param string $id
	 * @param string $type
	 *
	 * @return string
	 */
	public function import_widgets( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		global $wp_registered_sidebars;
		foreach ( $this->content_json[ $type ]['content'] as $sidebar => $widgets ) {
			foreach ( $widgets as $widget => $props ) {
				$widget_type = preg_replace( '/-[0-9]+$/', '', $widget );
				$widget_id   = str_replace( $widget_type . '-', '', $widget );

				$prop = array(
					'available'            => false,
					'sidebar_id'           => 'wp_inactive_widgets',
					'sidebar_message_type' => 'error',
				);

				if ( isset( $wp_registered_sidebars[ $sidebar ] ) ) {
					$prop['available']            = true;
					$prop['sidebar_id']           = $sidebar;
					$prop['sidebar_message_type'] = 'success';
				}

				$temp = array(
					'_multiwidget' => 1,
				);

				$widget_instance   = get_option( 'widget_' . $widget_type );
				$widget_instance   = ! empty( $widget_instance ) ? $widget_instance : $temp;
				$widget_instance[] = $this->content_json[ $type ]['content'][ $sidebar ][ $widget ];

				// Get the key it was given.
				end( $widget_instance );
				$new_id = key( $widget_instance );
				if ( '0' === strval( $new_id ) ) {
					$new_id                     = 1;
					$widget_instance[ $new_id ] = $widget_instance[0];
					unset( $widget_instance[0] );
				}
				if ( isset( $widget_instance['_multiwidget'] ) ) {
					$multiwidget = $widget_instance['_multiwidget'];
					unset( $widget_instance['_multiwidget'] );
					$widget_instance['_multiwidget'] = $multiwidget;
				}

				// Update option with new widget.
				update_option( 'widget_' . $widget_type, $widget_instance );
				$sidebars_widgets = get_option( 'sidebars_widgets' );
				if ( ! $sidebars_widgets ) {
					$sidebars_widgets = array();
				}
				$new_instance_id = $widget_type . '-' . $new_id;

				// Add new instance to sidebar.
				$sidebars_widgets[ $prop['sidebar_id'] ][] = $new_instance_id;

				// Save the amended data.
				update_option( 'sidebars_widgets', $sidebars_widgets );
			}

		}// End foreach().
		return 'ok';

	}

	/**
	 * Check if we have a static page
	 */
	public function check_static_page() {
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
		$this->front_page = get_option( 'page_on_front' );

		return 'ok';
	}

	/**
	 * Check if we have a blog page, if not add it
	 */
	public function check_blog_page() {
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
	 * @return int|object|void
	 */
	public function upload_logo( $image ) {
		$logo = get_theme_mod( 'custom_logo', false );
		/**
		 * If there is a logo, don`t overwrite it
		 */
		if ( false !== $logo ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/post.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$tmp  = download_url( get_template_directory_uri() . $image );
		$file = array(
			'name'     => basename( 'machothemes-logo' . rand( 1, 123123123 ) ) . '.png',
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
