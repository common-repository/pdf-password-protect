<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF\Password;

use GPLSCore\GPLS_PLUGIN_PSRPDF\PDF_Password_Protected;
use GPLSCore\GPLS_PLUGIN_PSRPDF\FPDI_Wrapper;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;
use PDF_Password_Settings;

/**
 * PDF Password Handle using FPDI.
 */
class PDF_Password_FPDI extends PDF_Password {

	/**
	 * Remap the FPDI error messages with ours.
	 *
	 * @var array
	 */
	private static $fpdi_error_msgs_remap = array();

	/**
	 * Advanced Compression Message.
	 *
	 * @var string
	 */
	private static $pdf_advanced_compression_msg = 'This PDF document probably uses a compression technique which is not supported by the ' .
	'free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)';

	/**
	 * Already encrypted FPDI Message.
	 *
	 * @var string
	 */
	private static $pdf_already_encrypted_msg = 'This PDF document is encrypted and cannot be processed with FPDI.';

	/**
	 * ALternative Method Message.
	 *
	 * @var string
	 */
	private static $alternative_method_msg;

	/**
	 * Setup.
	 *
	 * @return void
	 */
	protected function setup() {
		self::$alternative_method_msg                                       = ' check <a href="' . esc_url_raw( PDF_Password_Settings::page_path() . '&tab=status' ) . '">' . esc_html__( 'Status' ) . '</a>' . esc_html__( ' page to use alternative method', 'pdf-password-protect' );
		self::$fpdi_error_msgs_remap[ self::$pdf_already_encrypted_msg ]    = 'The PDF already encrypted with user or owner password';
		self::$fpdi_error_msgs_remap[ self::$pdf_advanced_compression_msg ] = 'The PDF uses an advanced compression tenchnique.' . self::$alternative_method_msg;

	}

	/**
	 * Handle Permissions.
	 *
	 * @param array $permissions
	 * @return array
	 */
	protected function handle_permissions( $permissions ) {

	}

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
	public function set_password( $pdf_path, $user_pass, $dest = false, $owner_pass = '', $permissions = array(), $downgrade = false ) {
		try {
			$pdf  = new FPDI_Wrapper( $pdf_path, $downgrade );
			$dest = ! $dest ? $pdf_path : $dest;
			return $pdf->set_password( $user_pass, $owner_pass, $dest, $permissions );
		} catch ( \Throwable $e ) {
			// Adjust the error message.
			$error_message = self::handle_fpdi_error_messages( $e->getMessage() );

			// If it's advanced compression, or soft encrypted, try again with downgrade.
			if ( GhostscriptConverter::is_gs_installed( false ) && ( self::$pdf_advanced_compression_msg === $e->getMessage() || self::$pdf_already_encrypted_msg === $e->getMessage() ) && ! $downgrade ) {
				return $this->set_password( $pdf_path, $user_pass, $dest, $owner_pass, $permissions, true );
			}

			return new \WP_Error(
				self::$plugin_info['classes_prefix'] . ( self::$pdf_already_encrypted_msg === $e->getMessage() ? '-pdf-already-encrypted' : ( self::$pdf_advanced_compression_msg === $e->getMessage() ? '-pdf-advanced-compression' : '-password-pdf-failed' ) ),
				$error_message
			);
		}
	}

	/**
	 * Check if PDFI is installed,which is by default
	 *
	 * @return boolean
	 */
	public static function is_installed() {
		return true;
	}

