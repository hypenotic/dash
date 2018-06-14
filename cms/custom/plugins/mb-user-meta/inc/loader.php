<?php
/**
 * Loader for user meta
 *
 * @package    Meta Box
 * @subpackage MB User Meta
 * @author     Tran Ngoc Tuan Anh <rilwis@gmail.com>
 */

/**
 * Loader class
 */
class MB_User_Meta_Loader {
	/**
	 * Meta boxes for users only.
	 *
	 * @var array
	 */
	public static $meta_boxes = array();

	/**
	 * Run hooks to get meta boxes for users and initialize them.
	 */
	public function init() {
		add_filter( 'rwmb_meta_boxes', array( $this, 'filter' ), 999 );

		/**
		 * Initialize meta boxes for user.
		 * 'rwmb_meta_boxes' runs at priority 10, we use priority 20 to make sure self::$meta_boxes is set.
		 */
		add_action( 'init', array( $this, 'register' ), 20 );

		add_filter( 'rwmb_meta_type', array( $this, 'filter_meta_type' ), 10, 2 );
	}

	/**
	 * Filter meta boxes to get only meta boxes for users and remove them from posts.
	 *
	 * @param array $meta_boxes Array of meta boxes.
	 *
	 * @return array
	 */
	public function filter( $meta_boxes ) {
		foreach ( $meta_boxes as $k => $meta_box ) {
			if ( isset( $meta_box['type'] ) && 'user' === $meta_box['type'] ) {
				unset( $meta_box['post_types'], $meta_box['pages'] );
				self::$meta_boxes[] = $meta_box;

				// Prevent adding meta box to post.
				unset( $meta_boxes[ $k ] );
			}
		}

		return $meta_boxes;
	}

	/**
	 * Register meta boxes for user, each meta box is a section
	 */
	public function register() {
		$field_registry = rwmb_get_registry( 'field' );

		foreach ( self::$meta_boxes as $meta_box ) {
			$meta_box = new MB_User_Meta_Box( $meta_box );

			foreach ( $meta_box->fields as $field ) {
				$field_registry->add( $field, 'user', 'user' );
			}
		}
	}

	/**
	 * Filter meta type from object type and object id.
	 *
	 * @param string $type        Meta type get from object type and object id.
	 *                                Assert 'user' if object type is 'term'.
	 * @param string $object_type Object type.
	 */
	public function filter_meta_type( $type, $object_type ) {
		if ( 'user' === $object_type ) {
			return 'user';
		}

		return $type;
	}
}
