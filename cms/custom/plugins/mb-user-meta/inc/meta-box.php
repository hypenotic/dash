<?php
/**
 * The main class of the plugin which handle show, edit, save custom fields (meta data) for users.
 * @package    Meta Box
 * @subpackage MB User Meta
 * @author     Tran Ngoc Tuan Anh <rilwis@gmail.com>
 */

/**
 * Class for handling custom fields (meta data) for users.
 */
class MB_User_Meta_Box extends RW_Meta_Box {
	/**
	 * Specific hooks for user.
	 */
	protected function object_hooks() {
		// Add meta fields to edit user page.
		add_action( 'show_user_profile', array( $this, 'show' ) );
		add_action( 'edit_user_profile', array( $this, 'show' ) );

		// Save user meta.
		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );

		add_action( "rwmb_before_{$this->meta_box['id']}", array( $this, 'show_heading' ) );
	}

	/**
	 * Show heading of the section.
	 */
	public function show_heading() {
		echo '<h2>', esc_html( $this->meta_box['title'] ), '</h2>';
	}

	/**
	 * Enqueue styles for user meta.
	 */
	public function enqueue() {
		if ( ! $this->is_edit_screen() ) {
			return;
		}
		parent::enqueue();
		list( , $url ) = RWMB_Loader::get_path( dirname( dirname( __FILE__ ) ) );
		wp_enqueue_style( 'mb-user-meta', $url . 'css/style.css', '', '1.0.0' );
	}

	/**
	 * Save meta fields for users
	 *
	 * @param int $user_id User ID.
	 */
	public function save( $user_id ) {
		// Check whether form is submitted properly.
		$nonce = (string) filter_input( INPUT_POST, "nonce_{$this->meta_box['id']}" );
		if ( ! wp_verify_nonce( $nonce, "rwmb-save-{$this->meta_box['id']}" ) ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			$name   = $field['id'];
			$single = $field['clone'] || ! $field['multiple'];
			$old    = get_user_meta( $user_id, $name, $single );
			$new    = isset( $_POST[ $name ] ) ? $_POST[ $name ] : ( $single ? '' : array() );

			// Allow field class change the value.
			$new = RWMB_Field::call( $field, 'value', $new, $old, 0 );
			$new = RWMB_Field::filter( 'value', $new, $field, $old );

			MB_User_Meta_Field::save( $new, $old, $user_id, $field );
		}
	}

	/**
	 * Check if user meta is saved.
	 *
	 * @return bool
	 */
	public function is_saved() {
		$user_id = self::get_object_id();

		foreach ( $this->fields as $field ) {
			$single = $field['clone'] || ! $field['multiple'];
			$value  = get_user_meta( $user_id, $field['id'], $single );
			if ( ( $single && '' !== $value ) || ( ! $single && array() !== $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if we're on the right edit screen.
	 *
	 * @param WP_Screen $screen Screen object. Optional. Use current screen object by default.
	 *
	 * @return bool
	 */
	public function is_edit_screen( $screen = null ) {
		$screen = get_current_screen();

		return in_array( $screen->id, array( 'profile', 'user-edit' ), true );
	}

	/**
	 * Get editing user ID.
	 *
	 * @return bool|int
	 */
	public static function get_object_id() {
		$user_id = false;
		$screen  = get_current_screen();
		if ( 'profile' === $screen->id ) {
			$user_id = get_current_user_id();
		} elseif ( 'user-edit' === $screen->id ) {
			$user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : false;
		}

		return $user_id;
	}
}
