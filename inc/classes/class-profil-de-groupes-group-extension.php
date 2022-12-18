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
			'name'              => __( 'A propos de nous', 'profil-de-groupes' ),
			'nav_item_position' => 14,
			'show_tab'          => 'loggedin',
			'screens'           => array(
				'admin' => array(
					'enabled' => false,
				),
				'create' => array(
					'enabled' => true,
					'position' => 14,
				),
				'edit' => array(
					'enabled' => true,
				),
			),
		) );
	}

	/**
	 * Determine whether the current user should see this nav tab.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $user_can_see_nav_item Whether or not the user can see the nav item.
	 * @return bool
	 */
	public function user_can_see_nav_item( $user_can_see_nav_item = false ) {
		$group = groups_get_current_group();

		if ( isset( $group->status ) && 'public' !== $group->status ) {
			return (bool) groups_is_user_member( bp_loggedin_user_id(), $group->id ) || bp_current_user_can( 'bp_moderate' );
		}

		return parent::user_can_see_nav_item( $user_can_see_nav_item );
	}

	/**
	 * Unused
	 */
	public function admin_screen( $group_id = null ) {}
	public function admin_screen_save( $group_id = null ) {}
	public function widget_display() {}

	/**
	 * Checks if the installation has been completed.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean Whether the group of Group Profile fields has been created or not.
	 */
	public function installation_failed() {
		$return = '';

		if ( ! profil_de_groupes_get_fields_group() ) {
			$return = sprintf( '<div id="message" class="error"><p>%s</p></div>',
				esc_html__( 'Une erreur est survenue, merci de contacter l\'administrateur de ce site', 'profil-de-groupes' )
			);
		}

		return $return;
	}

	/**
	 * Display the create step.
	 *
	 * @since  1.0.0
	 *
	 * @param  integer $group_id The ID of the group being created.
	 */
	public function create_screen( $group_id = null ) {
		$this->edit_screen( $group_id );
	}

	/**
	 * Saves the fields during the create step.
	 *
	 * @since  1.0.0
	 *
	 * @param  integer $group_id The ID of the group being created.
	 */
	public function create_screen_save( $group_id = null ) {
		$this->edit_screen_save( $group_id );
	}

	/**
	 * Displays the Group's profile fields.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $group_id The group ID.
	 */
	public function display( $group_id = null ) {
		if ( $error = $this->installation_failed() ) {
			echo $error;
			return;
		}

		add_filter( 'bp_get_template_stack', 'profil_de_groupes_template_stack', 10, 1 );

		bp_get_template_part( 'groups/single/profil-du-groupe' );

		remove_filter( 'bp_get_template_stack', 'profil_de_groupes_template_stack', 10, 1 );
	}

	/**
	 * Displays the form to edit fields.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $group_id The group ID.
	 */
	public function edit_screen( $group_id = null ) {
		if ( $error = $this->installation_failed() ) {
			echo $error;
			return;
		}

		$pdg = profil_de_groupes();
		$pdg->current_group_id = $group_id;

		add_filter( 'bp_get_template_stack', 'profil_de_groupes_template_stack', 10, 1 );

		bp_get_template_part( 'groups/single/admin/profil-du-groupe' );

		remove_filter( 'bp_get_template_stack', 'profil_de_groupes_template_stack', 10, 1 );

		unset( $pdg->current_group_id );
	}

	/**
	 * Save the fields of the group.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $group_id The group ID.
	 */
	public function edit_screen_save( $group_id = null ) {
		if ( empty( $_POST['field_ids'] ) || ( ! bp_is_item_admin() && ! bp_is_group_create() ) ) {
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

			if ( bp_is_group_create() ) {
				bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_get_groups_current_create_step() ) );
			}

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
			} elseif ( bp_is_group_create() ) {
				return true;

			} else {
				bp_core_add_message( __( 'Profil mis à jour avec succès.', 'profil-de-groupes' ) );
			}

			// Redirect back to the edit screen to display the updates and message.
			bp_core_redirect( bp_get_group_permalink( buddypress()->groups->current_group ) . 'admin/' . $this->slug );
		}
	}
}

endif;
