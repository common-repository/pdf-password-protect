<?php

namespace GPLSCore\GPLS_PLUGIN_PSRPDF\Password;

use GPLSCore\GPLS_PLUGIN_PSRPDF\ProcessCommand;
use GPLSCore\GPLS_PLUGIN_PSRPDF\Utils\NoticeUtils;
use Xthiago\PDFVersionConverter\Guesser\RegexGuesser;


/**
 * PDF Password Lib Base Class.
 */
abstract class PDF_Password {

	use ProcessCommand, NoticeUtils;

	/**
	 * Plugin Info Array.
	 *
	 * @var array
	 */
	protected static $plugin_info;

	/**
	 * Core Object.
	 *
	 * @var object
	 */
	protected static $core;

	/**
	 * Constructor.
	 *
	 * @param object $core
	 * @param array  $plugin_info
	 */
	public function __construct( $core, $plugin_info ) {
		self::$core        = $core;
		self::$plugin_info = $plugin_info;

		if ( method_exists( $this, 'setup' ) ) {
			$this->setup();
		}
	}

	/**
	 * Handle WordPress Permissions.
	 *
	 * @param array $permissions
	 * @return array
	 */
	abstract protected function handle_permissions( $permissions );

	/**
	 * Set PDF Password.
	 *
	 * @param string $pdf_path
	 * @param string $user_pass
	 * @param string $dest
	 * @param string $owner_pass
	 * @param array  $permissions
	 * @return string|\WP_Error
	 */
	abstract public function set_password( $pdf_path, $user_pass, $dest, $owner_pass = '', $permissions = array() );

	/**
	 * Is PDF lib is installed.
	 *
	 * @return string|false|\WP_Error
	 */
	abstract public static function is_installed();

	/**
	 * check if pdf file is encrypted.
	 *
	 * @param string $pdf_path
	 * @return boolean|string|\WP_Error
	 */
	abstract public function is_encrypted( $pdf_path, $check_encryption_type = false );

	/**
	 * PDF password Metabox HTML.
	 *
	 * @param \WP_Post $post
	 * @return void
	 */
	abstract public function pdf_password_metabox( $post );

	/**
	 * Choose PDF Library.
	 *
	 * @return PDF_Password|\WP_Error
	 */
	public static function get_pdf_lib( $core, $plugin_info ) {
		$pdf_libs = apply_filters( $plugin_info['name'] . '-pdf-libs', array( 'PDF_Password_QPDF', 'PDF_Password_FPDI' ) );
		foreach ( $pdf_libs as $pdf_lib_class ) {
			$pdf_lib_class = __NAMESPACE__ . '\\' . $pdf_lib_class;
			$is_installed  = $pdf_lib_class::is_installed();
			if ( $is_installed && ! is_wp_error( $is_installed ) ) {
				return new $pdf_lib_class( $core, $plugin_info );
			}
		}
		return new \WP_Error(
			$plugin_info['name'] . '-no-pdf-lib-found',
			esc_html__( 'No PDF library found' )
		);
	}

	/**
	 * Get PDF path by Attachment ID.
	 *
	 * @param int $attachment_id
	 * @return string|false
	 */
	public static function get_pdf_file_by_id( $attachment_id ) {
		return get_attached_file( $attachment_id );
	}

	/**
	 * Get PDF Version.
	 *
	 * @param string $pdf_path
	 * @return string|false
	 */
	public static function get_pdf_version( $pdf_path ) {
		try {
			$guesser     = new RegexGuesser();
			$pdf_version = $guesser->guess( $pdf_path );
			return $pdf_version;
		} catch ( \Exception $e ) {
			return false;
		}
	}

}
