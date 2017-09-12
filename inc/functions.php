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

function profil_de_groupes_get_fields_group( $groups = array() ) {
	if ( is_array( $groups ) ) {
		return wp_list_filter( $groups, array( 'id' => 2 ) );
	}

	return $groups;
}

function profil_de_groupes_admin_js() {
	if ( 'groups_page_bp-profile-setup-groupes' !== get_current_screen()->id ) {
		return;
	}

	$xprofile_admin_url = add_query_arg( 'page', 'bp-profile-setup', bp_get_admin_url( 'users.php' ) );
	$gprofile_admin_url = add_query_arg( 'page', 'bp-profile-setup-groupes', bp_get_admin_url( 'admin.php' ) );

	wp_add_inline_script( 'xprofile-admin-js', sprintf( '( function($) {
		$( document ).ready( function() {
			$( \'#add_group\' ).remove();
			$( \'#field-group-tabs\' ).remove();

			$.each( $( \'.tab-toolbar-left\' ).children(), function( i, a ) {
				if ( ! $( a ).hasClass( \'button-primary\' ) ) {
					$( a ).remove();
				}
			} );

			$.each( $( \'#profile-field-form a\' ), function( i, a ) {
				var urlReplace = \'%1$s\', urlBy = \'%2$s\';
				
				$( a ).prop( \'href\', $( a ).prop( \'href\' ).replace( urlReplace, urlBy ) );
			} );
		} );
	} )( jQuery )', esc_url_raw( $xprofile_admin_url ), esc_url_raw( $gprofile_admin_url ) ) );
}
add_action( 'bp_admin_enqueue_scripts', 'profil_de_groupes_admin_js' );


function profil_de_groupes_admin() {
	add_filter( 'bp_xprofile_get_groups', 'profil_de_groupes_get_fields_group', 10, 1 );
	
	xprofile_admin();

	remove_filter( 'bp_xprofile_get_groups', 'profil_de_groupes_get_fields_group', 10, 1 );
}