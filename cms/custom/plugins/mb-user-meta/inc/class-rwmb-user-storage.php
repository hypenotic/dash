<?php
/**
 * User storage
 *
 * @package    Meta Box
 * @subpackage MB Term Meta
 */

if ( ! interface_exists( 'RWMB_Storage_Interface' ) ) {
	return;
}

/**
 * Class RWMB_User_Storage
 */
class RWMB_User_Storage implements RWMB_Storage_Interface {

	/**
	 * Get value from storage.
	 *
	 * @param  int    $object_id Object id.
	 * @param  string $name      Field name.
	 * @param  array  $args      Custom arguments.
	 * @return mixed
	 */
	public function get( $object_id, $name, $args = array() ) {
		$single = ! empty( $args['single'] );

		return get_user_meta( $object_id, $name, $single );
	}
}
