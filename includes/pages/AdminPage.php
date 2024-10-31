<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF\pages;

use GPLSCore\GPLS_PLUGIN_PSRPDF\Utils\Helpers;
use GPLSCore\GPLS_PLUGIN_PSRPDF\Utils\NoticeUtils;
use GPLSCore\GPLS_PLUGIN_PSRPDF\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Page Class
 */
abstract class AdminPage {

	use Helpers;
	use NoticeUtils;

	/**
	 * Core Objet
	 *
	 * @var object
	 */
	protected static $core;
	/**
	 * Plugin Info
	 *
	 * @var object
	 */
	protected static $plugin_info;

	/**
	 * Parent Slug.
	 *
	 * @var string|nul
	 */
	protected $parent_slug = null;

	/**
	 * Page Title.
	 *
	 * @var string
	 */
	public $page_title;

	/**
	 * Menu Title.
	 *
	 * @var string
	 */
	protected $menu_title;

	/**
	 * Page Capability.
	 *
	 * @var string
	 */
	protected $cap;

	/**
	 * Menu Slug
	 *
	 * @var string
	 */
	protected $menu_slug;

	/**
	 * Icon URL.
	 *
	 * @var string|null
	 */
	protected $icon_url = null;

	/**
	 * Page Position.
	 *
	 * @var integer
	 */
	protected $position = 10;

	/**
	 * Page Template Name.
	 *
	 * @var string
	 */
	protected $template_name;

	/**
	 * Parent Page Object.
	 *
	 * @var AdminPage
	 */
	protected $parent_page = null;

	/**
	 * Page nonce.
	 *
	 * @var string
	 */
	protected $page_nonce;

	/**
	 * Page Tabs.
	 *
	 * array(
	 *  'tab-name' => array(
	 *      'title'   => 'Tab Title',
	 *      'default' => true,
	 *   ),
	 *   ...
	 * );
	 *
	 * @var array
	 */
	protected $tabs = array();

	/**
	 * Admin Menu Pages Slugs.
	 *
	 *  Default: bottom of menu structure
	 *      2 – Dashboard
	 *      4 – Separator
	 *      5 – Posts
	 *      10 – Media
	 *      15 – Links
	 *      20 – Pages
	 *      25 – Comments
	 *      59 – Separator
	 *      60 – Appearance
	 *      65 – Plugins
	 *      70 – Users
	 *      75 – Tools
	 *      80 – Settings
	 *      99 – Separator

	 *  For the Network Admin menu, the values are different:
	 *      2 – Dashboard
	 *      4 – Separator
	 *      5 – Sites
	 *      10 – Users
	 *      15 – Themes
	 *      20 – Plugins
	 *      25 – Settings
	 *      30 – Updates
	 *      99 – Separator
	 *
	 * @var array
	 */
	protected $parent_pages_slugs = array(
		'index.php',                                // Dashboard.
		'edit.php',                                 // Posts.
		'upload.php',                               // Media.
		'edit.php?post_type=page',                  // Pages.
		'edit-comments.php',                        // Comments.
		'edit.php?post_type=custom_post_type_name', // Custom Post Type.
		'admin.php?page=wc-admin',                  // WooCommerce.
		'edit.php?post_type=product',               // Products.
		'themes.php',                               // Appearance.
		'plugins.php',                              // Plugins.
		'users.php',                                // Users.
		'tools.php',                                // Tools.
		'options-general.php',                      // Settings.
		'settings.php',                             // Network Settings.
	);

	/**
	 * Assets Files to include.
	 *
	 * @return void
	 */
	protected $assets = array();

	/**
	 * Core Assets Files to include.
	 *
	 * @return void
	 */
	protected $core_assets = array();

	/**
	 * Enable - Disable Reload Submit.
	 *
	 * @var boolean
	 */
	protected $allow_submit = true;

	/**
	 * Enable - Disable Ajax Submit.
	 *
	 * @var boolean
	 */
	protected $allow_ajax_submit = true;

	/**
	 * Settings
	 *
	 * @var Settings
	 */
	protected $settings = null;

	/**
	 * Page PATH.
	 *
	 * @var string
	 */
	protected $page_path;

	/**
	 * Templates Folder name
	 *
	 * @var string
	 */
	protected $templates_folder;

	/**
	 * Page AJAX Action link.
	 *
	 * @var string
	 */
	protected $page_ajax_action;

