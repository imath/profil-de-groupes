<?php
/**
 * Profil De Groupes Admin functions.
 *
 * @package ProfilDeGroupes\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Does the plugin needs to be upgraded ?
 *
 * @since 1.0.0
 *
 * @param  string db_version The DB version of the plugin.
 * @return bool              True if it's an upgrade. False otherwise.
 */
function profil_de_groupes_admin_is_upgrade( $db_version = null ) {
	if ( is_null( $db_version ) ) {
		$db_version = bp_get_option( '_profil_de_groupes_version', 0 );
	}

	return version_compare( $db_version, profil_de_groupes()->version, '<' );
}

/**
 * Is this the first install of the plugin ?
 *
 * @since 1.0.0
 *
 * @param  string db_version The DB version of the plugin.
 * @return bool              True if it's the first install. False otherwise.
 */
function profil_de_groupes_admin_is_install( $db_version = null ) {
	if ( is_null( $db_version ) ) {
		$db_version = bp_get_option( '_profil_de_groupes_version', 0 );
	}

	return 0 === $db_version;
}

/**
 * Gets the main Profile group properties
 *
 * @since  1.0.0
 *
 * @return array The main Profile group properties.
 */
function profil_de_groupes_admin_get_group_properties() {
	return array(
		'name'        => 'profil_de_groupes',
		'description' => __( 'Liste des champs de profil pour les Groupes', 'profil-de-groupes' ),
		'can_delete'  => false,
	);
}

/**
 * Install or upgrade the plugin.
 *
 * @since  1.0.0
 */
function profil_de_groupes_admin_updater() {
	$db_version   = bp_get_option( '_profil_de_groupes_version', 0 );
	$version_bump = false;

	// It's the first time the plugin is activated.
	if ( profil_de_groupes_admin_is_install( $db_version ) ) {
		// Avoid duplicates!
		if ( bp_get_option( '_profil_de_groupes_id', 0 ) ) {
			return;
		}

		$fields_group = xprofile_insert_field_group( profil_de_groupes_admin_get_group_properties() );

		if ( $fields_group ) {
			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			dbDelta( array(
				"CREATE TABLE {$wpdb->base_prefix}profil_de_groupes_data (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
					field_id bigint(20) unsigned NOT NULL,
					group_id bigint(20) unsigned NOT NULL,
					value longtext NOT NULL,
					last_updated datetime NOT NULL,
					KEY field_id (field_id),
					KEY group_id (group_id)
				) {$wpdb->get_charset_collate()};"
			) );

			// Set the Group profiles option.
			bp_update_option( '_profil_de_groupes_id', $fields_group );
		}

		$version_bump = true;

		/**
		 * Trigger the 'profil_de_groupes_install' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'profil_de_groupes_install' );

	// The plugin needs an upgrade.
	} elseif ( profil_de_groupes_admin_is_upgrade( $db_version ) ) {
		$version_bump = true;

		/**
		 * Trigger the 'profil_de_groupes' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'profil_de_groupes_upgrade', $db_version );

	// The plugin was deactivated and needs the main Profile group to be restored.
	} elseif ( ! xprofile_get_field_group( profil_de_groupes_get_fields_group() ) ) {
		global $wpdb;

		$wpdb->insert( buddypress()->profile->table_name_groups, array_merge( array(
			'id' => profil_de_groupes_get_fields_group(),
		), profil_de_groupes_admin_get_group_properties() ) );
	}

	// Update the db version.
	if ( $version_bump  ) {
		bp_update_option( '_profil_de_groupes_version', profil_de_groupes()->version );
	}
}
add_action( 'bp_admin_init', 'profil_de_groupes_admin_updater', 1050 );

/**
 * Adds a new submenu to the Groups Administration Menu.
 *
 * @since  1.0.0
 */
function profil_de_groupes_admin_menu() {

	// Bail if current user cannot moderate community.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	$screen_id = add_submenu_page(
		'bp-groups',
		_x( 'Champs de profil des groupes', 'admin page title', 'profil-de-groupes' ),
		_x( 'Champs de profil', 'admin menu title', 'profil-de-groupes' ),
		'bp_moderate',
		'bp-profile-setup-groupes',
		'profil_de_groupes_admin'
	);

	add_action( 'load-' . $screen_id, 'profil_de_groupes_load_admin' );
}
add_action( bp_core_admin_hook(), 'profil_de_groupes_admin_menu' );

/**
 * Early validates the field name.
 *
 * @since  1.0.0
 */
function profil_de_groupes_load_admin() {
	if ( ! isset( $_POST['saveField'] ) || ! isset( $_POST['title'] ) ) {
		return;
	}

	/**
	 * Let's make sure field names are unique to keep it
	 * simple for the cache strategy the plugin is using.
	 */
	$profile_get_vars = wp_parse_args( $_GET, array(
		'mode'     => false,
		'field_id' => 0,
	) );

	if ( in_array( $profile_get_vars['mode'], array( 'add_field', 'edit_field' ), true ) ) {
		$dupe_id = profil_de_groupes_admin_check_duplicates( $_POST['title'] );

		if ( $dupe_id && $dupe_id !== (int) $profile_get_vars['field_id'] ) {
			wp_safe_redirect( add_query_arg( 'pdg_error', 'dupe' ) );
			exit();
		}
	}
}

/**
 * Diplays the Groups Profile Admin UI.
 *
 * @since 1.0.0
 */
