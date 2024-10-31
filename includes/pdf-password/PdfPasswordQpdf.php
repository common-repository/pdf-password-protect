<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF\Password;

use GPLSCore\GPLS_PLUGIN_PSRPDF\PDF_Password_Protected;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;

/**
 * PDF Password Handle using QPDF.
 */
class PDF_Password_QPDF extends PDF_Password {

	/**
	 * Minimum acceptable Version.
	 *
	 * @var string
	 */
	private static $min_version = '9.1.1';

	/**
	 * Permissions Mapping for QPDF commands.
	 *
	 * @var array
	 */
	private $permissions_mapping = array(
		'copy'      => array(
			'args'  => array( 'extract' ),
			'value' => 'n',
		),
		'fill-form' => array(
			'args'  => array( 'form', 'annotate', 'modify-other' ),
			'value' => 'n',
		),
		'print'     => array(
			'args'  => array( 'print' ),
			'value' => 'none',
		),
		'comment'   => array(
			'args'  => array( 'annotate' ),
			'value' => 'n',
		),
	);

	/**
	 * Handle Permissions.
	 *
	 * @param array $permissions
	 * @return array
	 */
	protected function handle_permissions( $permissions ) {
		$perms = array();
		foreach ( $permissions as $perm ) {
			if ( ! empty( $this->permissions_mapping[ $perm ] ) ) {
				foreach ( $this->permissions_mapping[ $perm ]['args'] as $arg ) {
					$perms[] = '--' . $arg . '=' . $this->permissions_mapping[ $perm ]['value'];
				}
			}
		}
		return $perms;
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
	public function set_password( $pdf_path, $user_pass, $dest, $owner_pass = '', $permissions = array() ) {
		try {
			// Check first if the file is already encrypted.
			$is_encrypted = $this->is_encrypted( $pdf_path, true );
			if ( 'user' === $is_encrypted ) {
				return new \WP_Error(
					'qpdf-pdf-already-encrypted',
					esc_html__( 'The PDF already encrypted', 'pdf-password-protect' )
				);
			}

			if ( 'owner' === $is_encrypted ) {
				// 1) Get PDF Version.
				$pdf_version = self::get_pdf_version( $pdf_path );
				if ( ! $pdf_version ) {
					$pdf_version = '1.4';
				}

				// 2) Convert with GhostScript to reset permissions.
				$convert_result = GhostscriptConverter::convert_pdf_version( $pdf_path, $pdf_version );

				if ( is_wp_error( $convert_result ) ) {
					return $convert_result;
				}
			}

			$permissions = $this->handle_permissions( $permissions );

			// Apply Password.
			$dest_for_command = ( ! $dest || ( $pdf_path === $dest ) ) ? '--replace-input' : $dest;
			$owner_pass       = empty( $owner_pass ) ? $user_pass : $owner_pass;
			$command_args     = array( 'qpdf', '-encrypt', $user_pass, $owner_pass, 256 );
			$command_args     = array_merge( $command_args, $permissions );

			$command_args[] = '--';
			$command_args[] = $pdf_path;
			$command_args[] = $dest_for_command;

			$process = self::run( $command_args );

			if ( ! $process->isSuccessful() ) {

				$exit_code = $process->getExitCode();
				$error_msg = $process->getErrorOutput();

				// 1) Check if its just a warning.
				if ( 3 === $exit_code ) {
					// 2) Check if the encryption already applied, pypass.
					if ( $this->is_encrypted( $dest ) ) {
						return $dest;
					}
				}

				if ( false !== stripos( $error_msg, 'file is damaged' ) ) {
					$error_msg = 'PDF file is damaged';
				}

				return new \WP_Error(
					'qpdf-set-password-failed',
					sprintf( esc_html( '%s' ), $error_msg )
				);
			}

			// Check the output result.
			$result = trim( $process->getOutput() );
			if ( empty( $result ) ) {
				return $dest;
			}

			// Errors.
			if ( false !== strpos( $result, 'invalid password' ) ) {
				return new \WP_Error(
					'qpdf-remove-password-already-encrypted',
					esc_html__( 'The PDF already encrypted', 'pdf-password-protect' )
				);
			}
			return new \WP_Error(
				'qpdf-remove-password-failed',
				$result
			);
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'qpdf-set-password-failed',
				$e->getMessage()
			);
		}
	}