	/**
	 * check if pdf file is encrypted.
	 *
	 * @param string $pdf_path
	 * @return boolean|string|\WP_Error
	 */
	public function is_encrypted( $pdf_path, $check_encryption_type = false ) {
		try {
			$pdf_path = is_int( $pdf_path ) ? self::get_pdf_file_by_id( $pdf_path ) : $pdf_path;
			new FPDI_Wrapper( $pdf_path, false );
			// Should fail once initialize, if not, not encrypted.
			return false;
		} catch ( \Throwable $e ) {
			$error_message = trim( $e->getMessage() );

			// Is advanced compression.
			if ( $error_message === self::$pdf_advanced_compression_msg ) {
				// Pypass if GS is here.
				if ( GhostscriptConverter::is_gs_installed( false ) ) {
					return false;
				}
			}

			// Check if owner password.
			if ( GhostscriptConverter::is_gs_installed( false ) ) {
				return GhostscriptConverter::is_encrypted( $pdf_path );
			}

			// Is already encrypted.
			if ( self::is_pdf_already_encrypted_err( $error_message ) ) {
				return true;
			}



			return new \WP_Error(
				'pdfi-is-encrypted-failed',
				$e->getMessage()
			);
		}
	}

	/**
	 * Check if PDF is already encrypted Error.
	 *
	 * @param \WP_Error|string $wp_err
	 * @return boolean
	 */
	private static function is_pdf_already_encrypted_err( $wp_err ) {
		return (
			(
				is_wp_error( $wp_err )
				&&
				(
					( $wp_err->get_code() === self::$plugin_info['classes_prefix'] . '-pdf-already-encrypted' )
				||
					( $wp_err->get_error_message() === self::$pdf_already_encrypted_msg )
				)
			)
		||
			( is_string( $wp_err ) && $wp_err === self::$pdf_already_encrypted_msg )
		);
	}

	/**
	 * Adjust the FPDI set pdf password error messages.
	 *
	 * @param string $message
	 * @return string
	 */
	private static function handle_fpdi_error_messages( $message ) {
		if ( ! empty( self::$fpdi_error_msgs_remap[ $message ] ) ) {
			return self::$fpdi_error_msgs_remap[ $message ];
		}
		return $message;
	}

