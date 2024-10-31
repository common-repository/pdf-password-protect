<?php
namespace Xthiago\PDFVersionConverter\Converter;

/**
 * Classes that implements this interface can convert the PDF version of given file.
 *
 * @author Thiago Rodrigues <xthiago@gmail.com>
 */
interface ConverterInterface {

	/**
	 * Change PDF version of given $file to $newVersion.
	 *
	 * @param string $file Full PATH.
	 * @param string $new_file Full PATH.
	 * @param string $newVersion version (1.4, 1.5, 1.6, etc).
	 * @return void
	 */
	public function convert( $file, $new_file, $new_version );
}
