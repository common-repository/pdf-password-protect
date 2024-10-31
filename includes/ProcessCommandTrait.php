<?php
namespace GPLSCore\GPLS_PLUGIN_PSRPDF;

use Symfony\Component\Process\Process;

/**
 * Process Command Trait
 *
 */
trait ProcessCommand {

	/**
	 * Run Process Command.
	 *
	 * @param string $original_file
	 * @param string $new_file
	 * @param string $new_version
	 * @return Process
	 */
	public static function run( $command ) {
		$process = new Process( $command );
		$process->run();
		return $process;
	}
}
