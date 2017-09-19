<?php
/**
 * Plugin Name: Profil de Groupes
 * Plugin URI: https://github.com/imath/profil-de-groupes/
 * Description: Un profil pour les groupes BuddyPress.
 * Version: 1.0.1-alpha
 * Requires at least: 4.8
 * Tested up to: 4.9
 * License: GPLv2 or later
 * Author: imath
 * Author URI: https://imathi.eu/
 * Text Domain: profil-de-groupes
 * Domain Path: /languages/
 * GitHub Plugin URI: https://github.com/imath/profil-de-groupes/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Profil_De_Groupes' ) ) :

/**
 * Main Plugin Class
 *
 * @since  1.0.0
 */
final class Profil_De_Groupes {
	/**
	 * Plugin's main instance
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->globals();
		$this->inc();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Setups plugin's globals
	 *
	 * @since 1.0.0
	 */
	private function globals() {
		// Version
		$this->version = '1.0.1-alpha';

		// Domain
		$this->domain = 'profil-de-groupes';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Path and URL
		$this->dir        = plugin_dir_path( $this->file );
		$this->url        = plugin_dir_url ( $this->file );
		$this->lang_dir   = trailingslashit( $this->dir . 'languages' );
		$this->inc_dir    = trailingslashit( $this->dir . 'inc' );
		$this->tpl_dir    = trailingslashit( $this->dir . 'templates' );

		// The Fields group ID for Groups profile fields.
		$this->fields_group = (int) bp_get_option( '_profil_de_groupes_id', 0 );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		if ( ! bp_is_active( 'groups' ) || ! bp_is_active( 'xprofile' ) ) {
			return;
		}

		/**
		 * The BuddyPress Class autoload doesn't load the group extension
		 * when no groups were created yet. We need to make sure to avoid
		 * a fatal to happen by instanciating a dummy Group Extension.
		 */
		if ( ! class_exists( 'BP_Group_Extension' ) ) {
			$dummy_group_extension = new BP_Group_Extension;
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		require $this->inc_dir . 'functions.php';
		require $this->inc_dir . 'templates.php';

		if ( is_admin() ) {
			require $this->inc_dir . 'admin.php';
		}
	}

	/**
	 * Class Autoload function
	 *
	 * @since  1.0.0
	 *
	 * @param  string $class The class name.
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, get_class() ) ) {
			return;
		}

		$path = sprintf( '%1$sclasses/class-%2$s.php',
			$this->inc_dir,
			str_replace( '_', '-', strtolower( $class ) )
		);

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		require $path;
	}
}

endif;

/**
 * Boot the plugin.
 *
 * @since 1.0.0
 */
function profil_de_groupes() {
	return Profil_De_Groupes::start();
}
add_action( 'bp_include', 'profil_de_groupes', 9 );
