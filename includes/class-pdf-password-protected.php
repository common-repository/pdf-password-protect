<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF;

use GPLSCore\GPLS_PLUGIN_PSRPDF\modules\SelectImages\Queries;
use GPLSCore\GPLS_PLUGIN_PSRPDF\Utils\Helpers;
use GPLSCore\GPLS_PLUGIN_PSRPDF\Utils\NoticeUtils;
use Mpdf\Mpdf;
use setasign\FpdiProtection\FpdiProtection;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use GPLSCore\GPLS_PLUGIN_PSRPDF\Password\PDF_Password;

/**
 * PDF Password Protected.
 */
class PDF_Password_Protected {

	use Helpers;
	use NoticeUtils;


	/**
	 * Singular Instance.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Core Object.
	 *
	 * @var object
	 */
	private static $core;

	/**
	 * PDF password meta key.
	 *
	 * @var string
	 */
	private static $pdf_pass_meta_key;

	/**
	 * Plugin Info Array.
	 *
	 * @var array
	 */
	private static $plugin_info;

	/**
	 * Pdf Lib.
	 *
	 * @var PDF_Password
	 */
	private static $pdf_lib = null;

	/**
	 * Permissions
	 *
	 * @var array
	 */
	private static $permissions = array();

	/**
	 * Singular Instance initialize.
	 *
	 * @param object $core
	 * @param array  $plugin_info
	 * @return object
	 */
	public static function init( $core, $plugin_info ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $core, $plugin_info );
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param object $core
	 * @param array  $pluing_info
	 */
	public function __construct( $core, $pluing_info ) {
		self::$core        = $core;
		self::$plugin_info = $pluing_info;

		$this->setup();
		$this->hooks();
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		self::$pdf_pass_meta_key = self::$plugin_info['name'] . '-pdf-password';
		self::$permissions       = array(
			'copy'      => array(
				'label' => esc_html__( 'Copy' ),
			),
			'print'     => array(
				'label' => esc_html__( 'Print' ),
			),
			'comment'   => array(
				'label' => esc_html__( 'Comment' ),
			),
			'fill-form' => array(
				'label' => esc_html__( 'Comment - Fill forms' ),
			),
		);
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_pdf_metabox' ), 100 );
		add_action( 'do_meta_boxes', array( $this, 'filter_pdf_metaboxes' ), 1000 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_ajax_' . self::$plugin_info['name'] . '-assign-pdf-password', array( $this, 'ajax_assign_pdf_password' ) );

		// Main settings link.
		add_filter( 'plugin_action_links_' . self::$plugin_info['basename'], array( $this, 'main_settings_page_link' ), 1000 );
	}

	/**
	 * Get Permissions.
	 *
	 * @return array
	 */
	public static function get_permissions() {
		return self::$permissions;
	}

	/**
	 * Settings Link.
	 *
	 * @param array $links
	 * @return array
	 */
	public function main_settings_page_link( $links ) {
		$links[] = '<a href="' . esc_url_raw( admin_url( 'upload.php?page=' . self::$plugin_info['name'] . '-pdf-password-protected-page' ) ) . '" >' . esc_html__( 'Settings' ) . '</a>';
		$links[] = '<a target="_blank" href="' . esc_url_raw( self::$plugin_info['pro_link'] ) . '" ><b>' . esc_html__( 'Premium' ) . '</b></a>';
		return $links;
	}

	/**
	 * Admin Assets.
	 *
	 * @return void
	 */
	public function admin_assets() {
		$current_screen = get_current_screen();
		if ( is_object( $current_screen ) && ( 'post' === $current_screen->base ) && ( 'attachment' === $current_screen->post_type ) ) {
			wp_enqueue_style( self::$plugin_info['name'] . '-bootstrap-css', self::$core->core_assets_lib( 'bootstrap', 'css' ), array(), self::$plugin_info['version'], 'all' );
			wp_enqueue_style( self::$plugin_info['name'] . '-admin-css', self::$plugin_info['url'] . 'assets/dist/css/admin/generals.min.css', array(), self::$plugin_info['version'], 'all' );
			wp_enqueue_script( self::$plugin_info['name'] . '-bootstrap-js', self::$core->core_assets_lib( 'bootstrap.bundle', 'js' ), array( 'jquery' ), self::$plugin_info['version'], true );
			wp_enqueue_script( self::$plugin_info['name'] . '-admin-js', self::$plugin_info['url'] . 'assets/dist/js/admin/edit-pdf-page-actions.min.js', array( 'jquery' ), self::$plugin_info['version'], true );
			wp_localize_script(
				self::$plugin_info['name'] . '-admin-js',
				str_replace( '-', '_', self::$plugin_info['name'] . '-localize-vars' ),
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'nonce'            => wp_create_nonce( self::$plugin_info['name'] . '-nonce' ),
					'prefix'           => self::$plugin_info['classes_prefix'],
					'general_prefix'   => self::$plugin_info['classes_general'],
					'name'             => self::$plugin_info['name'],
					'assignPassAction' => self::$plugin_info['name'] . '-assign-pdf-password',
					'removePassAction' => self::$plugin_info['name'] . '-remove-pdf-password',
				)
			);
		}
	}

