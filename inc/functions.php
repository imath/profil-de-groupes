<?php
/**
 * Profil De Groupes functions.
 *
 * @package ProfilDeGroupes\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gets the Groups group of fields ID.
 *
 * @since  1.0.0
 *
 * @return int The Groups group of fields ID.
 */
function profil_de_groupes_get_fields_group() {
	return profil_de_groupes()->fields_group;
}

/**
 * Overrides the list of available Groups of fields.
 *
 * PS: if the Groups group of fields is requested, returns this one only.
 *     if Users groups of fields are requested, remove the Groups group of fields from the list.
 *
 * @since  1.0.0
 *
 * @param  array  $groups List of groups of fields.
 * @param  array  $args   The request parameters.
 * @return array          List of groups of fields.
 */
function profil_de_groupes_set_fields_group( $groups = array(), $args = array() ) {
	if ( is_array( $groups ) ) {
		$group_field_group = wp_list_filter( $groups, array( 'id' => profil_de_groupes_get_fields_group() ) );

		if ( ! empty( $group_field_group ) ) {
			if ( ( is_admin() && 'groups_page_bp-profile-setup-groupes' === get_current_screen()->id )
			  || ( isset( $args['profile_group_id'] ) && profil_de_groupes_get_fields_group() === (int) $args['profile_group_id'] ) ) {
				return $group_field_group;
			}

			$index = key( $group_field_group );
			array_splice( $groups, $index, 1 );
		}
	}

	return $groups;
}
add_filter( 'bp_xprofile_get_groups', 'profil_de_groupes_set_fields_group', 10, 2 );

/**
 * Gets the slug for the Group extension.
 *
 * @since  1.0.0
 *
 * @return string The slug for the Group extension.
 */
function profil_de_groupes_get_slug() {
	return apply_filters( 'profil_de_groupes_get_slug', _x( 'a-propos', 'Group Profile slug', 'profil-de-groupes' ) );
}

/**
 * Registers the Group Extension.
 *
 * @since  1.0.0
 */
function profil_de_groupes_register_group_extension() {
	bp_register_group_extension( 'Profil_De_Groupes_Group_Extension' );
}
add_action( 'bp_init', 'profil_de_groupes_register_group_extension' );
