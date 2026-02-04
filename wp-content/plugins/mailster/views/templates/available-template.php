<?php

$class = array( 'mailster-box' );
if ( $data['update'] ) {
	$class[] = 'update';
}
if ( $data['is_feature'] ) {
	$class[] = 'is-feature';
}
if ( $data['is_free'] ) {
	$class[] = 'is-free';
}
if ( $data['hidden'] ) {
	$class[] = 'hidden';
}
if ( $unsupported = version_compare( $data['requires'], MAILSTER_VERSION, '>' ) ) {
	$class[] = 'is-unsupported';
}

?>
	<li class="<?php echo implode( ' ', $class ) ?>" id="template-<?php echo $slug ?>" data-id="<?php echo esc_attr( $slug ) ?>" data-support="<?php echo esc_attr( sprintf( esc_html__( 'This Template requires at least version %s of Mailster.', 'mailster' ), $data['requires'] ) ); ?>">
		<a class="external screenshot" title="<?php echo $data['name'] . ' ' . $data['new_version'] . ' ' . esc_attr__( 'by', 'mailster' ) . ' ' . $data['author'] ?>" <?php echo ! empty( $data['uri'] ) ? 'href="' . esc_url( $data['uri'] ) . '" ' : '' ?> data-slug="<?php echo esc_attr( $slug ) ?>">
				<img alt="" src="<?php echo esc_url( $data['image'] ) ?>" width="300" height="225">
		</a>
		<div class="meta">
			<h3><?php echo esc_html( $data['name'] ) ?> <span class="version"><?php echo esc_html( $data['new_version'] ) ?> <span class="installed-version">(<?php esc_html_e( 'your version', 'mailster' );?>: <?php echo $data['version'] ?>)</span></span></h3>
			<?php if ( $data['author'] ) : ?>
			<div>
				<?php esc_html_e( 'by', 'mailster' );?> <?php if ( ! empty( $data['author_profile'] ) ) : ?><a class="external" href="<?php echo esc_url( $data['author_profile'] ) ?>"><?php echo esc_html( $data['author'] ) ?></a><?php else : ?> <?php echo esc_html( $data['author'] ) ?><?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<div class="description">
		<?php if ( isset( $data['description'] ) ) : ?><p><?php echo $data['description'] ?></p><?php endif; ?>
		</div>
		<?php if ( ! $unsupported ) : ?>
		<div class="action-links">
			<ul>
				<?php if ( $data['is_free'] ) : ?>

					<?php if ( ! $data['is_free'] ) : ?>

					<?php endif; ?>

					<?php if ( in_array( $slug, array_keys( $templates ) ) ) : ?>
						<li class="alignright">
							<a title="<?php esc_html_e( 'update template', 'mailster' );?>" class="update button button-primary" href="edit.php?post_type=newsletter&page=mailster_templates&action=update&template=<?php echo $slug ?>&_wpnonce=<?php echo wp_create_nonce( 'download-' . $slug ) ?>">
							<?php if ( $data['update'] && $updates ) : ?>
								<?php echo sprintf( esc_html__( 'Update to %s', 'mailster' ), $data['new_version'] ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Download', 'mailster' ); ?>
							<?php endif; ?>
							</a>
						</li>
					<?php else : ?>

						<li class="alignright"><a title="<?php esc_html_e( 'download template', 'mailster' );?>" class="download button button-primary" href="edit.php?post_type=newsletter&page=mailster_templates&action=download&template=<?php echo $slug ?>&_wpnonce=<?php echo wp_create_nonce( 'download-' . $slug ) ?>"><?php esc_html_e( 'Download', 'mailster' );?></a></li>

					<?php endif; ?>

				<?php elseif ( isset( $data['uri'] ) ) :?>

					<?php if ( isset( $data['envato_item_id'] ) ) : ?>
						<?php if ( ! isset( $templates[ $slug ] ) && isset( $data['endpoint'] ) && ! empty( $data['endpoint'] ) ) : ?>

						<li>
							<a title="<?php esc_html_e( 'download via Envato', 'mailster' );?>" class="envato-activate button" href="<?php echo add_query_arg( array( 'auth' => wp_create_nonce( 'envato-activate' ), 'item_id' => $data['envato_item_id'], 'slug' => $slug, 'returnto' => urlencode( admin_url( 'edit.php?post_type=newsletter&page=mailster_templates' ) ) ), $data['endpoint'] ) ?>" data-slug="<?php echo $slug ?>"><?php esc_html_e( 'Download', 'mailster' );?></a>
						</li>

						<?php endif; ?>
					<?php endif; ?>

					<?php if ( isset( $data['envato_item_id'] ) && isset( $templates[ $slug ] ) ) : ?>
					<li class="alignright">
						<a title="<?php esc_html_e( 'activate on Envato', 'mailster' );?>" class="envato-activate update button button-primary" href="<?php echo add_query_arg( array( 'auth' => wp_create_nonce( 'envato-activate' ), 'item_id' => $data['envato_item_id'], 'slug' => $slug, 'returnto' => urlencode( admin_url( 'edit.php?post_type=newsletter&page=mailster_templates' ) ) ), $data['endpoint'] ) ?>" data-slug="<?php echo $slug ?>">
							<?php if ( $data['update'] && $updates ) : ?>
								<?php echo sprintf( esc_html__( 'Update to %s', 'mailster' ), $data['new_version'] ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Download', 'mailster' ); ?>
							<?php endif; ?>
						</a>
					</li>
					<?php else : ?>

					<li class="alignright">
						<a class="external purchase button button-primary" href="<?php echo esc_url( $data['uri'] ); ?>"><?php esc_html_e( 'Get this Template', 'mailster' );?></a>
					</li>

					<?php endif; ?>

				<?php endif; ?>
			</ul>
		</div>
		<?php endif; ?>
		<div class="loader"></div>
	</li>
