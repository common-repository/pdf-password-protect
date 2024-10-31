<?php defined( 'ABSPATH' ) || exit();
use GPLSCore\GPLS_PLUGIN_PSRPDF\Utils\Helpers;

use Dompdf\Dompdf;
use GPLSCore\GPLS_PLUGIN_PSRPDF\pages\AdminPage;
use GPLSCore\GPLS_PLUGIN_PSRPDF\PDF_Password_Protected;


use Mpdf\Mpdf;

/**
 * PDF Password Settings Page.
 */
class PDF_Password_Settings extends AdminPage {

	use Helpers;

	/**
	 * Symfony Process error message.
	 *
	 * @var string
	 */
	public static $process_error_msg = 'The Process class relies on proc_open, which is not available on your PHP installation.';

	/**
	 * Singular Instance.
	 *
	 * @var PDF_Password_Settings
	 */
	protected static $instance = null;

	/**
	 * Auto Password Rules Settings Key.
	 *
	 * @var string
	 */
	private $auto_password_rules_key;

	/**
	 * Page Path for public.
	 *
	 * @var string
	 */
	protected static $_page_path;

	/**
	 * Constructor.
	 *
	 * @param object    $core
	 * @param array     $plugin_info
	 * @param AdminPage $parent_page
	 * @param array     $other_pages
	 */
	public function __construct( $core, $plugin_info, $parent_page = null, $other_pages = array() ) {
		self::$core        = $core;
		self::$plugin_info = $plugin_info;
		$this->parent_page = $parent_page;
		$this->other_pages = $other_pages;

		parent::__construct();
		$this->hooks();
	}

	/**
	 * Init Page.
	 *
	 * @return void
	 */
	protected function init() {
		$this->page_title              = esc_html__( 'PDF Password Protected Settings', 'pdf-password-protect' );
		$this->menu_title              = esc_html__( 'PDF Password Protected', 'pdf-password-protect' );
		$this->cap                     = 'manage_options';
		$this->menu_slug               = self::$plugin_info['name'] . '-pdf-password-protected-page';
		$this->parent_slug             = 'upload.php';
		$this->templates_folder        = 'pdf-password-settings';
		$this->position                = 3;
		$this->auto_password_rules_key = self::$plugin_info['name'] . '-auto-password-rules';
		$this->tabs                    = array(
			'status'         => array(
				'title'    => esc_html__( 'Status', 'pdf-password-protect' ),
				'template' => 'status-template.php',
				'default'  => true,
			),
			'restricted-pdf-uploader' => array(
				'title'    => esc_html__( 'Restricted PDF Uploader', 'gpls-psrpdf-password-protected-pdf' ) . self::$core->new_keyword() . '&nbsp;' . self::$core->new_keyword( 'Pro' ),
				'template' => 'restricted-pdf-uploader-template.php',
			),
			'auto-passwords' => array(
				'title'    => esc_html__( 'Auto Password', 'pdf-password-protect' ) . self::$core->new_keyword( 'Pro' ),
				'template' => 'auto-passwords-template.php',
			),
			'bulk-passwords' => array(
				'title'    => esc_html__( 'Bulk Password', 'pdf-password-protect' ) . self::$core->new_keyword( 'Pro' ),
				'template' => 'bulk-passwords-template.php',
			),
		);
	}

	/**
	 * Page Hooks.
	 *
	 * @return void
	 */
	protected function hooks() {
		add_action( self::$plugin_info['name'] . '-' . $this->menu_slug . '-template-footer', array( $this, 'page_footer' ) );

		// Password Protected PDFs Meta.
		add_action( 'woocommerce_product_options_downloads', array( $this, 'downloadable_password_protected_pdf' ), PHP_INT_MAX );
	}

