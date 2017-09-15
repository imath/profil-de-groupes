<?php

require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';

if ( ! defined( 'BP_TESTS_DIR' ) ) {
	define( 'BP_TESTS_DIR', dirname( __FILE__ ) . '/../../../buddypress/tests/phpunit' );
}

function _bootstrap_profil_de_groupes() {

	if ( ! file_exists( BP_TESTS_DIR . '/bootstrap.php' ) )  {
		die( 'The BuddyPress Test suite could not be found' );
	}

	// Make sure BP is installed and loaded first
	require BP_TESTS_DIR . '/includes/loader.php';

	$dummy = new BP_Group_Extension;

	echo "Loading Profile de Groupes...\n";

	// load WP Idea Stream
	require dirname( __FILE__ ) . '/../../profil-de-groupes.php';

	// Run the upgrade method.
	add_action( 'bp_xprofile_setup_globals', '_profil_de_groupes_set', 10 );
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_profil_de_groupes' );

function _profil_de_groupes_set() {
	global $wpdb;

	$table = $wpdb->base_prefix . 'profil_de_groupes_data';

	if ( $wpdb->get_col( "SHOW TABLES LIKE '" . $table  . "'" ) ) {
		// Drop Profil de Groupes table.
		$wpdb->query( "DROP TABLE {$table}" );
	}

	bp_delete_option( '_profil_de_groupes_id' );
	bp_delete_option( '_profil_de_groupes_version' );

	require_once dirname( __FILE__ ) . '/../../inc/admin.php';

	profil_de_groupes_admin_updater();
	profil_de_groupes()->fields_group = (int) bp_get_option( '_profil_de_groupes_id', 0 );

	remove_action( 'bp_xprofile_setup_globals', '_profil_de_groupes_set', 10 );
}

require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';

// Load the BP-specific testing tools
require BP_TESTS_DIR . '/includes/testcase.php';
