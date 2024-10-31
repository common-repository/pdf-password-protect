<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF;

use Exception;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;
use Xthiago\PDFVersionConverter\Guesser\RegexGuesser;
use GPLSCore\GPLS_PLUGIN_PSRPDF\Utils\Helpers;
use setasign\FpdiProtection\FpdiProtection;


/**
 * FPDI Wrapper.
 */
class FPDI_Wrapper extends FpdiProtection {

	use Helpers;

	/**
	 * PDF Pages Count.
	 *
	 * @var int
	 */
	protected $pages_count;

	/**
	 * PDF Path.
	 *
	 * @var string
	 */
	private $pdf_path;

	/**
	 * PDF Path.
	 *
	 * @var string
	 */
	private $watermark_type;

	/**
	 * Watemark angle.
	 *
	 * @var integer
	 */
	private $angle = 0;

	/**
	 * PDF Path.
	 *
	 * @var array
	 */
	private $watermarks = array();

	/**
	 * Alpha Ext States.
	 *
	 * @var array
	 */
	protected $extgstates = array();

	/**
	 * Current Applying watermark.
	 *
	 * @var array
	 */
	private $current_watermark = array();

	/**
	 * Handle newer versions check.
	 *
	 * @var boolean
	 */
	private $handle_version = false;

	/**
	 * Fonts Path.
	 *
	 * @var string
	 */
	private $fonts_path;

	/**
	 * Permisson Mapper.
	 *
	 * @var array
	 */
	private $permission_mapping = array(
		'print'         => self::PERM_PRINT,
		'modify'        => self::PERM_MODIFY,
		'copy'          => self::PERM_COPY,
		'annot'         => self::PERM_ANNOT,
		'fill-form'     => self::PERM_FILL_FORM,
		'accessibility' => self::PERM_ACCESSIBILITY,
		'assemble'      => self::PERM_ASSEMBLE,
		'print_digital' => self::PERM_DIGITAL_PRINT,
	);

	/**
	 * Full Permissions.
	 *
	 * @var int
	 */
	private $full_permissions = self::PERM_PRINT | self::PERM_MODIFY | self::PERM_COPY | self::PERM_ANNOT | self::PERM_FILL_FORM | self::PERM_ACCESSIBILITY | self::PERM_ASSEMBLE | self::PERM_DIGITAL_PRINT;

	/**
	 * Constructor.
	 *
	 * @param string $pdf_path
	 * @param string $watermark_type
	 * @param array  $watermark_details
	 */
	public function __construct( $pdf_path, $handle_version = false ) {
		$this->pdf_path       = $pdf_path;
		$this->handle_version = $handle_version;

		parent::__construct();

		$this->prepare_pdf();
		$this->setup();
	}

	/**
	 * Setup the PDF.
	 *
	 * @return void
	 */
	private function setup() {
		$this->pages_count = $this->setSourceFile( $this->pdf_path );
	}

	/**
	 * Adjust the pdf verison - encryption etc.
	 *
	 * @return void
	 */
	private function prepare_pdf() {
		if ( $this->handle_version ) {
			$this->handle_pdf_version();
		}
	}

	/**
	 * Handle Permissions from string to Binary.
	 *
	 * @param array $permission
	 * @return int
	 */
	private function handle_permission( $permissions ) {
		$result_perm = $this->full_permissions;
		foreach ( $permissions as $perm ) {
			if ( in_array( $perm, array_keys( $this->permission_mapping ) ) ) {
				$result_perm = $result_perm & ~ $this->permission_mapping[ $perm ];
			}
		}
		return $result_perm;
	}

	/**
	 * Set Password to PDF.
	 *
	 * @param string $user_pass
	 * @param string $owner_pass
	 * @param string $dest
	 * @param array  $permissions
	 * @return void
	 */
	public function set_password( $user_pass, $owner_pass, $dest, $permissions = array() ) {
		try {
			// 1) Loop over the PDF pages and apply each page template.
			for ( $index = 1; $index <= $this->pages_count; $index++ ) {

				// Get PDF Page template.
				$pdf_page_template = $this->importPage( $index );

				// Get template Dimensions.
				$pdf_page_template_dimension = $this->getTemplateSize( $pdf_page_template );

				$orientation = ( $pdf_page_template_dimension['height'] > $pdf_page_template_dimension['width'] ) ? 'P' : 'L';
				if ( 'P' === $orientation ) {
					$this->AddPage( $orientation, array( $pdf_page_template_dimension['width'], $pdf_page_template_dimension['height'] ) );
				} else {
					$this->AddPage( $orientation, array( $pdf_page_template_dimension['height'], $pdf_page_template_dimension['width'] ) );
				}

				// Use imported Page as a template.
				$this->useTemplate( $pdf_page_template );
			}

			// 2) Set the protection section.
			$this->setProtection(
				$this->handle_permission( $permissions ),
				$user_pass,
				$owner_pass
			);

			$this->save_pdf( $dest );
			return $dest;

		} catch ( \Exception $e ) {

			return new \WP_Error(
				'pdf-encryption-failed',
				$e->getMessage()
			);
		}
	}

	/**
	 * Adjust the PDF Version
	 *
	 * @return void
	 */
	private function handle_pdf_version() {
		$guesser     = new RegexGuesser();
		$pdf_version = $guesser->guess( $this->pdf_path );

		if ( is_null( $pdf_version ) ) {
			throw new Exception( esc_html__( 'Failed to detect the PDF version.', 'pdf-password-protect' ) );
		}

		GhostscriptConverter::convert_pdf_version( $this->pdf_path, $pdf_version );
	}

	/**
	 * Save PDF Result to Desination File.
	 *
	 * @param string $dest
	 * @return void
	 */
	public function save_pdf( $dest ) {
		$this->Output( 'F', $dest );
	}

}