	/**
	 * Downloadable Password Protected PDf.
	 *
	 * @return void
	 */
	public function downloadable_password_protected_pdf() {
		global $post;
		?>
		<div style="background-color:#EEE;opacity:0.5;" class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-password-protected-downloadable-pdfs-wrapper' ); ?>">
			<h3><?php esc_html_e( 'Password Protected Downloadable PDFs [GrandPlugins]', 'pdf-password-protect' ); ?> <?php self::$core->pro_btn( '', 'Premium', '', 'display: inline-block;font-weight: 400;line-height: 1.5;color: #212529;text-align: center;text-decoration: none;vertical-align: middle;cursor: pointer;-webkit-user-select: none;-moz-user-select: none;user-select: none;background-color: transparent;border: 1px solid transparent;padding: 0.375rem 0.75rem;font-size: 1rem;border-radius: 0.25rem;transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;' ); ?></h3>
			<table class="widefat <?php echo esc_attr( self::$plugin_info['prefix'] . '-password-protected-downloadable-pdfs' ); ?>">
				<thead>
					<tr>
						<th class="sort">&nbsp;</th>
						<th><?php esc_html_e( 'Name', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the name of the download shown to the customer.', 'woocommerce' ) ); ?></th>
						<th colspan="2"><?php esc_html_e( 'File URL', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the URL or absolute path to the file which customers will get access to. URLs entered here should already be encoded.', 'woocommerce' ) ); ?></th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<tr class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-downloadable-pdf-row-placeholder hidden' ); ?>">
						<td class="sort"></td>
						<td class="file_name">
							<input type="text" class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-pdf-name' ); ?>" placeholder="<?php esc_attr_e( 'File name', 'woocommerce' ); ?>" name="<?php echo esc_attr( self::$plugin_info['prefix'] . '-pdf-file-names[]' ); ?>" value="" />
							<input type="hidden" name="<?php echo esc_attr( self::$plugin_info['prefix'] . '-pdf-file-hashes[]' ); ?>" value="" />
						</td>
						<td class="file_url">
							<input readonly type="text" class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-pdf-url' ); ?>" placeholder="<?php esc_attr_e( 'http://', 'woocommerce' ); ?>" name="<?php echo esc_attr( self::$plugin_info['prefix'] . '-pdf-file-urls[]' ); ?>" value="" />
						</td>
						<td width="1%"><a href="#" class="button <?php echo esc_attr( self::$plugin_info['prefix'] . '-choose' ); ?>" data-choose="<?php esc_attr_e( 'Choose file', 'woocommerce' ); ?>" data-update="<?php esc_attr_e( 'Insert file URL', 'woocommerce' ); ?>"><?php echo esc_html__( 'Choose file', 'woocommerce' ); ?></a></td>
						<td class="file-delete" width="1%"><a href="#" class="<?php echo esc_attr( self::$plugin_info['prefix'] . '-delete' ); ?>"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a></td>
					</tr>
					<tfoot>
						<tr>
							<th colspan="2">
								<a disabled href="#" class="button <?php echo esc_attr( self::$plugin_info['prefix'] . '-insert' ); ?>"><?php esc_html_e( 'Add PDF', 'gpls-psrpdf-password-protected-pdf' ); ?></a>
							</th>
						</tr>
					</tfoot>
					<h5><?php esc_html_e( 'Make sure the PDF file is not already protected with a password. A unique password will be generated for each customer dynamically at download. The password will appear below the Download button in the Downloads section.', 'gpls-psrpdf-password-protected-pdf' ); ?></h5>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Page Assets.
	 *
	 * @return void
	 */
	protected function set_assets() {
		$this->assets = array(
			array(
				'type'   => 'js',
				'handle' => 'plupload-handlers',
			),
			array(
				'type'   => 'css',
				'handle' => self::$plugin_info['name'] . '-generals-css',
				'url'    => self::$plugin_info['url'] . 'assets/dist/css/admin/generals.min.css',
			),
		);
	}

	/**
	 * get Cpts.
	 *
	 * @return array
	 */
	public function get_cpts() {
		$pypass_cpts = array( 'wp_template', 'acf-field-group', 'attachment', 'custom_css', 'wp_template_part', 'wp_block', 'nav_menu_item', 'acf-field', 'nav_menu_item', 'custom_css' );
		return array_filter(
			get_post_types(
				array(
					'can_export' => true,
				)
			),
			function( $cpt_slug ) use ( $pypass_cpts ) {
				return ! in_array( $cpt_slug, $pypass_cpts );
			}
		);
	}

	/**
	 * Setting Page Footer.
	 *
	 * @return void
	 */
	public function page_footer() {
		if ( $this->is_tab_active( 'status' ) ) {
			self::$core->review_notice( 'https://wordpress.org/support/plugin/pdf-password-protect/reviews/#new-post' );
		}
	}

}