	/**
	 * Check if PDFTK is installed.
	 *
	 * @return string|false|\WP_Error
	 */
	public static function is_installed() {
		try {
			$process = self::run( array( 'qpdf', '--version' ) );
			if ( ! $process->isSuccessful() ) {
				return new \WP_Error(
					'qpdf-is-installed-check-failed',
					sprintf( esc_html( '%s' ), $process->getErrorOutput() )
				);
			}

			$result         = $process->getOutput();
			$qpdf_version   = preg_match( '/(?:qpdf version)\s*((?:[0-9]+\.?)+)/i', $result, $matches ) && version_compare( trim( $matches[1] ), '0.0.1', '>=' ) ? trim( $matches[1] ) : false;
			$version_result = version_compare( $qpdf_version, self::$min_version, '>=' );
			if ( ! $version_result ) {
				return new \WP_Error(
					'qpdf-low-version',
					sprintf( esc_html__( 'Minimum Version required is %s', 'pdf-password-protect' ), self::$min_version )
				);
			}
			return $qpdf_version;
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'qpdf-check-install-error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Get QPDF Version.
	 *
	 * @return string
	 */
	public static function get_version() {
		$process = self::run( array( 'qpdf', '--version' ) );
		$result  = $process->getOutput();
		return preg_match( '/(?:qpdf version)\s*((?:[0-9]+\.?)+)/i', $result, $matches ) && version_compare( trim( $matches[1] ), '0.0.1', '>=' ) ? trim( $matches[1] ) : false;
	}

	/**
	 * check if pdf file is encrypted.
	 *
	 * @param string $pdf_path
	 * @return boolean|string|\WP_Error
	 */
	public function is_encrypted( $pdf_path, $check_encryption_type = false ) {
		// Check if its not encrypted.
		$process   = self::run( array( 'qpdf', '--is-encrypted', $pdf_path ) );
		$exit_code = $process->getExitCode();

		if ( $exit_code ) {
			return false;
		}

		if ( ! $check_encryption_type ) {
			return true;
		}

		// Check encryption type.
		$process   = self::run( array( 'qpdf', '--requires-password', $pdf_path ) );
		$exit_code = $process->getExitCode();

		if ( 0 === $exit_code ) {
			return 'user';
		}

		if ( 3 === $exit_code ) {
			return 'owner';
		}

		return false;
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

		<?php if ( ! $pdf_has_password && $is_encrypted ) : ?>
			<?php if ( 'owner' === $is_encrypted ) : ?>
				<p class="text-small bg-light d-block fw-light p-1 mb-2 mt-1">
					<?php esc_html_e( 'PDF already has owner password only. It can be overwritten in Premium version.', 'pdf-password-protect' ); ?>
				</p>
				<?php self::$core->pro_btn( '', 'Premium', 'mb-2' ); ?>
			<?php elseif ( 'user' === $is_encrypted ) : ?>
				<p class="text-small bg-light d-block fw-light p-1 mb-2 mt-1">
					<?php esc_html_e( 'PDF already has user password.', 'pdf-password-protect' ); ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>

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
							<input <?php echo esc_attr( ! empty( $pdf_pass['perms'] ) && in_array( $permission_name, $pdf_pass['perms'] ) ? 'checked=checked' : '' ); ?> type="checkbox" value="<?php echo esc_attr( $permission_name ); ?>" class="me-1 <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-add-pdf-user-permission' ); ?> <?php echo esc_attr( $is_encrypted && $pdf_has_password ? 'disabled' : '' ); ?>" <?php echo esc_attr( $is_encrypted && $pdf_has_password ? 'disabled=disabled' : '' ); ?> >
							<label class="form-label my-1 pb-1"><?php echo esc_html( $permission_arr['label'] ); ?></label>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( ! $is_encrypted && ! $pdf_has_password ) : ?>
				<button data-action="<?php echo esc_attr( self::$plugin_info['name'] . '-assign-pdf-password' ); ?>" data-attachment_id="<?php echo esc_attr( absint( $post->ID ) ); ?>" type="button" class="disabled btn-block my-3 btn btn-primary <?php echo esc_attr( self::$plugin_info['classes_prefix'] . '-assign-pdf-password-btn ' . self::$plugin_info['classes_prefix'] . '-pdf-password-btn' ); ?>" ><?php esc_html_e( 'Set the password', 'pdf-password-protect' ); ?></button>
			<?php endif; ?>
		<?php endif; ?>

			<!-- Remove Password -->
			<div class="remove-password-container w-100 border-top">
				<h6 class="form-label my-3"><?php esc_html_e( 'Remove password', 'pdf-password-protect' ); ?></h6>

				<input type="text" class="form-control disabled" placeholder="<?php esc_html_e( 'Owner or User password', 'pdf-password-protect' ); ?>" disabled="disabled" />
				<button disabled="disabled" type="button" class="disabled btn-block my-3 btn btn-primary" ><?php esc_html_e( 'Remove password', 'pdf-password-protect' ); ?></button>
				<p class="text-small bg-light d-block fw-light p-1 mb-2 mt-1">
					<?php esc_html_e( 'Remove password is part of Premium version', 'pdf-password-protect' ); ?>
				</p>

				<?php self::$core->pro_btn( '', 'Premium' ); ?>
			</div>
		</div>
		<?php
	}
}
