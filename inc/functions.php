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
 * Overrides the list of available Groups of fields.
 *
 * PS: if the Groups group of fields is requested, returns this one only.
 *     if Users groups of fields are requested, remove the Groups group of fields from the list.
 *
 * @since  1.0.0
 *
 * @param  array  $groups List of groups of fields.
 * @return array          List of groups of fields.
 */
function profil_de_groupes_set_fields_group( $groups = array() ) {
	if ( is_array( $groups ) ) {
		$group_field_group = wp_list_filter( $groups, array( 'id' => 2 ) );

		if ( $group_field_group ) {
			if ( is_admin() && 'groups_page_bp-profile-setup-groupes' === get_current_screen()->id ) {
				return $group_field_group;
			}

			$index = key( $group_field_group );
			unset( $groups[ $index ] );
		}
	}

	return $groups;
}
add_filter( 'bp_xprofile_get_groups', 'profil_de_groupes_set_fields_group', 10, 1 );
