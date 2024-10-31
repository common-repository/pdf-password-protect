<?php defined( 'ABSPATH' ) || exit();

$core          = $args['core'];
$plugin_info   = $args['plugin_info'];
$template_page = $args['template_page'];

?>
<div class="container">
	<!-- Restricted PDF -->
	<div class="assign-password-status my-5">
		<div>
			<h3 class="p-5 border my-3 text-center"><?php esc_html_e( 'Premium feature', 'pdf-password-protect' ); ?><?php $core->pro_btn( '', 'Premium' ); ?></h3>
		</div>
		<form disabled enctype="multipart/form-data" method="post" action="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>" class="media-upload-form type-form validate position-relative" id="file-form">
			<div style="background:#EEE;z-index:1000;opacity:0.5;" class="overlay position-absolute w-100 h-100"></div>
            <?php media_upload_form(); ?>
			<script type="text/javascript">
			var post_id = 0, shortform = 3;
			</script>
			<?php wp_nonce_field( 'media-form' ); ?>
			<div id="media-items" class="hide-if-no-js"></div>
		</form>
		<h6 class="mx-auto text-center my-3 text-muted"><?php esc_html_e( 'PDF files Uploaded using this uploader will be blocked from view or download', 'pdf-password-protect' ); ?></h6>
	</div>
</div>