	/**
	 * Filter Metaboxes for pdf attachments only.
	 *
	 * @return void
	 */
	public function filter_pdf_metaboxes() {
		global $post;
		if ( $post && ! is_wp_error( $post ) && ( 'application/pdf' !== $post->post_mime_type ) ) {
			remove_meta_box( self::$plugin_info['name'] . '-password-protected-pdf-options-metabox', 'attachment', 'side' );
		}
	}

	/**
	 * Register Password Procteded PDF Metabox.
	 *
	 * @return void
	 */
	public function register_pdf_metabox() {
		add_meta_box(
			self::$plugin_info['name'] . '-password-protected-pdf-options-metabox',
			esc_html__( 'PDF password options', 'pdf-password-protect' ),
			array( $this, 'pdf_password_metabox' ),
			'attachment',
			'side',
			'high'
		);
	}

	/**
	 * PDF Password Metabox.
	 *
	 * @param \WP_Post $post
	 * @return void
	 */
	public function pdf_password_metabox( $post ) {
		self::set_pdf_lib();

		if ( is_wp_error( self::$pdf_lib ) ) {
			self::error_message( self::$pdf_lib->get_error_message(), false );
		}

		self::$pdf_lib->pdf_password_metabox( $post );
	}

	/**
	 * Get PDF Password Metabox.
	 *
	 * @param int|\WP_Post $post
	 * @return string
	 */
	private function get_pdf_password_metabox( $post ) {
		$post = is_a( $post, '\WP_Post' ) ? $post : get_post( $post );
		if ( ! $post || is_wp_error( $post ) ) {
			return;
		}
		ob_start();
		$this->pdf_password_metabox( $post );
		return ob_get_clean();
	}