function profil_de_groupes_admin() {
	if ( ! xprofile_get_field_group( profil_de_groupes_get_fields_group() ) ) {
		printf( '<h1>%1$s</h1><div id="message" class="error notice is-dismissible"><p>%2$s</p></div>',
			esc_html__( 'Erreur', 'profil-de-groupes' ),
			esc_html__( 'L\'installation a échoué.', 'profil-de-groupes' )
		);

		// Stop here.
		return;
	}

	// Take care of the duplicate name error.
	if ( ! empty( $_GET['pdg_error'] ) ) {
		$message = apply_filters(
			'profil_de_groupes_admin_error',
			__( 'Il est impératif que le nom des champs soient uniques.', 'profil-de-groupes' ),
			$_GET['pdg_error']
		);

		xprofile_admin_screen( $message, 'error' );

	// Leave BuddyPress manage other cases.
	} else {
		// When a field is deleted, make sure the corresponding data is too.
		add_action( 'xprofile_fields_deleted_field', 'profil_de_groupes_admin_delete_field_data', 10, 1 );

		xprofile_admin();
	}
}

/**
 * Checks the field's name is not dupe.
 *
 * @since  1.0.0
 *
 * @param  string  $field_name The field name to check.
 * @return integer             The field ID found or 0 if no dupes were found.
 */
function profil_de_groupes_admin_check_duplicates( $field_name = '' ) {
	global $wpdb;

	if ( empty( $field_name ) ) {
		return 0;
	}

	$table = buddypress()->profile->table_name_fields;

	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$table} WHERE name = %s AND parent_id = 0 AND group_id = %d",
		$field_name,
		profil_de_groupes()->fields_group
	) );
}

/**
 * Deletes field's data when it has been deleted.
 *
 * @since  1.0.0
 *
 * @param  BP_XProfile_Field $field   The deleted field object.
 * @param  boolean           $deleted True if corresponding data is deleted, false otherwise.
 */
function profil_de_groupes_admin_delete_field_data( BP_XProfile_Field $field ) {
	if ( empty( $field->id ) ) {
		return;
	}

	$deleted = Profil_De_Groupes_Group_Data::delete_for_field( $field->id );

	/**
	 * Fires once the Groups profile field has been deleted.
	 *
	 * @since  1.0.0
	 *
	 * @param  BP_XProfile_Field $field   The deleted field object.
	 * @param  boolean           $deleted True if corresponding data is deleted, false otherwise.
	 */
	do_action( 'profil_de_groupes_deleted_field', $field, $deleted );

	return $deleted;
}

/**
 * Adds an inline JavaScript to customize the Profile Admin UI
 *
 * @since 1.0.0
 */
function profil_de_groupes_admin_js() {
	if ( ! profil_de_groupes_is_admin() ) {
		return;
	}

	$xprofile_admin_url = add_query_arg( 'page', 'bp-profile-setup', bp_get_admin_url( 'users.php' ) );
	$gprofile_admin_url = add_query_arg( 'page', 'bp-profile-setup-groupes', bp_get_admin_url( 'admin.php' ) );

	wp_add_inline_script( 'xprofile-admin-js', sprintf( '( function($) {
		$( document ).ready( function() {
			var urlReplace = \'%1$s\', urlBy = \'%2$s\';

			$( \'#add_group\' ).remove();
			$( \'#field-group-tabs\' ).remove();

			$.each( $( \'.tab-toolbar-left\' ).children(), function( i, a ) {
				if ( ! $( a ).hasClass( \'button-primary\' ) ) {
					$( a ).remove();
				}
			} );

			$.each( $( \'#profile-field-form a, #bp-xprofile-add-field a\' ), function( i, a ) {
				$( a ).prop( \'href\', $( a ).prop( \'href\' ).replace( urlReplace, urlBy ) );
			} );

			if ( $( \'#bp-xprofile-add-field\' ).length ) {
				$( \'#bp-xprofile-add-field\' ).prop( \'action\', $( \'#bp-xprofile-add-field\' ).prop( \'action\' ).replace( urlReplace, urlBy ) );

				// Remove the member types, visibility & autolink metabox
				$(\'#member-type-none\' ).closest( \'.postbox\' ).remove();
				$(\'#default-visibility\' ).closest( \'.postbox\' ).remove();
				$(\'#do-autolink\' ).closest( \'.postbox\' ).remove();
			}
		} );
	} )( jQuery )', esc_url_raw( $xprofile_admin_url ), esc_url_raw( $gprofile_admin_url ) ) );
}
add_action( 'bp_admin_enqueue_scripts', 'profil_de_groupes_admin_js' );

/**
 * Add a specific removable query arg to WordPress ones.
 *
 * @since  1.0.0
 *
 * @param  array $rqa The removable query args.
 * @return array      The removable query args.
 */
function profil_de_groupes_admin_removable_query_args( $rqa = array() ) {
	$rqa[] = 'pdg_error';
	return $rqa;
}
add_filter( 'removable_query_args', 'profil_de_groupes_admin_removable_query_args', 10, 1 );

/**
 * Deletes the main Profile Group of fields.
 *
 * This is needed to avoid the group of fields to appear
 * in user xProfile once the plugin is deactivated.
 *
 * @since 1.0.0
 */
function profil_de_groupes_admin_deactivation() {
	global $wpdb;
	$bp = buddypress();

	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$bp->profile->table_name_groups} WHERE id = %d",
		profil_de_groupes_get_fields_group()
	) );
}
add_action(
	'deactivate_' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/profil-de-groupes.php',
	'profil_de_groupes_admin_deactivation'
);
