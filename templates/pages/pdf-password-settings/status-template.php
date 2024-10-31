<?php defined( 'ABSPATH' ) || exit();

use GPLSCore\GPLS_PLUGIN_PSRPDF\Password\PDF_Password_QPDF;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;

$core          = $args['core'];
$plugin_info   = $args['plugin_info'];
$template_page = $args['template_page'];
?>
<div class="container">
	<!-- Remove Password Requirements -->
	<div class="assign-password-status my-5">
		<h5 class="border p-3 bg-white shadow-sm"><?php esc_html_e( 'Required libs' ); ?></h5>
		<div class="requirements-list bg-light p-3">
			<ul class="px-0">
				<!-- Qpdf -->
				<?php
					$qpdf_installed = PDF_Password_QPDF::is_installed();
					$is_low_version = ( is_wp_error( $qpdf_installed ) && 'qpdf-low-version' === $qpdf_installed->get_error_code() ) ? $qpdf_installed->get_error_message() : false;
				?>
				<li class="req-row px-3 border shadow-sm bg-white py-3">
					<div class="row align-items-center">
						<div class="col-12">
							<div class="row">
								<div class="col-md-3 border bg-light px-3 py-2">
									<div class="req-name">
										<a target="_blank" href="https://qpdf.readthedocs.io/"><?php esc_html_e( 'QPDF' ); ?></a>
									</div>
								</div>
								<div class="col-md-9 border bg-light px-3 py-2">
									<div class="req-status text-end">
										<span class="install-status-icon <?php echo esc_attr( ( $qpdf_installed && ! is_wp_error( $qpdf_installed ) ) ? 'led-green' : 'led-red' ); ?> mx-2 align-middle"></span>
										<?php
										if ( $is_low_version ) :
											$qpdf_version = PDF_Password_QPDF::get_version();
											?>
											<span class="align-middle"><?php echo esc_html( 'V ' . $qpdf_version ); ?></span>
										<?php else : ?>
											<span class="align-middle"><?php printf( esc_html( '%s', 'pdf-password-protect' ), ( $qpdf_installed && ! is_wp_error( $qpdf_installed ) ) ? 'V ' . $qpdf_installed : 'Not installed' ); ?></span>
										<?php endif; ?>
									</div>
								</div>
								<?php if ( ! $qpdf_installed || is_wp_error( $qpdf_installed ) ) : ?>
								<div class="col-md-12 mt-4 ps-4">
									<h6><?php esc_html_e( 'Version check result: ', 'pdf-password-protect' ); ?></h6>
									<code style="padding:4px;">
										<?php echo esc_html( is_wp_error( $qpdf_installed ) ? $qpdf_installed->get_error_message() : $qpdf_installed ); ?>
									</code>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="row my-4">
						<div class="col-md-12 mt-4 ps-4">
							<div class="usage-box bg-white">
								<h6><?php esc_html_e( 'Used for', 'pdf-password-protect' ); ?></h6>
								<ul class="list-group">
									<li class="list-group-item my-0"><?php esc_html_e( 'Add password to PDF files that have [ forms - annotations - bookmarks ]', 'pdf-password-protect' ); ?></li>
									<li class="list-group-item my-0"><?php esc_html_e( 'Remove passwords', 'pdf-password-protect' ); ?> <?php $core->pro_btn( '', 'Premium' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
					<?php if ( ! $qpdf_installed || is_wp_error( $qpdf_installed ) ) : ?>
					<!-- How to Install -->
					<div class="row-my-4">
						<div class="col-md-12 mt-4 ps-4">
							<h6><?php esc_html_e( 'Install command', 'pdf-password-protect' ); ?></h6>
							<ul>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'apt' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo apt-get install -y qpdf' ); ?></code></div>
									</div>
								</li>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'yum' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo yum -y install qpdf-libs' ); ?></code></div>
									</div>
								</li>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'dnf' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo dnf -y install qpdf-libs' ); ?></code></div>
									</div>
								</li>
							</ul>
						</div>
						<div class="col-12">
							<?php if ( is_wp_error( $qpdf_installed ) && ( $template_page::$process_error_msg === $qpdf_installed->get_error_message() ) ) : ?>
							<h6 class="p-3 my-4 bg-light"><?php esc_html_e( 'proc_open() function seems not available, please contact your hosting support if it can be enabled or consider upgrading to a higher hosting plan', 'pdf-password-protect' ); ?></h6>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
				</li>

				<!-- GhostScript -->
				<?php $gs_installed = GhostscriptConverter::is_gs_installed(); ?>
				<li class="req-row px-3 border shadow-sm bg-white py-3">
					<div class="row align-items-center">
						<div class="col-12">
							<div class="row">
								<div class="col-md-3 border bg-light px-3 py-2">
									<div class="req-name">
										<a target="_blank" href="https://www.ghostscript.com/"><?php esc_html_e( 'GhostScript' ); ?></a>
									</div>
								</div>
								<div class="col-md-9 border bg-light px-3 py-2">
									<div class="req-status text-end">
										<span class="install-status-icon <?php echo esc_attr( ( $gs_installed && ! is_wp_error( $gs_installed ) ) ? 'led-green' : 'led-red' ); ?> mx-2 align-middle"></span>
										<span class="align-middle"><?php printf( esc_html( '%s', 'pdf-password-protect' ), ( $gs_installed && ! is_wp_error( $gs_installed ) ) ? 'V ' . $gs_installed : 'Not installed' ); ?></span>
									</div>
								</div>
								<?php if ( ! $gs_installed || is_wp_error( $gs_installed ) ) : ?>
								<div class="col-md-12 mt-4 ps-4">
									<h6><?php esc_html_e( 'Version check result: ', 'pdf-password-protect' ); ?></h6>
									<code style="padding:4px;">
										<?php echo esc_html( is_wp_error( $gs_installed ) ? $gs_installed->get_error_message() : $gs_installed ); ?>
									</code>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="row my-4">
						<div class="col-md-12 mt-4 ps-4">
							<div class="usage-box bg-white">
								<h6><?php esc_html_e( 'Used for', 'pdf-password-protect' ); ?></h6>
								<ul class="list-group">
									<li class="list-group-item my-0"><?php esc_html_e( 'Add password to PDF files that use advanced compression techniques', 'pdf-password-protect' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
					<?php if ( ! $gs_installed || is_wp_error( $gs_installed ) ) : ?>
					<!-- How to Install -->
					<div class="row-my-4">
						<div class="col-md-12 mt-4 ps-4">
							<h6><?php esc_html_e( 'Install command', 'pdf-password-protect' ); ?></h6>
							<ul>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'apt' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo apt-get install -y ghostscript' ); ?></code></div>
									</div>
								</li>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'yum' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo yum -y install ghostscript' ); ?></code></div>
									</div>
								</li>
								<li class="my-0">
									<div class="row my-0">
										<div class="col-md-3 border p-3"><?php echo esc_html( 'dnf' ); ?></div>
										<div class="col-md-9 border p-3"><code><?php echo esc_html( 'sudo dnf -y install ghostscript' ); ?></code></div>
									</div>
								</li>
							</ul>
						</div>
						<div class="col-12">
							<?php if ( is_wp_error( $gs_installed ) && ( $template_page::$process_error_msg === $gs_installed->get_error_message() ) ) : ?>
							<h6 class="p-3 my-4 bg-light"><?php esc_html_e( 'proc_open() function seems not available, please contact your hosting support if it can be enabled or consider upgrading to a higher hosting plan', 'pdf-password-protect' ); ?></h6>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
				</li>
			</ul>
		</div>
	</div>
</div>
