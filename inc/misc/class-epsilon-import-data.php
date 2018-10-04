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
				'posts'      => true,
				'options'    => true,
				'widgets'    => true,
				'content'    => true,
				'sections'   => true,
				'custom_css' => true,
				'menus'      => true,
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

		$importer = new Epsilon_Options_Importer(
			array(
				'props' => $this->content_json[ $type ]
			)
		);

		$result           = $importer->import();
		$this->front_page = get_option( 'page_on_front' );

		return $result;
	}

	/**
	 * @param string $type
	 *
	 * @return Epsilon_Posts_Importer|string
	 */
	public function import_posts( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		$importer = new Epsilon_Posts_Importer(
			array(
				'props' => $this->content_json[ $type ]
			)
		);

		return $importer->import();
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

		$importer = new Epsilon_Section_Importer(
			array(
				'multiple' => true,
				'props'    => $this->content_json[ $type ]
			)
		);

		return $importer->import();
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

		$importer = new Epsilon_Content_Importer(
			array(
				'props' => $this->content_json[ $type ]
			)
		);

		return $importer->import();
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

		$importer = new Epsilon_Menu_Importer(
			array(
				'props' => $this->content_json[ $type ]
			)
		);

		return $importer->import();
	}

	/**
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

		$importer = new Epsilon_Custom_Css_Importer(
			array(
				'props' => $this->content_json[ $type ]
			)
		);

		return $importer->import();
	}


	/**
	 * Imports widgets
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function import_widgets( $type = '' ) {
		if ( empty( $this->content_json[ $type ] ) ) {
			return 'nok';
		}

		$importer = new Epsilon_Widgets_Importer(
			array(
				'props' => $this->content_json[ $type ]
			)
		);

		return $importer->import();
	}
}
