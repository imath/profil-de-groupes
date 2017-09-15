<?php
/**
 * Profil De Groupes template functions.
 *
 * @package ProfilDeGroupes\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Inject Plugin's templates dir into the BuddyPress Templates dir stack.
 *
 * @since 1.0.0
 *
 * @param  array $template_stack The list of available locations to get BuddyPress templates
 * @return array                 The list of available locations to get BuddyPress templates
 */
function profil_de_groupes_template_stack( $template_stack = array() ) {
	if ( ! bp_is_group() ) {
		return $template_stack;
	}

	// Set default priority into the Stack
	$priority = 0;

	foreach ( $template_stack as $t => $template ) {
		if ( false !== strrpos( $template, bp_get_theme_compat_dir() ) ) {
			$priority = $t;
			break;
		}
	}

	// Before BuddyPress active's template pack.
	$bp_legacy = array_splice( $template_stack, $priority );

	return array_merge(
		$template_stack,
		array( profil_de_groupes()->tpl_dir ),
		$bp_legacy
	);
}
add_filter( 'bp_get_template_stack', 'profil_de_groupes_template_stack', 10, 1 );

/**
 * Builds the query argument for the Group's profile loop.
 *
 * @since 1.0.0
 *
 * @return array The query argument for the Group's profile loop.
 */
function profil_de_groupes_get_loop_args() {
	/**
	 * Filter here to add custom args for the public profile loop.
	 *
	 * @since  1.0.0
	 *
	 * @param array $value The custom arguments for the public loop.
	 */
	$customs = apply_filters( 'profil_de_groupes_get_loop_args', array() );

	return array_merge( array(
		'profile_group_id' => profil_de_groupes_get_fields_group(),
		'fetch_field_data' => false
	), $customs );
}

/**
 * Init the Group's profile loop.
 *
 * Wraps bp_has_profile().
 *
 * @since 1.0.0
 *
 * @return boolean True if the group has profile. False otherwise.
 */
function profil_de_groupes_has_profile() {
	return bp_has_profile( profil_de_groupes_get_loop_args() );
}

/**
 * Populates the Group's profile loop.
 *
 * Wraps bp_profile_groups().
 *
 * @since 1.0.0
 *
 * @return boolean True if there are more profiles to display. False otherwise.
 */
function profil_de_groupes_profiles() {
	return bp_profile_groups();
}

/**
 * Sets the current profile and fetches data for the Group.
 *
 * Wraps bp_the_profile_group().
 *
 * @since 1.0.0
 */
function profil_de_groupes_profile() {
	$group_id = null;
	$pdg      = profil_de_groupes();

	if ( isset( $pdg->current_group_id ) ) {
		$group_id = $pdg->current_group_id;
	}

	bp_the_profile_group();
	profil_de_groupes_fetch_fields_data( $group_id );
}

/**
 * Check if the profile has fields.
 *
 * Wraps bp_profile_group_has_fields().
 *
 * @since 1.0.0
 *
 * @return boolean True if the profile fields. False otherwise.
 */
function profil_de_groupes_has_fields() {
	return bp_profile_group_has_fields();
}

/**
 * Populates the fields.
 *
 * Wraps bp_profile_fields().
 *
 * @since 1.0.0
 *
 * @return boolean True if there are more fields to display. False otherwise.
 */
function profil_de_groupes_fields() {
	return bp_profile_fields();
}

/**
 * Sets the current field.
 *
 * Wraps bp_the_profile_field().
 *
 * @since 1.0.0
 */
function profil_de_groupes_field() {
	bp_the_profile_field();
}

/**
 * Check if the field has date.
 *
 * Wraps bp_field_has_data().
 *
 * @since 1.0.0
 *
 * @return boolean True if the field has date. False otherwise.
 */
function profil_de_groupes_field_has_data() {
	return bp_field_has_data();
}

/**
 * Outputs the field's entry CSS class attibute.
 *
 * @since 1.0.0
 */
function profil_de_groupes_field_css_class( $context = 'displayfield' ) {
	echo bp_get_field_css_class( $context );
}

/**
 * Outputs the field's name.
 *
 * Wraps bp_the_profile_field_name().
 *
 * @since 1.0.0
 */
function profil_de_groupes_field_name() {
	bp_the_profile_field_name();
}

/**
 * Outputs the field's value.
 *
 * Wraps bp_the_profile_field_value().
 *
 * @since 1.0.0
 */
function profil_de_groupes_field_data() {
	bp_the_profile_field_value();
}

/**
 * Generates the field edit output.
 *
 * @since 1.0.0
 */
function profil_de_groupes_edit_field() {
	$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );

	profil_de_groupes_add_field_filters();

	$field_type->edit_field_html();

	profil_de_groupes_remove_field_filters();
}

/**
 * Outputs a comma separated list of field IDs.
 *
 * Wraps bp_the_profile_field_value().
 *
 * @since 1.0.0
 */
function profil_de_groupes_field_ids() {
	bp_the_profile_field_ids();
}

/**
 * Does the current group has no profile fields defined?
 *
 * @since  1.0.0
 *
 * @return boolean True if the group has no profile fields defined. False otherwise.
 */
function profil_de_groupes_empty_profile() {
	global $group;

	$empty_profile = true;

	if ( ! empty( $group->fields ) ) {
		$field_data = wp_list_pluck( $group->fields, 'data' );

		$empty_profile = empty( array_filter( $field_data ) );
	}

	return $empty_profile;
}