	/**
	 * AJAX assign PDF password.
	 *
	 * @return void
	 */
	public function ajax_assign_pdf_password() {
		if ( ! empty( $_POST['nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['nonce'] ), self::$plugin_info['name'] . '-nonce' ) ) {

			if ( empty( $_POST['id'] ) || ( empty( $_POST['userPass'] ) && empty( $_POST['ownerPass'] ) ) ) {
				$this->invalid_submitted_data_response();
			}

			$user_pass  = ! empty( $_POST['userPass'] ) ? trim( wp_unslash( $_POST['userPass'] ) ) : '';
			$owner_pass = ! empty( $_POST['ownerPass'] ) ? trim( wp_unslash( $_POST['ownerPass'] ) ) : '';
			$perms      = ! empty( $_POST['perms'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['perms'] ) ) : array();
			$id         = absint( sanitize_text_field( wp_unslash( $_POST['id'] ) ) );

			$dest = self::set_pdf_password( $id, $user_pass, $owner_pass, $perms, false );
			$args = array(
				'id'       => $id,
				'pdf_path' => PDF_Password::get_pdf_file_by_id( $id ),
			);

			if ( is_wp_error( $dest ) ) {
				$args['notice_fixed'] = true;
			} else {
				$args['metabox'] = $this->get_pdf_password_metabox( $id );
			}

			$this->ajax_response(
				is_wp_error( $dest ) ? $dest->get_error_message() : esc_html__( 'PDF is password protected successfully', 'pdf-password-protect' ),
				is_wp_error( $dest ) ? 'error' : 'success',
				'assign-password',
				true,
				$args
			);
		}

		$this->expired_response();
	}

	/**
	 * Get PDF Lib.
	 *
	 * @return void
	 */
	private static function set_pdf_lib() {
		if ( is_null( self::$pdf_lib ) ) {
			self::$pdf_lib = PDF_Password::get_pdf_lib( self::$core, self::$plugin_info );
		}
	}

	/**
	 * Set Password to PDF File.
	 *
	 * @param int|string $pdf_attachment_id
	 * @param string     $user_password
	 * @param string     $owner_password
	 * @param array      $permissions
	 * @param string     $dest
	 * @return string|\WP_Error
	 */
	public static function set_pdf_password( $pdf_attachment_id, $user_password, $owner_password = '', $permissions = array(), $dest = false ) {
		self::set_pdf_lib();
		if ( is_wp_error( self::$pdf_lib ) ) {
			return self::$pdf_lib;
		}

		// 2) Get PDF Path.
		$pdf_path = is_int( $pdf_attachment_id ) ? PDF_Password::get_pdf_file_by_id( $pdf_attachment_id ) : $pdf_attachment_id;
		$dest     = false === $dest ? $pdf_path : $dest;

		try {
			// 3) Set PDF Password.
			$result = self::$pdf_lib->set_password( $pdf_path, $user_password, $dest, $owner_password, $permissions );

			// 4) Update password meta.
			if ( ! is_wp_error( $result ) && is_int( $pdf_attachment_id ) ) {
				self::update_attachment_pdf_pass( $pdf_attachment_id, $user_password, $owner_password, $permissions );
			}

			return $result;

		} catch ( \Exception $e ) {
			return new \WP_Error(
				self::$plugin_info['name'] . '-failed-set-pdf-password',
				$e->getMessage()
			);
		}
	}

	/**
	 * Set PDF Password automatically on upload.
	 *
	 * @param string  $pdf_path
	 * @param string  $password
	 * @param array   $permissions
	 * @param boolean $dest
	 * @return string|\WP_Error
	 */
	public static function set_pdf_auto_password( $pdf_path, $user_password, $owner_password, $permissions = array(), $dest = false ) {
		return self::set_pdf_password( $pdf_path, $user_password, $owner_password, $permissions, $dest );
	}

	/**
	 * Check if pdf is already encrypted.
	 *
	 * @param int|string $pdf_attachment_id Attachment ID or PDF PATH.
	 * @return boolean|\WP_Error
	 */
	public static function is_pdf_encrypted( $pdf_attachment_id ) {
		self::set_pdf_lib();
		if ( is_wp_error( self::$pdf_lib ) ) {
			return self::$pdf_lib;
		}
		$pdf_path = is_int( $pdf_attachment_id ) ? PDF_Password::get_pdf_file_by_id( $pdf_attachment_id ) : $pdf_attachment_id;
		return self::$pdf_lib->is_encrypted( $pdf_path );
	}

	/**
	 * Update Attachment PDF Password Meta field.
	 *
	 * @param int    $attachment_id
	 * @param string $user_pass
	 * @param string $owner_pass
	 * @param array  $perms
	 * @return void
	 */
	public static function update_attachment_pdf_pass( $attachment_id, $user_pass, $owner_pass, $perms ) {
		$pdf_pass = array(
			'user_pass'  => $user_pass,
			'owner_pass' => $owner_pass,
			'perms'      => $perms,
		);
		update_post_meta( $attachment_id, self::$pdf_pass_meta_key, $pdf_pass );
	}

	/**
	 * Delete Attachment PDF Password meta.
	 *
	 * @param int $attachment_id
	 * @return void
	 */
	public static function remove_attachment_pdf_pass( $attachment_id ) {
		delete_post_meta( $attachment_id, self::$pdf_pass_meta_key );
	}

	/**
	 * Check if pdf already has a password.
	 *
	 * @param int $attachment_id
	 * @return array|false
	 */
	public static function pdf_attachment_already_has_pass( $attachment_id ) {
		return get_post_meta( $attachment_id, self::$pdf_pass_meta_key, true );
	}

}
