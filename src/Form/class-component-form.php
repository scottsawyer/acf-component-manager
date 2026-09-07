<?php
/**
 * Component form class.
 *
 * @package acf-component-manager
 *
 * @since 0.0.1
 */

declare( strict_types=1 );

namespace AcfComponentManager\Form;

// If called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Contains components form.
 */
class ComponentForm extends FormBase {

	/**
	 * Provides the ComponentForm form.
	 *
	 * @param array $components An array of theme components.
	 *
	 * @return void
	 */
	public function form( array $components ): void {
		?>
		<form method="post" action="<?php print $this->get_form_url(); ?>">
			<?php wp_nonce_field( 'acf_component_manager', 'save' ); ?>
			<input type="hidden" name="action" value="save">
			<input type="hidden" name="callback" value="manage_components">
			<div class="instructions">
				<p><?php print __( 'ACF Component Manager helps manage ACF components.', 'acf-component-manager' ); ?></p>
				<p class="instructions">
					<?php
					print __( 'To manage components with Component Manager, export the ACF component and place the JSON file in your theme or plugin and configure the source directory.', 'acf-component-manager' )
					?>
				</p>

				<p class="warning"><?php print __( 'There can only be one ACF JSON export per directory, and the \'key\' must be unique.', 'acf-component-manager' ); ?></p>
			</div>

			<?php if ( ! empty( $components ) ) : ?>
			<table class="form-table wp-list-table widefat striped">
				<thead>
				<tr>
					<th style="padding-left:10px;"><?php print __( 'Component', 'acf-component-manager' ); ?></th>
					<th><?php print __( 'Component source', 'acf-component-manager' ); ?></th>
					<th><?php print __( 'File name', 'acf-component-manager' ); ?></th>
					<th><?php print __( 'Key', 'acf-component-manager' ); ?></th>
					<th><?php print __( 'Enabled', 'acf-component-manager' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $components as $component => $component_properties ) : ?>
					<tr class="form-field form-required">
						<th class="row row-title" style="padding-left:10px">
							<label for="<?php print $component_properties['hash']; ?>">
								<?php print $component_properties['name']; ?>
							</label>
						</th>
						<td>
							<?php print $component_properties['source_name']; ?>
							<input
								type="hidden"
								name="source_id[<?php print $component_properties['hash']; ?>]"
								value="<?php print $component_properties['source_id']; ?>"
							>
							<input
								type="hidden"
								name="path[<?php print $component_properties['hash']; ?>]"
								value="<?php print $component_properties['path']; ?>"
							>
							<input
								type="hidden"
								name="source_name[<?php print $component_properties['hash']; ?>]"
								value="<?php print $component_properties['source_name']; ?>"
							>
							<input
								type="hidden"
								name="modified[<?php print $component_properties['hash'] ?? ''; ?>"
								value="<?php print $component_properties['modified'] ?? ''; ?>"
							>
						</td>
						<?php
						if ( isset( $component_properties['files'] ) ) {
							if ( count( $component_properties['files'] ) > 1 ) {
								?>
									<td colspan="3">
									<?php
										_e( 'There can be only one ACF JSON file per component, ', 'acf-component-manager' );
										print count( $component_properties['files'] );
										_e( ' files found.', 'acf-component-manager' );
									?>
									</td>
								<?php
							} elseif ( count( $component_properties['files'] ) == 1 ) {
								?>
									<td>
										<input
											type="hidden"
											name="file[<?php print $component_properties['hash']; ?>]"
											value="<?php print $component_properties['files'][0]['file_name']; ?>"
										>
										<?php print $component_properties['files'][0]['file_name']; ?>
									</td>
									<td>
										<input
											type="hidden"
											name="key[<?php print $component_properties['hash']; ?>]"
											value="<?php print $component_properties['files'][0]['key']; ?>"
										>
										<?php print $component_properties['files'][0]['key']; ?>
									</td>

									<td>
										<input
											type="checkbox"
											name="enabled[<?php print $component_properties['hash']; ?>]"
											id="<?php print $component_properties['hash']; ?>-enabled"
											value="1"
											<?php isset( $component_properties['stored']['enabled'] ) ? checked( $component_properties['stored']['enabled'] ) : print ''; ?>

										>
										<label for="<?php print $component_properties['hash']; ?>-enabled">
											<?php _e( 'Enabled', 'acf-component-manager' ); ?>
										</label>
									</td>
								<?php
							} else {
								?>
									<td colspan="3">
									<?php print __( 'No files found.  Export the ACF component and place in the active theme.', 'acf-component-manager' ); ?>
									</td>

								<?php
							}
						} else {
							?>
								<td colspan="3">
									<?php print __( 'No files found.  Export the ACF component and place in the active theme.', 'acf-component-manager' ); ?>
								</td>
							<?php
						}
						?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

					<?php submit_button( __( 'Save Components', 'acf-component-manager' ), 'primary', 'submit' ); ?>
					<input type="hidden" name="acf_component_manager_submit" value="1">

			<?php else : ?>
			<div class="warning"><?php _e( 'No components found.', 'topfloor-parcel' ); ?></div>
			<?php endif; ?>

		</form>
		<?php
	}
}
