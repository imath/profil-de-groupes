<?php
/**
 * Plugin Name: Profil de Groupes
 * Plugin URI: https://github.com/imath/profil-de-groupes/
 * Description: Un profil pour les groupes BuddyPress.
 * Version: 1.0.0-alpha
 * Requires at least: 4.9
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
		$this->version = '1.0.0-alpha';

		// Domain
		$this->domain = 'profil-de-groupes';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Path and URL
		$this->dir        = plugin_dir_path( $this->file );
		$this->url        = plugin_dir_url ( $this->file );
		$this->js_url     = trailingslashit( $this->url . 'js' );
		$this->assets_url = trailingslashit( $this->url . 'assets' );
		$this->inc_dir    = trailingslashit( $this->dir . 'inc' );

		// @todo Create the install routine.
		$this->fields_group = (int) bp_get_option( '_profil_de_groupes_id', 2 );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		spl_autoload_register( array( $this, 'autoload' ) );

		require $this->inc_dir . 'functions.php';

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
