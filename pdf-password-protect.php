<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF;

/**
 * Plugin Name:       PDF Password Protect [[GrandPlugins]]
 * Description:       Protect PDF files by passwords on your WordPress Website. PDF Password Protect plugin offers an easy way to set passwords and control edit permissions to your PDF files.
 * Author:            GrandPlugins
 * Author URI:        https://grandplugins.com
 * Text Domain:       pdf-password-protect
 * Std Name:          gpls-psrpdf-password-protected-pdf
 * Version:           1.0.3
 * Requires at least: 5.8.0
 * Requires PHP:      7.2.5
 *
 * @package         PDF_Password_Protect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GPLSCore\GPLS_PLUGIN_PSRPDF\Core;
use GPLSCore\GPLS_PLUGIN_PSRPDF\PDF_Password_Protected;
use function GPLSCore\GPLS_PLUGIN_PSRPDF\pages\setup_pages;

if ( ! class_exists( __NAMESPACE__ . '\GPLS_PSRPDF_Class' ) ) :

	/**
	 *  PDF Password Protected Plugin Main Class.
	 */
	class GPLS_PSRPDF_Class {

		/**
		 * Single Instance
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Plugin Info
		 *
		 * @var array
		 */
		private static $plugin_info;

		/**
		 * Debug Mode Status
		 *
		 * @var bool
		 */
		protected $debug;

		/**
		 * Core Object
		 *
		 * @return object
		 */
		private static $core;

		/**
		 * Singular init Function.
		 *
		 * @return Object
		 */
		public static function init() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Core Actions Hook.
		 *
		 * @return void
		 */
		public static function core_actions( $action_type ) {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
			self::$core = new Core( self::$plugin_info );
			if ( 'activated' === $action_type ) {
				self::$core->plugin_activated();
			} elseif ( 'deactivated' === $action_type ) {
				self::$core->plugin_deactivated();
			} elseif ( 'uninstall' === $action_type ) {
				self::$core->plugin_uninstalled();
			}
		}

		/**
		 * Plugin Activated Hook.
		 *
		 * @return void
		 */
		public static function plugin_activated() {
			self::setup_plugin_info();
			self::includes();
			self::disable_duplicate();
			self::core_actions( 'activated' );
			register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_PSRPDF_Class', 'plugin_uninstalled' ) );
		}

		/**
		 * Plugin Deactivated Hook.
		 *
		 * @return void
		 */
		public static function plugin_deactivated() {
			self::setup_plugin_info();
			self::core_actions( 'deactivated' );
		}

		/**
		 * Plugin Installed hook.
		 *
		 * @return void
		 */
		public static function plugin_uninstalled() {
			self::setup_plugin_info();
			self::core_actions( 'uninstall' );
		}
		/**
		 * Constructor
		 */
		public function __construct() {
			self::setup_plugin_info();
			$this->load_languages();
			self::includes();
			$this->load();
		}

		/**
		 * Includes Files
		 *
		 * @return void
		 */
		public static function includes() {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
		}

		/**
		 * Load languages Folder.
		 *
		 * @return void
		 */
		public function load_languages() {
			load_plugin_textdomain( self::$plugin_info['text_domain'], false, self::$plugin_info['path'] . 'languages/' );
		}

		/**
		 * Load Classes.
		 *
		 * @return void
		 */
		public function load() {
			self::$core = new Core( self::$plugin_info );
			setup_pages( self::$core, self::$plugin_info );
			PDF_Password_Protected::init( self::$core, self::$plugin_info );
		}

		/**
		 * Set Plugin Info
		 *
		 * @return array
		 */
		public static function setup_plugin_info() {
			$plugin_data = get_file_data(
				__FILE__,
				array(
					'Version'     => 'Version',
					'Name'        => 'Plugin Name',
					'URI'         => 'Plugin URI',
					'SName'       => 'Std Name',
					'text_domain' => 'Text Domain',
				),
				false
			);

			self::$plugin_info = array(
				'id'              => 1606,
				'basename'        => plugin_basename( __FILE__ ),
				'version'         => $plugin_data['Version'],
				'name'            => $plugin_data['SName'],
				'text_domain'     => $plugin_data['text_domain'],
				'file'            => __FILE__,
				'plugin_url'      => $plugin_data['URI'],
				'public_name'     => $plugin_data['Name'],
				'path'            => trailingslashit( plugin_dir_path( __FILE__ ) ),
				'url'             => trailingslashit( plugin_dir_url( __FILE__ ) ),
				'options_page'    => $plugin_data['SName'],
				'localize_var'    => str_replace( '-', '_', $plugin_data['SName'] ) . '_localize_data',
				'type'            => 'free',
				'classes_prefix'  => 'gpls-psrpdf',
				'prefix'          => 'gpls-psrpdf',
				'classes_general' => 'gpls-general',
				'pro_link'        => 'https://grandplugins.com/product/pdf-password-protect?utm_source=free',
				'duplicate_base'  => 'gpls-psrpdf-password-protected-pdf/gpls-psrpdf-password-protected-pdf.php',
			);
		}

		/**
		 * Disable Duplicate Free/Pro.
		 *
		 * @return void
		 */
		private static function disable_duplicate() {
			if ( ! empty( self::$plugin_info['duplicate_base'] ) && self::is_plugin_active( self::$plugin_info['duplicate_base'] ) ) {
				deactivate_plugins( self::$plugin_info['duplicate_base'] );
			}
		}

		/**
		 * Check is Plugin Active.
		 *
		 * @param string $plugin_basename
		 * @return boolean
		 */
		private static function is_plugin_active( $plugin_basename ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
			return is_plugin_active( $plugin_basename );
		}

	}

	add_action( 'plugins_loaded', array( __NAMESPACE__ . '\GPLS_PSRPDF_Class', 'init' ), 10 );
	register_activation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_PSRPDF_Class', 'plugin_activated' ) );
	register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\GPLS_PSRPDF_Class', 'plugin_deactivated' ) );
endif;