	/**
	 * PDF Password Metabox.
	 *
	 * @param \WP_Post $post
	 * @return void
	 */
	public function pdf_password_metabox( $post ) {
		$pdf_path         = self::get_pdf_file_by_id( $post->ID );
		$pdf_pass         = PDF_Password_Protected::pdf_attachment_already_has_pass( $post->ID );
		$is_encrypted     = $this->is_encrypted( $pdf_path, true );
		$pdf_has_password = ! empty( $pdf_pass ) ? $pdf_pass : false;
		?>
		<div class="<?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-password-protected-pdf-wrapper' ); ?> position-relative mt-4 form">
		<?php PDF_Password_Protected::loader_html( self::$plugin_info['classes_prefix'] ); ?>
		<?php
		if ( is_wp_error( $is_encrypted ) ) :
			$error_message = self::handle_fpdi_error_messages( $is_encrypted->get_error_message() );
			$this->error_message( $error_message, false, '', false );
			return;
			endif;
		?>
		<?php
		if ( ! $pdf_has_password && $is_encrypted ) :
			if ( 'owner' === $is_encrypted ) : ?>
				<p class="text-small bg-light d-block fw-light p-1 mb-2 mt-1">
					<?php esc_html_e( 'PDF already has owner password only. It can be overwritten in Premium version.', 'pdf-password-protect' ); ?>
				</p>
				<?php self::$core->pro_btn( '', 'Premium', 'mb-2' ); ?>
			<?php elseif ( 'user' === $is_encrypted ) : ?>
				<p class="text-small bg-light d-block fw-light p-1 mb-2 mt-1">
					<?php esc_html_e( 'PDF already has user password.', 'pdf-password-protect' ); ?>
				</p>
			<?php
				else :
					$this->error_message( self::handle_fpdi_error_messages( self::$pdf_already_encrypted_msg ), false, '', false );
			endif;
		endif;
		?>

		<?php if ( ! $is_encrypted || $pdf_has_password ) : ?>
			<!-- Assign Password -->
			<div class="mb-3">
				<!-- User Password. -->
				<label class="form-label"><?php esc_html_e( 'User password', 'pdf-password-protect' ); ?></label>
				<input type="text" class="form-control <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-add-pdf-password ' . self::$plugin_info['classes_prefix'] . '-add-pdf-user-password' ); ?>" <?php echo esc_attr( $pdf_has_password ? 'disabled=disabled' : '' ); ?> value="<?php echo esc_attr( $pdf_has_password ? $pdf_pass['user_pass'] : '' ); ?>">
				<div class="form-text d-none gpls-general-notice gpls-general-error-notice owner-pass-only-error text-white p-2 position-relative"><?php esc_html_e( 'Using owner password without user password is not secure and it can be overwritten.', 'pdf-password-protect' ); ?></div>
			</div>
			<div class="mb-3">
				<!-- Owner Password -->
				<label class="form-label"><?php esc_html_e( 'Owner password', 'pdf-password-protect' ); ?></label>
				<input type="text" class="form-control <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-add-pdf-password ' . self::$plugin_info['classes_prefix'] . '-add-pdf-owner-password' ); ?>" <?php echo esc_attr( $pdf_has_password ? 'disabled=disabled' : '' ); ?> value="<?php echo esc_attr( $pdf_has_password ? $pdf_pass['owner_pass'] : '' ); ?>">
				<div class="form-text d-none gpls-general-notice gpls-general-error-notice user-pass-only-error text-white p-2 position-relative"><?php esc_html_e( 'Using user password without owner password is not secure because PDF can be opened without password, use the same password for owner password if you don\'t need to restrict permissions', 'pdf-password-protect' ); ?></div>
			</div>
			<div class="permissions-checklist collapse <?php echo esc_attr( $pdf_has_password ? 'show' : '' ); ?>">
				<div class="wrapper mb-3 border p-2">
					<h6 class="form-label mt-1"><?php esc_html_e( 'Disable permissions', 'pdf-password-protect' ); ?></h6>
					<p class="p-1 mb-3 bg-light">
						<?php esc_html_e( 'check permissions to disable', 'pdf-password-protect' ); ?>
						<span class="d-inline-block popover-hint bg-warning d-inline-flex align-items-center justify-content-center fw-bold" tabindex="0" data-bs-toggle="tooltip" data-bs-title="<?php esc_html_e( 'Disable permissions can be ignored by some PDF viewer applications. always rely on passwords to protect your PDF files', 'pdf-password-protect' ); ?>" style="width:20px;height:20px;border-radius:50%;">?</span>
					</p>
					<!-- Permissions -->
					<?php foreach ( PDF_Password_Protected::get_permissions() as $permission_name => $permission_arr ) : ?>
						<div class="mb-1 border p-2 d-flex align-items-center pt-3">
							<input <?php echo esc_attr( ! empty( $pdf_pass['perms'] ) && in_array( $permission_name, $pdf_pass['perms'] ) ? 'checked=checked' : '' ); ?> type="checkbox" value="<?php echo esc_attr( $permission_name ); ?>" class="me-1 <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-add-pdf-user-permission' ); ?> <?php echo esc_attr( $pdf_has_password ? 'disabled' : '' ); ?>" <?php echo esc_attr( $pdf_has_password ? 'disabled=disabled' : '' ); ?> >
							<label class="form-label my-1 pb-1"><?php echo esc_html( $permission_arr['label'] ); ?></label>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( ! $pdf_has_password ) : ?>
				<button data-action="<?php echo esc_attr( self::$plugin_info['name'] . '-assign-pdf-password' ); ?>" data-attachment_id="<?php echo esc_attr( absint( $post->ID ) ); ?>" type="button" class="disabled btn-block my-3 btn btn-primary <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-assign-pdf-password-btn ' . self::$plugin_info['classes_prefix'] . '-pdf-password-btn' ); ?>" ><?php esc_html_e( 'Set the password', 'pdf-password-protect' ); ?></button>
			<?php endif; ?>

		<?php endif; ?>
		<?php
	}
}
