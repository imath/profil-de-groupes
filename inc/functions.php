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

/**
 * Sets field data for a specific field and a specific group.
 *
 * @since 1.0.0
 *
 * @param  integer    $field_id    The ID of the field.
 * @param  integer    $group_id    The ID of the group.
 * @param  mixed      $value       The value for the field you want to set for the group.
 * @param  boolean    $is_required Whether or not the field is required.
 * @return boolean                 True on success, false on failure.
 */
function profil_de_groupes_set_field_data( $field_id = 0, $group_id = 0, $value = '', $is_required = false ) {
	add_action( 'xprofile_data_before_save', '__return_true' );

	$validated_field = xprofile_set_field_data( $field_id, $group_id, $value, $is_required );

	remove_action( 'xprofile_data_before_save', '__return_true' );

	if ( ! $validated_field ) {
		return false;
	}

	$field_data = new Profil_De_Groupes_Group_Data;
	$field_data->field_id = $field_id;
	$field_data->group_id = $group_id;
	$field_data->value    = maybe_serialize( $value );

	return $field_data->save();
}

/**
 * Fetches fields data for the current Group.
 *
 * @since  1.0.0
 */
function profil_de_groupes_fetch_fields_data() {
	global $group;

	$field_ids = array();

	if ( ! empty( $group->fields ) ) {
		$field_ids = wp_list_pluck( $group->fields, 'id' );
	}


	if ( ! $field_ids ) {
		return false;
	}

	$data = Profil_De_Groupes_Group_Data::get_data_for_group( bp_get_current_group_id(), $field_ids );

	if ( ! is_array( $data ) ) {
		return false;
	}

	foreach ( $group->fields as $k => $field ) {
		$data_field = wp_list_filter( $data, array( 'field_id' => $field->id ) );
		$data_field = reset( $data_field );
		$group->fields[ $k ]->data = (object) array( 'id' => $data_field->id, 'value' => $data_field->value );
	}
}

/**
 * Set the selected option in a dropdown field type.
 *
 * @since 1.0.0
 *
 * @param  string  $output   Option tag for the value being rendered.
 * @param  object  $option   The option object being rendered for.
 * @param  integer $field_id ID of the field object being rendered.
 * @return string            Option tag for the value being rendered.
 */
function profil_de_groupes_set_options_select( $output = '', $option = null, $field_id = 0 ) {
	global $group;

	if ( empty( $group->fields ) || empty( $option->name ) ) {
		return $output;
	}

	$current_option = wp_filter_object_list( $group->fields, array( 'id' => $field_id ), 'and', 'data' );
	$current_option = reset( $current_option );

	if ( $current_option->value === $option->name ) {
		$output = sprintf(
			'<option selected="selected" value="%1$s">%2$s</option>',
			esc_attr( stripslashes( $option->name ) ),
			esc_html( stripslashes( $option->name ) )
		);
	}

	return $output;
}

/**
 * Adds temporary filters for fields using options.
 *
 * @since  1.0.0
 *
 * @todo as it only takes care of Dropdowns list, take care of all other kind of fields.
 */
function profil_de_groupes_add_field_filters() {
	add_filter( 'bp_get_the_profile_field_options_select', 'profil_de_groupes_set_options_select', 10, 3 );
}

/**
 * Removes temporary filters for fields using options.
 *
 * @since  1.0.0
 *
 * @todo as it only takes care of Dropdowns list, take care of all other kind of fields.
 */
function profil_de_groupes_remove_field_filters() {
	remove_filter( 'bp_get_the_profile_field_options_select', 'profil_de_groupes_set_options_select', 10, 3 );
}
