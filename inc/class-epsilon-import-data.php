<?php
/**
 * Epsilon Import Data Class
 *
 * @package MedZone
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
	 * Available demos
	 *
	 * @var array
	 */
	public $demos = array();
	/**
	 * Path to json
	 *
	 * @var mixed|string
	 */
	public $path = '';

	/**
	 * Epsilon_Import_Data constructor.
	 */
	public function __construct( $args = array() ) {
		$this->path = $args['path'];
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
	 * Get the JSON, Parse IT and figure out content
	 *
	 * @return bool|array|mixed
	 */
	public function handle_json() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();
		}

		$json = $wp_filesystem->get_contents( $this->path );
		$json = json_decode( $json, true );

		/**
		 * In case the json could not be decoded, we return a new stdClass
		 */
		if ( null === $json ) {
			return $this->path;
		}

		return $this->_parse_json_js( $json );
	}

	/**
	 * Parses a json for frontend rendering
	 *
	 * @param $json
	 *
	 * @returns array
	 */
	private function _parse_json_js( $json ) {
		$arr = array();
		foreach ( $json as $k => $v ) {
			$arr[ $k ]['id']      = $k;
			$arr[ $k ]['label']   = $v['label'];
			$arr[ $k ]['thumb']   = get_template_directory_uri() . $v['thumb'];
			$arr[ $k ]['content'] = array();

			foreach ( $v as $key => $value ) {
				if ( 'thumb' === $key ) {
					continue;
				}
				if ( 'label' === $key ) {
					continue;
				}
				$arr[ $k ]['content'][] = array(
					'label' => $value['label'],
					'id'    => $key,
				);
			}
		}

		return $arr;
	}

	/**
	 * Parses a json
	 *
	 * @param $json
	 *
	 * @returns array
	 */
	private function _parse_json( $json ) {
		$arr = array();
		foreach ( $json as $k => $v ) {
			$arr[ $k ]          = $v;
			$arr[ $k ]['id']    = $k;
			$arr[ $k ]['thumb'] = get_template_directory_uri() . $v['thumb'];

			foreach ( $v as $index => $data ) {
				if ( ! is_array( $data ) ) {
					continue;
				}

				if ( ! isset( $data['content'] ) ) {
					continue;
				}

				if ( ! is_array( $data['content'] ) ) {
					continue;
				}

				foreach ( $data['content'] as $key => $value ) {
					if ( ! is_array( $key ) ) {
						if ( false !== strpos( $key, '_image' ) || ( false !== strpos( $key, '_background' ) && false === strpos( $key, '_color' ) ) ) {
							$arr[ $k ][ $index ]['content'][ $key ] = get_template_directory_uri() . $value;
						}
						continue;
					}
					foreach ( $value as $fid => $fields ) {
						if ( false !== strpos( $fid, '_image' ) || false !== strpos( $fid, '_background' ) ) {
							$arr[ $k ][ $index ]['content'][ $key ][ $fid ] = get_template_directory_uri() . $fields;
						}
					}
				}
			}
		}

		return $arr;
	}

	/**
	 * Import all content
	 */
	public static function import_all_content() {

	}
}
