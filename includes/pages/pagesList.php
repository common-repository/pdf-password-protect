<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF\pages;

use PDF_Password_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Pages.
 */
function setup_pages( $core, $plugin_info ) {
	PDF_Password_Settings::get_instance( $core, $plugin_info );
}
