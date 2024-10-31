<?php
namespace Xthiago\PDFVersionConverter\Converter;

use GPLSCore\GPLS_PLUGIN_PSRPDF\ProcessCommand;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Converter that uses ghostscript to change PDF version.
 */
class GhostscriptConverter implements ConverterInterface {

	use ProcessCommand;

	/**
	 * @var GhostscriptConverterCommand
	 */
	protected $command;

	/**
	 * @param GhostscriptConverterCommand $command
	 * @param Filesystem                  $fs
	 * @param null|string                 $tmp
	 */
	public function __construct( GhostscriptConverterCommand $command ) {
		$this->command = $command;
	}

	/**
	 * {@inheritdoc }
	 */
	public function convert( $file, $new_file, $new_version ) {
		$this->command->run( $file, $new_file, $new_version );
	}

	/**
	 * Convert PDF Version using GhostScript.
	 *
	 * @param string  $pdf_path
	 * @param string  $version
	 * @param boolean $dest
	 * @return void
	 */
	public static function convert_pdf_version( $pdf_path, $version, $dest = false ) {
		$command       = new GhostscriptConverterCommand();
		$converter     = new GhostscriptConverter( $command, new Filesystem() );
		$dest          = $dest ? $dest : $pdf_path;
		$temp_pdf_path = trailingslashit( dirname( $pdf_path ) ) . wp_unique_filename( dirname( $pdf_path ), wp_basename( $pdf_path ) );

		$converter->convert( $pdf_path, $temp_pdf_path, $version );

		@copy( $temp_pdf_path, $dest );

		@unlink( $temp_pdf_path );

		return true;
	}

	/**
	 * Check if GhostScript is installed.
	 *
	 * @return boolean|\WP_Error
	 */
	public static function is_gs_installed( $return_error = true ) {
		try {
			$process = self::run( array( 'gs', '--version' ) );
			if ( ! $process->isSuccessful() ) {
				if ( $return_error ) {
					return new \WP_Error(
						'gs-is-installed-check-failed',
						sprintf( esc_html( '%s' ), $process->getErrorOutput() )
					);
				}
				return false;
			}
			$result = trim( $process->getOutput() );
			return version_compare( $result, '0.0.1', '>=' ) ? $result : false;
		} catch ( \Exception $e ) {
			if ( $return_error ) {
				return new \WP_Error(
					'gs-check-install-error',
					$e->getMessage()
				);
			}
			return false;
		}
	}

	/**
	 * Is the PDF file encrypted.
	 *
	 * @param string $pdf_path
	 * @return false|string|\WP_Error
	 */
	public static function is_encrypted( $pdf_path ) {
		try {
			$process      = self::run( array( 'gs', '-quiet', '-dBATCH', '-sNODISPLAY', $pdf_path ) );
			$error_result = $process->getErrorOutput();
			if ( false !== stripos( $error_result, 'This file requires a password' ) ) {
				return 'user';
			}
			return 'owner';
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'gs-check-is-encrypted-error',
				$e->getMessage()
			);
		}
	}
}
