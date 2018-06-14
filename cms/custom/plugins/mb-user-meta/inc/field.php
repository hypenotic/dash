<?php
/**
 * Handle field actions for users
 *
 * @package    Meta Box
 * @subpackage MB User Meta
 */

/**
 * Field class.
 */
class MB_User_Meta_Field {
	/**
	 * Add hooks for fields in user edit screen.
	 */
	public static function init() {
		add_action( 'load-profile.php', array( __CLASS__, 'hook' ) );
		add_action( 'load-user-edit.php', array( __CLASS__, 'hook' ) );
	}

	/**
	 * Hooks run in the edit profile/user page.
	 */
	public static function hook() {
		add_filter( 'rwmb_field_meta', array( __CLASS__, 'meta' ), 10, 3 );
	}

	/**
	 * Get field meta value
	 *
	 * @param mixed $meta  Meta value.
	 * @param array $field Field parameters.
	 * @param bool  $saved Is meta box saved.
	 *
	 * @return mixed
	 */
	public static function meta( $meta, $field, $saved ) {
		$user_id = MB_User_Meta_Box::get_object_id();

		$single = $field['clone'] || ! $field['multiple'];
		$meta   = get_user_meta( $user_id, $field['id'], $single );

		// Use $field['std'] only when the meta box hasn't been saved (i.e. the first time we run).
		$meta = ( ! $saved && '' === $meta || array() === $meta ) ? $field['std'] : $meta;

		// Escape attributes.
		$meta = RWMB_Field::call( $field, 'esc_meta', $meta );

		// Make sure meta value is an array for clonable and multiple fields.
		if ( $field['clone'] || $field['multiple'] ) {
			if ( empty( $meta ) || ! is_array( $meta ) ) {
				/**
				 * Note: if field is clonable, $meta must be an array with values,
				 * so that the foreach loop in self::show() runs properly.
				 *
				 * @see self::show()
				 */
				$meta = $field['clone'] ? array( '' ) : array();
			}
		}

		return $meta;
	}

	/**
	 * Save meta value.
	 *
	 * @param mixed $new     New meta value.
	 * @param mixed $old     Old meta value.
	 * @param int   $user_id User ID.
	 * @param array $field   Field parameters.
	 */
	public static function save( $new, $old, $user_id, $field ) {
		$name = $field['id'];

		// Media fields: remove user meta to save order.
		if ( in_array( $field['type'], array(
			'media',
			'file_advanced',
			'file_upload',
			'image_advanced',
			'image_upload',
		), true ) ) {
			$old = array();
			delete_user_meta( $user_id, $name );
		}

		// Remove user meta if it's empty.
		if ( '' === $new || array() === $new ) {
			delete_user_meta( $user_id, $name );

			return;
		}

		// If field is cloneable, value is saved as a single entry in the database.
		if ( $field['clone'] ) {
			$new = (array) $new;
			foreach ( $new as $k => $v ) {
				if ( '' === $v ) {
					unset( $new[ $k ] );
				}
			}
			update_user_meta( $user_id, $name, $new );

			return;
		}

		// If field is multiple, value is saved as multiple entries in the database (WordPress behaviour).
		if ( $field['multiple'] ) {
			foreach ( $new as $new_value ) {
				if ( ! in_array( $new_value, $old ) ) {
					add_user_meta( $user_id, $name, $new_value, false );
				}
			}
			foreach ( $old as $old_value ) {
				if ( ! in_array( $old_value, $new ) ) {
					delete_user_meta( $user_id, $name, $old_value );
				}
			}

			return;
		}

		// Default: just update user meta.
		update_user_meta( $user_id, $name, $new );
	}
}
