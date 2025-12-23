
<?php
/**
 * Admin Page Template
 *
 * @package Template_Porter_for_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="wrap etp-admin-wrap">
	<h1><?php esc_html_e( 'Template Porter for Elementor', 'template-porter-for-elementor' ); ?></h1>
	<p><?php esc_html_e( 'Export and import Elementor templates with images bundled.', 'template-porter-for-elementor' ); ?></p>

	<div class="etp-container">
		<!-- EXPORT SECTION -->
		<div class="etp-box">
			<h2><?php esc_html_e( 'Export Template', 'template-porter-for-elementor' ); ?></h2>
			<p><?php esc_html_e( 'Select a template to export with all images bundled into a ZIP file.', 'template-porter-for-elementor' ); ?></p>

			<form id="etp-export-form">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="etp-template-select"><?php esc_html_e( 'Select Template', 'template-porter-for-elementor' ); ?></label></th>
						<td>
							<select id="etp-template-select" name="template_id" required>
								<option value=""><?php esc_html_e( '-- Choose a template --', 'template-porter-for-elementor' ); ?></option>
								<?php foreach ( $templates as $tpl ) : ?>
									<option value="<?php echo esc_attr( $tpl->ID ); ?>">
										<?php echo esc_html( $tpl->post_title ); ?> (ID: <?php echo esc_html( $tpl->ID ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<p>
					<button type="submit" class="button button-primary" id="etp-export-btn">
						<?php esc_html_e( 'Export Template', 'template-porter-for-elementor' ); ?>
					</button>
					<span class="spinner" style="float:none;margin:0 10px;"></span>
				</p>
			</form>

			<div id="etp-export-result"></div>
		</div>

		<!-- IMPORT SECTION -->
		<div class="etp-box">
			<h2><?php esc_html_e( 'Import Template', 'template-porter-for-elementor' ); ?></h2>
			<p><?php esc_html_e( 'Upload a ZIP file exported by this plugin. Images will be imported into Media Library and template JSON will be updated automatically.', 'template-porter-for-elementor' ); ?></p>

			<form id="etp-import-form" enctype="multipart/form-data">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="etp-import-file"><?php esc_html_e( 'Choose ZIP file', 'template-porter-for-elementor' ); ?></label></th>
						<td>
							<input type="file" id="etp-import-file" name="import_file" accept=".zip" required />
						</td>
					</tr>
				</table>
				<p>
					<button type="submit" class="button button-primary" id="etp-import-btn">
						<?php esc_html_e( 'Import Template', 'template-porter-for-elementor' ); ?>
					</button>
					<span class="spinner" style="float:none;margin:0 10px;"></span>
				</p>
			</form>

			<div id="etp-import-result"></div>
		</div>
	</div>
</div>