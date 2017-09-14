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
 * Install or upgrade the plugin.
 *
 * @since  1.0.0
 */
function profil_de_groupes_admin_updater() {
	$db_version = bp_get_option( '_profil_de_groupes_version', 0 );

	if ( ! profil_de_groupes_admin_is_upgrade( $db_version ) && ! profil_de_groupes_admin_is_install( $db_version ) ) {
		return;
	}

	if ( profil_de_groupes_admin_is_install( $db_version ) ) {
		// Avoid duplicates!
		if ( bp_get_option( '_profil_de_groupes_id', 0 ) ) {
			return;
		}

		$fields_group = xprofile_insert_field_group( array(
			'name'        => 'profil_de_groupes',
			'description' => __( 'Liste des champs de profil pour les Groupes', 'profil-de-groupes' ),
		) );

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

		/**
		 * Trigger the 'profil_de_groupes_install' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'profil_de_groupes_install' );

	} elseif ( profil_de_groupes_admin_is_upgrade( $db_version ) ) {
		/**
		 * Trigger the 'profil_de_groupes' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'profil_de_groupes', $db_version );
	}

	// Update the db version.
	bp_update_option( '_profil_de_groupes_version', profil_de_groupes()->version );
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

	add_submenu_page(
		'bp-groups',
		_x( 'Champs de profil des groupes', 'admin page title', 'profil-de-groupes' ),
		_x( 'Champs de profil', 'admin menu title', 'profil-de-groupes' ),
		'bp_moderate',
		'bp-profile-setup-groupes',
		'profil_de_groupes_admin'
	);
}
add_action( bp_core_admin_hook(), 'profil_de_groupes_admin_menu' );

/**
 * Loads the Groups Profile Admin UI.
 *
 * @since 1.0.0
 */
function profil_de_groupes_admin() {
	if ( ! profil_de_groupes()->fields_group ) {
		printf( '<h1>%1$s</h1><div id="message" class="error notice is-dismissible"><p>%2$s</p></div>',
			esc_html__( 'Erreur', 'profil-de-groupes' ),
			esc_html__( 'L\'installation a échoué.', 'profil-de-groupes' )
		);

		// Stop here.
		return;
	}

	// It's the xProfile one!
	xprofile_admin();
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
 * Saves the About us Group's tab display preferences for the field.
 *
 * @since  1.0.0
 *
 * @param  BP_XProfile_Field $field The BuddyPress Xprofile field object.
 */
function profil_de_groupes_admin_field_save_preference( BP_XProfile_Field $field ) {
	if ( ! isset( $_POST['_profil_de_groupes_about_us_current_value'] ) || ! $field->id ) {
		return;
	}

	if ( ! empty( $_POST['_profil_de_groupes_about_us'] ) ) {
		bp_xprofile_update_field_meta( $field->id, '_profil_de_groupes_about_us', 1 );
	} elseif ( ! empty( $_POST['_profil_de_groupes_about_us_current_value'] ) ) {
		bp_xprofile_delete_meta( $field->id, 'field', '_profil_de_groupes_about_us' );
	}
}
add_action( 'xprofile_fields_saved_field', 'profil_de_groupes_admin_field_save_preference', 10, 1 );

/**
 * Adds a metabox to set the About us Group's tab display preferences for the field.
 *
 * @since  1.0.0
 *
 * @param  BP_XProfile_Field $field The BuddyPress Xprofile field object.
 */
function profil_de_groupes_admin_field_preferences( BP_XProfile_Field $field ) {
	$disabled = 1 === (int) bp_xprofile_get_meta( $field->id, 'field', '_profil_de_groupes_about_us' );
	?>

	<div class="postbox">
		<h2><?php esc_html_e( 'Préférences d\'affichage', 'profil-de-groupes' ); ?></h2>
		<div class="inside">
			<p class="description">
				<?php esc_html_e( 'Les champs de Groupe s\'affichent dans la page "A propos de nous" du Groupe par défaut. Pour masquer celui-ci, activez la case à cocher.', 'profil-de-groupes' ); ?>
			<p>


			<label for="_profil_de_groupes_about_us">
				<input name="_profil_de_groupes_about_us" class="checkbox" type="checkbox" value="1" <?php checked( $disabled ); ?>/>
				<?php esc_html_e( 'Ne pas afficher', 'profil-de-groupes' ); ?>
			</label>

			<input name="_profil_de_groupes_about_us_current_value" type="hidden" value="<?php echo esc_attr( $disabled ); ?>"/>
		</div>
	</div>

	<?php
}
add_action( 'xprofile_field_after_sidebarbox', 'profil_de_groupes_admin_field_preferences', 10, 1 );
