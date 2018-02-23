<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Post_Generator
 */
class Epsilon_Post_Generator {
	/**
	 * Posts array
	 *
	 * @var int
	 */
	public $posts = array();

	/**
	 * Epsilon_Post_Generator constructor.
	 *
	 * @param array $posts
	 */
	public function __construct(
		$posts = array(
			'post_count'     => 4,
			'image_size'     => array(),
			'image_category' => array( 'dogs' )
		)
	) {
		$this->posts = $posts;
	}

	/**
	 * Start adding posts to WP
	 *
	 */
	public function add_posts() {
		$posts = array();
		for ( $i = 0; $i < $this->posts['post_count']; $i ++ ) {
			$post = $this->generate_post();
			if ( ! is_wp_error( $post ) ) {
				$posts[] = $post;
			}

			$this->generate_image(
				$post,
				$this->posts['image_size'],
				'',
				array(
					'category' => $this->posts['image_category'],
				)
			);
		}

		return true;
	}

	/**
	 * Run this function to generate a new post
	 *
	 */
	private function generate_post() {
		$generator = new Epsilon_Text_Generator();

		$post = array(
			'post_title'   => ucfirst( $generator->words( 5 ) ),
			'post_content' => $generator->paragraphs( 1, 'p' ) . "\n" . '<!--more-->' . "\n" . $generator->paragraphs( 3, 'p' ),
			'post_status'  => 'publish',
		);

		return wp_insert_post( $post );
	}

	/**
	 * Run this function to generate an image and assign it to a post
	 *
	 * @param        $post_id
	 * @param array  $sizes
	 * @param string $description
	 * @param array  $options
	 */
	private function generate_image( $post_id, $sizes = array(), $description = '', $options = array() ) {
		$image = new Epsilon_Image_Generator( $post_id, $sizes, $description, $options );
		$image->generate_featured_image();
	}
}
