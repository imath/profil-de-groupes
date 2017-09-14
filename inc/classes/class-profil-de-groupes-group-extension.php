<?php
/**
 * Profil De Groupes Group extension class.
 *
 * @package ProfilDeGroupes\inc\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Group_Extension' ) ) :
/**
 * The Group Extension class.
 *
 * @since  1.0.0
 */
class Profil_De_Groupes_Group_Extension extends BP_Group_Extension {
	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::init(  array(
			'slug'              => profil_de_groupes_get_slug(),
			'name'              => __( 'A propos de nous', 'altctrl-public-group' ),
			'visibility'        => 'public',
			'nav_item_position' => 14,
			'enable_nav_item'   => true,
			'screens'           => array(
				'admin' => array(
					'enabled' => false,
				),
				'create' => array(
					'enabled' => false,
					'position' => 14,
				),
				'edit' => array(
					'enabled' => true,
				),
			),
		) );
	}

	/**
	 * Unused
	 */
	public function create_screen( $group_id = null ) {}
	public function create_screen_save( $group_id = null ) {}
	public function admin_screen( $group_id = null ) {}
	public function admin_screen_save( $group_id = null ) {}
	public function display( $group_id = null ) {}
	public function widget_display() {}

	/**
	 * Displays the form to edit fields.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $group_id The group ID.
	 */
	public function edit_screen( $group_id = null ) {
		$fields_group = profil_de_groupes_get_fields_group();

		if ( ! $fields_group ) {
			printf( '<div id="message" class="error"><p>%s</p></div>',
				esc_html__( 'Une erreur est survenue, merci de contacter l\'administrateur de ce site', 'profil-de-groupes' )
			);

			return;
		}

		if ( bp_has_profile( array( 'profile_group_id' => $fields_group, 'fetch_field_data' => false ) ) ) :
			while ( bp_profile_groups() ) : bp_the_profile_group(); profil_de_groupes_fetch_fields_data(); ?>

				<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

					<div<?php bp_field_css_class( 'editfield' ); ?>>
						<fieldset>

							<?php
							/**
							 * Generate the field edit output.
							 */
							$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );

							profil_de_groupes_add_field_filters();

							$field_type->edit_field_html();

							profil_de_groupes_remove_field_filters(); ?>

						</fieldset>
					</div>

				<?php endwhile; ?>

				<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

		<?php endwhile; endif;
	}

	/**
	 * Save the fields of the group.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $group_id The group ID.
	 */
	public function edit_screen_save( $group_id = null ) {
		if ( empty( $_POST['field_ids'] ) || ! bp_is_item_admin() ) {
			return;
		}

		$posted_field_ids = wp_parse_id_list( $_POST['field_ids'] );
		$is_required      = array();

		// Loop through the posted fields formatting any datebox values then validate the field.
		foreach ( (array) $posted_field_ids as $field_id ) {
			bp_xprofile_maybe_format_datebox_post_data( $field_id );

			$is_required[ $field_id ] = xprofile_check_is_required_field( $field_id );

			if ( $is_required[$field_id] && empty( $_POST['field_' . $field_id] ) ) {
				$errors = true;
			}
		}

		// There are errors.
		if ( ! empty( $errors ) ) {
			bp_core_add_message( __( 'Certains champs requis sont manquants. Merci de les définir.', 'profil-de-groupes' ), 'error' );

		// No errors.
		} else {

			// Reset the errors var.
			$errors = false;

			// Now we've checked for required fields, lets save the values.
			foreach ( (array) $posted_field_ids as $field_id ) {
				$value = '';

				if ( isset( $_POST['field_' . $field_id] ) ) {
					$value = $_POST['field_' . $field_id];
				}

				// Update the field data.
				$field_updated = profil_de_groupes_set_field_data( $field_id, $group_id, $value, $is_required[ $field_id ] );

				if ( ! $field_updated ) {
					$errors = true;
				} else {

					/**
					 * Fires on each iteration of a field being saved with no error.
					 *
					 * @since 1.0.0
					 *
					 * @param int    $field_id ID of the field that was saved.
					 * @param string $value    Value that was saved to the field.
					 */
					do_action( 'profil_de_groupes_field_updated', $field_id, $value );
				}
			}

			// Set the feedback messages.
			if ( ! empty( $errors ) ) {
				bp_core_add_message( __( 'Des erreurs sont survenues lors de la mise à jour des champs de profil du groupe.', 'profil-de-groupes' ), 'error' );
			} else {
				bp_core_add_message( __( 'Profil mis à jour avec succès.', 'profil-de-groupes' ) );
			}

			// Redirect back to the edit screen to display the updates and message.
			bp_core_redirect( bp_get_group_permalink( buddypress()->groups->current_group ) . 'admin/' . $this->slug );
		}
	}
}

endif;