	/**
	 * Undocumented function
	 *
	 * @param object    $core
	 * @param array     $plugin_info
	 * @param AdminPage $parent_page
	 */
	public static function get_instance( $core, $plugin_info, $parent_page = null ) {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static( $core, $plugin_info, $parent_page );
		}
		return static::$instance;
	}

	/**
	 * Admin Page Constructor.
	 */
	protected function __construct() {
		$this->init();
		$this->setup();
		$this->set_assets();
		$this->main_hooks();
	}

	/**
	 * Setup Parent.
	 *
	 * @return void
	 */
	public function setup() {
		// Page Nonce.
		$this->page_nonce = $this->menu_slug . '-nonce';

		// Page Slug.
		if ( ! $this->page_path ) {
			$this->page_path = admin_url( ( $this->parent_slug ? $this->parent_slug : 'admin.php' ) . '?page=' . $this->menu_slug );
		}

		// Page AJAX Action.
		$this->page_ajax_action = $this->menu_slug . '-ajax-action';

		// Main Assets
		$this->core_assets = array(
			array(
				'type'   => 'js',
				'handle' => 'jquery',
			),
			array(
				'type'   => 'js',
				'handle' => 'wp-hook',
			),
			array(
				'type'   => 'css',
				'handle' => self::$plugin_info['name'] . '-bootstrap-css',
				'url'    => self::$core->core_assets_lib( 'bootstrap', 'css' ),
			),
			array(
				'type'   => 'js',
				'handle' => self::$plugin_info['name'] . '-bootstrap-js',
				'url'    => self::$core->core_assets_lib( 'bootstrap.bundle', 'js' ),
			),
		);

		static::$_page_path = $this->page_path;
	}

	/**
	 * Add Page.
	 *
	 * @return void
	 */
	public function add_page() {
		if ( ! is_null( $this->parent_slug ) ) {
			add_submenu_page(
				$this->parent_slug,
				$this->page_title,
				$this->menu_title,
				$this->cap,
				$this->menu_slug,
				array( $this, 'page_output_function' ),
				$this->position
			);
		} else {
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				$this->cap,
				$this->menu_slug,
				array( $this, 'page_output_function' ),
				$this->icon_url,
				$this->position
			);
		}
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	protected function main_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ), 100 );
		add_action( 'wp_loaded', array( $this, 'page_form_submit' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ), 100, 1 );

		if ( $this->allow_ajax_submit ) {
			add_action( 'wp_ajax_' . $this->page_ajax_action, array( $this, 'page_ajax_submit' ) );
		}

		// Connected Settings Submit Save.
		if ( $this->settings ) {
			add_action( $this->menu_slug . '-form-submit', array( $this->settings, 'save_settings' ) );
		}

		// Page nonce in settings.
		if ( $this->settings ) {
			add_action( self::$plugin_info['name'] . '-' . $this->settings->get_id() . '-form-close-submit-fields', array( $this, 'settings_page_nonce' ) );
		}
	}

	/**
	 * Page Nonce in settings form.
	 *
	 * @return void
	 */
	public function settings_page_nonce() {
		?>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( $this->page_nonce ) ); ?>">
		<?php
	}

	/**
	 * Get Default Tab.
	 *
	 * @return string|false
	 */
	private function get_default_tab() {
		foreach ( $this->tabs as $tab_name => $tab_arr ) {
			if ( ! empty( $tab_arr['default'] ) ) {
				return $tab_name;
			}
		}
		return false;
	}

	/**
	 * Page Output HTML function.
	 *
	 * @return void
	 */
	public function page_output_function() {
		$args = array(
			'core'          => self::$core,
			'plugin_info'   => self::$plugin_info,
			'template_page' => $this,
		);

		$tab = ! empty( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		$tab = ! empty( $tab ) ? $tab : $this->get_default_tab();

		if ( empty( $tab ) || empty( $this->tabs[ $tab ] ) ) {
			return;
		}

		$tab = $this->tabs[ $tab ];
		?>
		<!-- Template Header -->
		<div class="wrap <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-page-template-wrapper gpls-general-page-template-wrapper' ); ?> mt-0 bg-light p-3 mt-5 min-vh-100">

			<?php $this->output_page_tabs_nav( true ); ?>

			<?php do_action( self::$plugin_info['name'] . '-general-top-notices' ); ?>

			<!-- notices -->
			<div class="notices">
				<?php $this->show_messages(); ?>
			</div>

				<!-- Template Title -->
				<h1 class="wp-heading-inline mb-4 shadow-sm p-2 bg-white"><?php printf( esc_html( '%s', 'pdf-password-protect' ), $tab['title'] ); ?></h1>

				<?php

				do_action( self::$plugin_info['name'] . '-' . $this->menu_slug . '-template-header', $this );

				// Template Form Open.
				if ( ! empty($tab['use_form'] ) ) {
					$this->form_open();
				}

				// Template additional args.
				if ( ! empty( $tab['args'] ) ) {
					$args = array_merge( $args, $tab['args'] );
				}

				// Tab's Template.
				load_template(
					self::$plugin_info['path'] . 'templates/pages/' . $this->templates_folder . '/' . $tab['template'],
					false,
					$args
				);

				// Template Form Close.
				if ( ! empty( $tab['use_form'] ) ) {
					$this->form_close();
				}

			?>

			<!-- Template Footer -->
			<?php do_action( self::$plugin_info['name'] . '-' . $this->menu_slug . '-template-footer', $this ); ?>

		</div>
		<?php
	}

	/**
	 * Get Page Menu Slug.
	 *
	 * @return string
	 */
	public function get_menu_slug() {
		return $this->menu_slug;
	}

	/**
	 * Get Current Page Path.
	 *
	 * @return string
	 */
	public function get_page_path() {
		return $this->page_path;
	}

	/**
	 * Get Page Path.
	 *
	 * @return void
	 */
	public static function page_path() {
		return static::$_page_path;
	}

	/**
	 * Get Page Templates Folder
	 *
	 * @return string
	 */
	public function get_templates_folder() {
		return $this->templates_folder;
	}

	/**
	 * Get Page nonce.
	 *
	 * @return string
	 */
	public function get_page_nonce() {
		return $this->page_nonce;
	}

	/**
	 * Check if its current Page.
	 *
	 * @return boolean
	 */
	public function is_current_page( $custom_slug = null ) {
		$full_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		return ( ! empty( $full_url ) && ( 0 === strpos( ( is_null( $custom_slug ) ? $this->page_path : $custom_slug ), esc_url_raw( $full_url ) ) ) );
	}

	/**
	 * init function.
	 *
	 * @return void
	 */
	abstract protected function init();

	/**
	 * Hooks function.
	 *
	 * @return void
	 */
	abstract protected function hooks();

	/**
	 * Set Page Assets files.
	 *
	 * @return void
	 */
	abstract protected function set_assets();

	/**
	 * Page assets.
	 *
	 * @return void
	 */
	public function assets( $suffix ) {
		if ( $this->is_current_page() || ( $suffix === get_plugin_page_hookname( $this->menu_slug, $this->parent_slug ) ) ) {
			$assets = array_merge( $this->assets, $this->core_assets );
			foreach ( $assets as $asset_file ) {
				// Conditional Tab.
				if ( ! empty( $asset_file['conditional'] ) ) {
					foreach ( $asset_file['conditional'] as $key => $value ) {
						if ( empty( $_GET[ $key ] ) || $value !== sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ) {
							continue;
						}
					}
				}

				// CSS.
				if ( 'css' === $asset_file['type'] ) {
					// Registered or other Asset.
					if ( empty( $asset_file['url'] ) ) {
						if ( ! wp_script_is( $asset_file['handle'] ) ) {
							wp_enqueue_style( $asset_file['handle'] );
						}
					} else {
						wp_enqueue_style( $asset_file['handle'], $asset_file['url'], ! empty( $asset_file['dependency'] ) ? $asset_file['dependency'] : array(), self::$plugin_info['version'], ! empty( $asset_file['media'] ) ? $asset_file['media'] : 'all' );
					}
				}

				// JS.
				if ( 'js' === $asset_file['type'] ) {
					if ( empty( $asset_file['url'] ) ) {
						if ( ! wp_script_is( $asset_file['handle'] ) ) {
							wp_enqueue_script( $asset_file['handle'] );
						}
					} else {
						wp_enqueue_script( $asset_file['handle'], $asset_file['url'], ! empty( $asset_file['dependency'] ) ? $asset_file['dependency'] : array(), self::$plugin_info['version'], isset( $asset_file['in_footer'] ) ? $asset_file['in_footer'] : true );
					}
					if ( ! empty( $asset_file['localized'] ) ) {
						wp_localize_script( $asset_file['handle'], $asset_file['localized']['name'], $asset_file['localized']['data'] );
					}
				}
			}

			do_action( self::$plugin_info['name'] . '-admin-page-assets', $this );

		}
	}

	/**
	 * Form Open
	 *
	 * @return void
	 */
	public function form_open() {
		do_action( self::$plugin_info['name'] . '-' . $this->menu_slug . '-before-form-open', $this );
		?>
		<form method="post" id="mainform" action enctype="multipart/form-data">
		<?php
	}

	/**
	 * Form Fields for Submit.
	 */
	public function form_close() {
		?>
			<p class="submit">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( $this->page_nonce ) ); ?>">
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url_raw( $this->page_path ); ?>" >
				<button name="save" class="button-primary" type="submit" value="Save Changes"><?php esc_html_e( 'Save changes', '' ); ?></button>
				<?php do_action( self::$plugin_info['name'] . '-' . $this->menu_slug . '-form-close-submit-fields' ); ?>
			</p>
		</form>
		<?php
		do_action( self::$plugin_info['name'] . '-' . $this->menu_slug . '-after-form-close', $this );
	}

	/**
	 * Form Submit on the page.
	 *
	 * @return void
	 */
	public function page_form_submit() {
		if ( $this->allow_submit && is_admin() && ! empty( $_POST['save'] ) && $this->page_path === wp_get_raw_referer() && ! empty( $_GET['page'] ) && $this->menu_slug === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			// Nonce Check.
			check_admin_referer( $this->page_nonce, 'nonce' );

			// Cap Check.
			if ( ! current_user_can( $this->cap ) ) {
				wp_die(
					'<h1>' . esc_html__( 'You need a higher level of permission.' ) . '</h1>',
					403
				);
			}

			do_action( $this->menu_slug . '-form-submit' );
		}

	}

	/**
	 * Ajax Submit on the page.
	 *
	 * @return void
	 */
	public function page_ajax_submit() {
		if ( wp_doing_ajax() && is_admin() && ! empty( $_POST['context'] ) ) {
			// Nonce Check.
			check_ajax_referer( $this->page_nonce, 'nonce' );

			// Cap Check.
			if ( ! current_user_can( $this->cap ) ) {
				wp_die(
					'<h1>' . esc_html__( 'You need a higher level of permission.' ) . '</h1>',
					403
				);
			}

			$context = sanitize_text_field( wp_unslash( $_POST['context'] ) );

			do_action( $this->menu_slug . '-ajax-submit-' . $context );

		}

		wp_die( -1, 403 );
	}

	/**
	 * Output Page Tabs navbar.
	 *
	 * @return void|string
	 */
	public function output_page_tabs_nav( $echo = false ) {
		if ( empty( $this->tabs ) ) {
			return;
		}
		if ( ! $echo ) {
			ob_start();
		}
		?>
		<div class="<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-page-template-tabs-nav gpls-general-page-template-tabs-nav' ); ?> mt-0 bg-light p-3 my-3 border-bottom shadow-sm">
			<?php $this->toast(); ?>
			<?php self::loader_html(); ?>
			<ul class="list-group list-group-horizontal">
				<?php foreach ( $this->tabs as $tab_name => $tab ) : ?>
				<li class="list-group-item btn p-0 <?php echo esc_attr( $this->is_tab_active( $tab_name, $tab ) ? 'active' : '' ); ?>">
					<a
						class="list-group-item-link text-decoration-none fw-bold d-block px-3 py-2"
						href="
						<?php
						echo esc_url_raw(
							add_query_arg(
								array(
									'tab' => $tab_name,
								),
								$this->page_path
							)
						);
						?>
						"
					>
					<?php echo wp_kses_post( $tab['title'] ); ?>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		if ( ! $echo ) {
			return ob_get_clean();
		}
	}

	/**
	 * Check if tab is active.
	 *
	 * @param string $tab_name
	 * @param array  $tab_arr
	 * @return boolean
	 */
	public function is_tab_active( $tab_name, $tab_arr = array() ) {
		if ( ! empty( $tab_arr ) && ! empty( $tab_arr['default'] ) && empty( $_GET['tab'] ) ) {
			return true;
		}

		if ( ! empty( $_GET['tab'] ) && $tab_name === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
			return true;
		}

		return false;
	}

}
