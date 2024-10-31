<?php defined( 'ABSPATH' ) || exit();

$core          = $args['core'];
$plugin_info   = $args['plugin_info'];
$template_page = $args['template_page'];
?>

<div class="container">
	<!-- Bulk PDF Password -->
	<div class="bulk-pdf-passwords">
		<h3 class="p-5 border my-3 text-center"><?php esc_html_e( 'Premium feature', 'pdf-password-protect' ); ?><?php $core->pro_btn( '', 'Premium' ); ?></h3>
	</div>
</div>
