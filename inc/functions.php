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
 * Checks if the Groups Admin profile screen is displayed.
 *
 * @since 1.0.0
 *
 * @return boolean True if the Groups Admin profile screen is displayed.
 *                 False otherwise.
 */
function profil_de_groupes_is_admin() {
	$return = false;

	if ( ! is_admin() ) {
		return $return;
	}

	$current_screen = get_current_screen();

	if ( ! isset( $current_screen->id ) ) {
		return $return;
	} else {
		$screen_id = explode( '_', $current_screen->id );
		$return    = 'bp-profile-setup-groupes' === end( $screen_id );
	}

	return $return;
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
			if ( profil_de_groupes_is_admin() || ( isset( $args['profile_group_id'] ) && profil_de_groupes_get_fields_group() === (int) $args['profile_group_id'] ) ) {
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
 * Temporary filter to shortcircuit BP_XProfile_ProfileData->save().
 *
 * @since 1.0.0
 *
 * @param  BP_XProfile_ProfileData $field_data The field data object.
 */
function profil_de_groupes_save_field_data( $field_data ) {
	$field_data->user_id = $field_data->field_id = 0;
}

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
	add_action( 'xprofile_data_before_save', 'profil_de_groupes_save_field_data' );

	$validated_field = xprofile_set_field_data( $field_id, $group_id, $value, $is_required );

	remove_action( 'xprofile_data_before_save', 'profil_de_groupes_save_field_data' );

	$field_data = new Profil_De_Groupes_Group_Data;
	$field_data->field_id = $field_id;
	$field_data->group_id = $group_id;
	$field_data->value    = maybe_serialize( $value );

	$saved = $field_data->save();

	/**
	 * Fires when a Group's profile field has been saved.
	 *
	 * @since  1.0.0
	 *
	 * @param  integer $field_id The ID of the field.
	 * @param  integer $group_id The ID of the group.
	 * @param  boolean $saved    True on success, false on failure.
	 */
	do_action( 'profil_de_groupes_set_field_data', $field_id, $group_id, $saved );

	return $saved;
}

/**
 * Fetches fields data for the current Group.
 *
 * @since  1.0.0
 */
function profil_de_groupes_fetch_fields_data( $group_id = 0 ) {
	global $group;

	$field_ids = array();

	if ( ! empty( $group->fields ) ) {
		$field_ids = wp_list_pluck( $group->fields, 'id' );
	}

	if ( ! $field_ids ) {
		return false;
	}

	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	$group_fields = (array) wp_cache_get( 'group_fields', 'profil_de_groupes' );
	$group_fields = array_filter( $group_fields );

	// Use Database
	if ( ! isset( $group_fields[ $group_id ] ) ) {
		$data = Profil_De_Groupes_Group_Data::get_data_for_group( $group_id, $field_ids );

		if ( ! is_array( $data ) ) {
			return false;
		}

		foreach ( $group->fields as $k => $field ) {
			$data_field = wp_list_filter( $data, array( 'field_id' => $field->id ) );

			if ( empty( $data_field ) || ! is_array( $data_field ) ) {
				continue;
			}

			$data_field = reset( $data_field );
			$group->fields[ $k ]->data = (object) array(
				'id'       => $data_field->id,
				'field_id' => $field->id,
				'name'     => $field->name,
				'value'    => $data_field->value
			);
		}

		$d = wp_filter_object_list( $group->fields, array(), 'and', 'data' );
		$group_fields[ $group_id ] = array();

		foreach( $d as $f ) {
			$group_fields[ $group_id ][ $f->field_id] = $f;
		}

		wp_cache_set( 'group_fields', $group_fields, 'profil_de_groupes' );

	// Use cache.
	} else {
		foreach ( $group->fields as $k => $field ) {
			if ( ! isset( $group_fields[ $group_id ][ $field->id ] ) ) {
				continue;
			}

			$group->fields[ $k ]->data = $group_fields[ $group_id ][ $field->id ];
		}
	}
}

/**
 * Set the selected option/checked value in field containing options.
 *
 * @since 1.0.0
 *
 * @param  string  $output   Tag for the value being rendered.
 * @param  object  $option   The option object being rendered for.
 * @param  integer $field_id ID of the field object being rendered.
 * @return string            Tag for the value being rendered.
 */
function profil_de_groupes_set_options_field( $output = '', $option = null, $field_id = 0, $selected = '', $i = 0 ) {
	global $group;

	if ( empty( $group->fields ) || empty( $option->name ) ) {
		return $output;
	}

	$current_field = wp_filter_object_list( $group->fields, array( 'id' => $field_id ), 'and' );
	$current_field = reset( $current_field );

	if ( ! isset( $current_field->data->value ) ) {
		return $output;
	}

	$options = maybe_unserialize( $current_field->data->value );

	if ( is_a( $current_field->type_obj, 'BP_XProfile_Field_Type_Checkbox' ) || is_a( $current_field->type_obj, 'BP_XProfile_Field_Type_Radiobutton' ) ) {
		$input_name = bp_get_the_profile_field_input_name();

		if ( ! empty( $current_field->type_obj->supports_multiple_defaults ) ) {
			$input_name .= '[]';
		}

		if ( in_array( $option->name, (array) $options, true ) ) {
			$output = sprintf( '<label for="%2$s" class="option-label"><input checked="checked" type="%5$s" name="%1$s" id="%2$s" value="%3$s">%4$s</label>',
				esc_attr( $input_name ),
				esc_attr( "field_{$option->id}_{$i}" ),
				esc_attr( stripslashes( $option->name ) ),
				esc_html( stripslashes( $option->name ) ),
				esc_attr( $current_field->type_obj->field_obj->type )
			);
		}
	} else {
		if ( in_array( $option->name, (array) $options, true ) ) {
			$output = sprintf(
				'<option selected="selected" value="%1$s">%2$s</option>',
				esc_attr( stripslashes( $option->name ) ),
				esc_html( stripslashes( $option->name ) )
			);
		}
	}

	return $output;
}

/**
 * Set the selected options for the date field.
 *
 * @since 1.0.0
 *
 * @param  string  $html     HTML output for the date field.
 * @param  string  $type     Which date type is being rendered for.
 * @param  string  $day      Date formatted for the current day.
 * @param  string  $month    Date formatted for the current month.
 * @param  string  $year     Date formatted for the current year.
 * @param  integer $field_id ID of the field object being rendered.
 * @param  string  $date     Current date.
 * @return string            HTML output for the date field.
 */
function profil_de_groupes_set_options_date( $html = '', $type = '', $day = '', $month = '', $year = '', $field_id = 0, $date = '' ) {
	global $group;

	if ( empty( $group->fields ) ) {
		return $html;
	}

	$current_field = wp_filter_object_list( $group->fields, array( 'id' => $field_id ), 'and' );
	$current_field = reset( $current_field );

	if ( ! isset( $current_field->data->value ) ) {
		return $html;
	}

	$date = $current_field->data->value;

	$types = array(
		'day'   => 'j',
		'month' => 'F',
		'year'  => 'Y',
	);

	// Set day, month, year defaults.
	if ( ! empty( $date ) ) {
		// If Unix timestamp.
		if ( is_numeric( $date ) ) {
			$current = date( $types[ $type ], $date );

		// If MySQL timestamp.
		} else {
			$current = mysql2date( $types[ $type ], $date, false );
		}

		$selected = ' selected="selected"';
		$html     = str_replace( $selected, '', $html );
		$find     = addcslashes( sprintf( '/value="%s"/', $current ), '"' );

		if ( preg_match( $find, $html, $match ) ) {
			$match = reset( $match );
			$html  = str_replace( $match, $match.$selected, $html );
		}
	}

	return $html;
}

/**
 * Adds temporary filters for fields using options.
 *
 * @since  1.0.0
 *
 * @todo as it only takes care of Dropdowns list, take care of all other kind of fields.
 */
function profil_de_groupes_add_field_filters() {
	add_filter( 'bp_get_the_profile_field_options_select',      'profil_de_groupes_set_options_field', 10, 3 );
	add_filter( 'bp_get_the_profile_field_options_checkbox',    'profil_de_groupes_set_options_field', 10, 5 );
	add_filter( 'bp_get_the_profile_field_options_radio',       'profil_de_groupes_set_options_field', 10, 5 );
	add_filter( 'bp_get_the_profile_field_options_multiselect', 'profil_de_groupes_set_options_field', 10, 5 );

	// This one is particular
	add_filter( 'bp_get_the_profile_field_datebox', 'profil_de_groupes_set_options_date', 10, 7 );
}

/**
 * Removes temporary filters for fields using options.
 *
 * @since  1.0.0
 *
 * @todo as it only takes care of Dropdowns list, take care of all other kind of fields.
 */
function profil_de_groupes_remove_field_filters() {
	remove_filter( 'bp_get_the_profile_field_options_select',      'profil_de_groupes_set_options_field', 10, 3 );
	remove_filter( 'bp_get_the_profile_field_options_checkbox',    'profil_de_groupes_set_options_field', 10, 5 );
	remove_filter( 'bp_get_the_profile_field_options_radio',       'profil_de_groupes_set_options_field', 10, 5 );
	remove_filter( 'bp_get_the_profile_field_options_multiselect', 'profil_de_groupes_set_options_field', 10, 5 );

	// This one is particular
	remove_filter( 'bp_get_the_profile_field_datebox', 'profil_de_groupes_set_options_date', 10, 7 );
}

/**
 * Get a Group's field data.
 *
 * @since  1.0.0
 *
 * @param  array|string      $fields   Field(s) to get.
 * @param  integer           $group_id Group ID to get field data for.
 * @return string|array|bool           The field value, an array of field values.
 *                                     False when no values were found.
 */
function profil_de_groupes_get_field_data( $names = '', $group_id = 0 ) {
	if ( empty( $names ) ) {
		return false;
	}

	$return_array = true;
	if ( ! is_array( $names ) ) {
		$names        = (array) $names;
		$return_array = false;
	}

	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	$data         = array();
	$field_name   = array();
	$group_fields = (array) wp_cache_get( 'group_fields', 'profil_de_groupes' );
	$group_fields = array_filter( $group_fields );

	foreach ( $names as $k => $name ) {
		$key = sanitize_key( $name );

		$field_name[ $key ] = (array) wp_cache_get( $key, 'profil_de_groupes' );
		$field_name[ $key ] = array_filter( $field_name[ $key ] );

		if ( ! isset( $field_name[ $key ][ $group_id ] ) ) {
			if ( ! isset( $group_fields[ $group_id ] ) ) {
				continue;
			}

			$gfc = wp_list_filter( $group_fields[ $group_id ], array( 'name' => $name ) );
			$gfc = reset( $gfc );

			if ( empty( $gfc ) ) {
				continue;
			}

			$data[ $name ]                   = $gfc->value;
			$field_name[ $key ][ $group_id ] = $gfc->value;


			wp_cache_set( $key, $field_name[ $key ], 'profil_de_groupes' );
			unset( $names[ $k ] );
		} else {
			$data[ $name ] = $field_name[ $key ][ $group_id ];
			unset( $names[ $k ] );
		}
	}

	if ( ! empty( $names ) ) {
		$db_data = Profil_De_Groupes_Group_Data::get_value_byfieldname( $names, $group_id );

		if ( $db_data ) {
			foreach ( $db_data as $kn => $vn ) {
				$key_n = sanitize_key( $kn );

				$field_name[ $key_n ][ $group_id ] = $vn;
				wp_cache_set( $key_n, $field_name[ $key_n ], 'profil_de_groupes' );
			}
		}

		if ( is_array( $db_data ) ) {
			$data = array_merge( $data, $db_data );
		}
	}

	if ( ! $data ) {
		return false;

	} elseif ( $return_array ) {
		return array_map( 'maybe_unserialize', $data );
	}

	$data = reset( $data );
	return maybe_unserialize( $data );
}

/**
 * Adds the plugin's cache group.
 *
 * @since 1.0.0
 */
function profil_de_groupes_setup_cache_group() {
	wp_cache_add_global_groups( 'profil_de_groupes' );
}
add_action( 'bp_include', 'profil_de_groupes_setup_cache_group' );

/**
 * Clears the Group fields cache if it exists.
 *
 * @since  1.0.0
 */
function profil_de_groupes_clear_group_fields_cache() {
	$group_fields = wp_cache_get( 'group_fields', 'profil_de_groupes' );

	if ( false !== $group_fields ) {
		wp_cache_delete( 'group_fields', 'profil_de_groupes' );
	}
}

/**
 * Clears the Profil de Groupes caches when needed.
 *
 * @since  1.0.0
 */
function profil_de_groupes_clear_caches() {
	profil_de_groupes_clear_group_fields_cache();

	$groups_profile = bp_xprofile_get_groups( array_merge( profil_de_groupes_get_loop_args(), array(
		'fetch_fields' => true,
	) ) );

	$groups_profile = reset( $groups_profile );

	foreach ( wp_list_pluck( $groups_profile->fields, 'name' ) as $name ) {
		$key = sanitize_key( $name );
		wp_cache_delete( $key, 'profil_de_groupes' );
	}
}
add_action( 'profil_de_groupes_set_field_data', 'profil_de_groupes_clear_caches' );

/**
 * Clears the Field's name cache.
 *
 * @since  1.0.0
 *
 * @param  BP_XProfile_Field $field The field object
 */
function profil_de_groupes_clear_field_cache( BP_XProfile_Field $field ) {
	profil_de_groupes_clear_group_fields_cache();

	if ( empty( $field->name ) ) {
		return;
	}

	$key = sanitize_key( $field->name );
	wp_cache_delete( $key, 'profil_de_groupes' );
}
add_action( 'profil_de_groupes_deleted_field', 'profil_de_groupes_clear_field_cache', 10, 1 );
