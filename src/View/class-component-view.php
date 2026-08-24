<?php
/**
 * Component manager view.
 *
 * @package acf-component-manager
 *
 * @since 0.0.1
 */

declare( strict_types=1 );

namespace AcfComponentManager\View;

// If called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Contains components view.
 */
class ComponentView extends ViewBase {

	/**
	 * Provides the ComponentView view.
	 *
	 * @param array $managed_components    An array of theme components.
	 * @param array $unmanaged_components  An array of components not managed.
	 * @param array $missing_components    An array of database components not in code.
	 *
	 * @return void
	 */
	public function view( array $managed_components, array $unmanaged_components, array $missing_components ): void {

		if ( empty( $managed_components ) && empty( $unmanaged_components ) ) {
			print '<p>' . __( 'No components found.', 'acf-component-manager' ) . '</p>';
		} else {
			$this->update_action( 'edit' );
			?>
			<a href="<?php print $this->get_form_url(); ?>" class="button">
				<?php print __( 'Edit components', 'acf-component-manager' ); ?>
			</a>
			<?php
		}

		if ( ! empty( $managed_components ) ) {
			print '<h3>' . __( 'Managed components', 'acf-component-manager' ) . '</h3>';
			print '<p>' . __( 'Components currently managed.', 'acf-component-manager' ) . '</p>';
			?>
			<table class="widefat">
				<thead>
				<tr>
					<th>
						<h3><?php print __( 'Component', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Source', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'File name', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Field group key', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Auto sync', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Sync date', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Enabled', 'acf-component-manager' ); ?></h3>
					</th>
				</tr>
				</thead>
				<tbody>
			<?php foreach ( $managed_components as $component ) : ?>
				<tr>
					<td class="row-title">
						<?php print $component['name']; ?>
					</td>
					<td>
						<?php print $component['source_name']; ?>
					</td>
					<td>
						<?php print $component['file']; ?>
					</td>
					<td>
						<?php print $component['key']; ?>
					</td>
					<td>

					</td>
					<td>
						<?php
						print $component['auto_sync'] ? __( 'Enabled', 'acf-component-manager' ) : '';
						?>
					</td>
					<td>
						<?php
						print $component['enabled'] ? __( 'Enabled', 'acf-component-manager' ) : '';
						?>
					</td>
				</tr>
			<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		if ( ! empty( $unmanaged_components ) ) {
			print '<h3>' . __( 'Unmanaged components', 'acf-component-manager' ) . '</h3>';
			print '<p>' . __( 'Components discovered in a configured source but not currently being managed.  Typically resolved by saving the \'Edit components\' form', 'acf-component-manager' ) . '</p>';
			?>
			<table class="widefat">
				<thead>
				<tr>
					<th><h3><?php print __( 'Component', 'acf-component-manager' ); ?></h3></th>
					<th><h3><?php print __( 'Source', 'acf-component-manager' ); ?></h3></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $unmanaged_components as $component ) : ?>
					<tr>
						<td class="row-title">
							<?php print $component['name']; ?>
						</td>
						<td>
							<?php print $component['source_name']; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		if ( ! empty( $missing_components ) ) {
			print '<h3>' . __( 'Missing components', 'acf-component-manager' ) . '</h3>';
			print '<p>' . __( 'ACF components in the database but not found in the configured sources.', 'acf-component-manager' ) . '</p>';
			?>
			<table class="widefat">
				<thead>
				<tr>
					<th>
						<h3><?php print __( 'Component name', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Field group key', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Status', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Modified', 'acf-component-manager' ); ?></h3>
					</th>
					<th>
						<h3><?php print __( 'Post id', 'acf-component-manager' ); ?></h3>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $missing_components as $component ) : ?>
				<tr>
					<td class="row-title">
						<?php print $component['name']; ?>
					</td>
					<td>
						<?php print $component['key']; ?>
					</td>
					<td>
						<?php print $component['status']; ?>
					</td>
					<td>
						<?php print $component['modified']; ?>
					</td>
					<td>
						<?php print $component['id']; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}
	}

	/**
	 * Dashboard.
	 *
	 * @since 0.0.1
	 * @param array $enabled_components The components that are currently enabled.
	 */
	public function dashboard( array $enabled_components ) {
		if ( empty( $enabled_components ) ) {
			print '<p>' . __( 'No enabled components.', 'acf-component-manager' ) . '</p>';
		} else {
			print '<h3>' . __( 'Enabled components', 'acf-component-manager' ) . '</h3>';
			?>
			<table class="widefat">
				<thead>
				<tr>
					<th>
						<?php print __( 'Component', 'acf-component-manager' ); ?>
					</th>
					<th>
						<?php print __( 'Source', 'acf-component-manager' ); ?>
					</th>
					<th>
						<?php print __( 'File name', 'acf-component-manager' ); ?>
					</th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ( $enabled_components as $component ) : ?>
						<tr>
							<td class="row-title">
								<?php if ( isset( $component['name'] ) ) : ?>
									<?php print $component['name']; ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( isset( $component['source_name'] ) ) : ?>
									<?php print $component['source_name']; ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( isset( $component['file'] ) ) : ?>
									<?php print $component['file']; ?>
								<?php endif; ?>
							</td>

						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}
	}
}
