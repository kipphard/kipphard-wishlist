<?php
/**
 * WordPress-Admin-UI: Einstellungsseite und POST-Handler.
 *
 * @package Kipphard\Wunschliste
 */

namespace Kipphard\Wunschliste;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert Admin-Menüs und verarbeitet Formularabsendungen.
 */
class Admin {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_kipphard_wishlist_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Untermenüs unter WooCommerce registrieren.
	 */
	public function register_menus() {
		add_submenu_page(
			'woocommerce',
			__( 'Wishlist – Settings', 'kipphard-wishlist' ),
			__( 'Wishlist', 'kipphard-wishlist' ),
			Helpers::CAP,
			KIPPHARD_WISHLIST_SLUG . '-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Assets nur auf den Plugin-Seiten einbinden.
	 *
	 * @param string $hook Aktueller Admin-Seiten-Hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_' . KIPPHARD_WISHLIST_SLUG . '-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'kipphard-wishlist-admin',
			KIPPHARD_WISHLIST_URL . 'assets/admin.css',
			array(),
			KIPPHARD_WISHLIST_VERSION
		);
		wp_enqueue_script(
			'kipphard-wishlist-admin',
			KIPPHARD_WISHLIST_URL . 'assets/admin.js',
			array(),
			KIPPHARD_WISHLIST_VERSION,
			true
		);
	}

	// -------------------------------------------------------------------------
	// POST-Handler
	// -------------------------------------------------------------------------

	/**
	 * Einstellungen speichern.
	 */
	public function handle_save_settings() {
		Helpers::guard_post( 'kipphard_wishlist_save_settings' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in Helpers::guard_post() above.
		$clean = Helpers::sanitize_settings( $_POST );
		update_option( Helpers::OPT_SETTINGS, $clean );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => KIPPHARD_WISHLIST_SLUG . '-settings',
					'notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Seiten-Renderer
	// -------------------------------------------------------------------------

	/**
	 * Einstellungsseite rendern.
	 */
	public function render_settings() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display value, no state change.
		$notice         = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$sharing_active = class_exists( __NAMESPACE__ . '\\Sharing' );
		$settings       = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$defaults       = Helpers::defaults();

		$button_label  = isset( $settings['button_label'] ) ? $settings['button_label'] : $defaults['button_label'];
		$in_list_label = isset( $settings['in_list_label'] ) ? $settings['in_list_label'] : $defaults['in_list_label'];
		$button_style  = isset( $settings['button_style'] ) ? $settings['button_style'] : $defaults['button_style'];
		$remove_label  = isset( $settings['remove_label'] ) ? $settings['remove_label'] : $defaults['remove_label'];
		$show_on_loop  = isset( $settings['show_on_loop'] ) ? (bool) $settings['show_on_loop'] : $defaults['show_on_loop'];
		$show_on_single = isset( $settings['show_on_single'] ) ? (bool) $settings['show_on_single'] : $defaults['show_on_single'];
		$page_id       = isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : $defaults['page_id'];
		$empty_text    = isset( $settings['empty_text'] ) ? $settings['empty_text'] : $defaults['empty_text'];
		$msg_added     = isset( $settings['msg_added'] ) ? $settings['msg_added'] : $defaults['msg_added'];
		$msg_removed   = isset( $settings['msg_removed'] ) ? $settings['msg_removed'] : $defaults['msg_removed'];
		$msg_error     = isset( $settings['msg_error'] ) ? $settings['msg_error'] : $defaults['msg_error'];

		// Seitenauswahl: alle veröffentlichten Seiten.
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="wrap wun-wrap">
			<h1><?php esc_html_e( 'Wishlist – Settings', 'kipphard-wishlist' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'kipphard-wishlist' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="kipphard_wishlist_save_settings">
				<?php wp_nonce_field( 'kipphard_wishlist_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="wun-button-label"><?php esc_html_e( 'Button label', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-button-label" name="button_label" class="regular-text"
								value="<?php echo esc_attr( $button_label ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-in-list-label"><?php esc_html_e( 'In-wishlist label', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-in-list-label" name="in_list_label" class="regular-text"
								value="<?php echo esc_attr( $in_list_label ); ?>">
							<p class="description"><?php esc_html_e( 'Shown once a product has been added (button style).', 'kipphard-wishlist' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-button-style"><?php esc_html_e( 'Button style', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<select id="wun-button-style" name="button_style">
								<option value="button" <?php selected( $button_style, 'button' ); ?>><?php esc_html_e( 'Text button', 'kipphard-wishlist' ); ?></option>
								<option value="icon" <?php selected( $button_style, 'icon' ); ?>><?php esc_html_e( 'Heart icon on the product image', 'kipphard-wishlist' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-remove-label"><?php esc_html_e( 'Remove label', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-remove-label" name="remove_label" class="regular-text"
								value="<?php echo esc_attr( $remove_label ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show button', 'kipphard-wishlist' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="show_on_loop" value="1" <?php checked( $show_on_loop ); ?>>
									<?php esc_html_e( 'In product lists (shop, category)', 'kipphard-wishlist' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="show_on_single" value="1" <?php checked( $show_on_single ); ?>>
									<?php esc_html_e( 'On single product pages', 'kipphard-wishlist' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-page-id"><?php esc_html_e( 'Wishlist page', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<select id="wun-page-id" name="page_id">
								<option value="0"><?php esc_html_e( '— No page selected —', 'kipphard-wishlist' ); ?></option>
								<?php foreach ( $pages as $p ) : ?>
									<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $page_id, $p->ID ); ?>>
										<?php echo esc_html( $p->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Page containing the [kipphard_wishlist] shortcode.', 'kipphard-wishlist' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-empty-text"><?php esc_html_e( 'Empty notice', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-empty-text" name="empty_text" class="large-text"
								value="<?php echo esc_attr( $empty_text ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-msg-added"><?php esc_html_e( 'Message: Added', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-msg-added" name="msg_added" class="large-text"
								value="<?php echo esc_attr( $msg_added ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-msg-removed"><?php esc_html_e( 'Message: Removed', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-msg-removed" name="msg_removed" class="large-text"
								value="<?php echo esc_attr( $msg_removed ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-msg-error"><?php esc_html_e( 'Error message', 'kipphard-wishlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-msg-error" name="msg_error" class="large-text"
								value="<?php echo esc_attr( $msg_error ); ?>">
						</td>
					</tr>
				</table>

				<?php if ( class_exists( '\Kipphard\Shared\Appearance' ) ) : ?>
					<h2 class="title"><?php esc_html_e( 'Appearance', 'kipphard-wishlist' ); ?></h2>
					<p class="description" style="margin-bottom:8px;">
						<?php esc_html_e( 'Make the wishlist match your brand in seconds — or switch it off to inherit your theme completely.', 'kipphard-wishlist' ); ?>
					</p>
					<table class="form-table" role="presentation">
						<?php \Kipphard\Shared\Appearance::render_fields( $settings ); ?>
					</table>
				<?php endif; ?>

				<?php submit_button( __( 'Save settings', 'kipphard-wishlist' ) ); ?>
			</form>

			<?php if ( $sharing_active ) : ?>

				<hr>
				<div class="card wun-pro-settings" style="max-width:680px;padding:20px 24px;margin-top:20px;">
					<h2><?php esc_html_e( 'Sharing', 'kipphard-wishlist' ); ?></h2>
					<p><?php esc_html_e( 'Wishlist sharing is active — customers can generate a public link to their wishlist.', 'kipphard-wishlist' ); ?></p>

					<h3><?php esc_html_e( 'Most-wished products', 'kipphard-wishlist' ); ?></h3>
					<?php
					$most_wished = Wishlist::most_wished( 10 );
					if ( empty( $most_wished ) ) :
						?>
						<p><?php esc_html_e( 'No data available yet.', 'kipphard-wishlist' ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped" style="max-width:480px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Product', 'kipphard-wishlist' ); ?></th>
									<th><?php esc_html_e( 'Entries', 'kipphard-wishlist' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $most_wished as $row ) : ?>
									<?php
									$pid      = absint( $row['product_id'] );
									$product  = wc_get_product( $pid );
									$pname    = $product ? $product->get_name() : sprintf( '#%d', $pid );
									$edit_url = $product ? get_edit_post_link( $pid ) : '';
									?>
									<tr>
										<td>
											<?php if ( $edit_url ) : ?>
												<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $pname ); ?></a>
											<?php else : ?>
												<?php echo esc_html( $pname ); ?>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( $row['cnt'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

			<?php else : ?>

				<p class="description" style="margin-top:20px;">
					<?php esc_html_e( 'Looking for wishlist sharing?', 'kipphard-wishlist' ); ?>
					<a href="https://kipphard.com/products/wunschliste" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'See Wishlist Pro.', 'kipphard-wishlist' ); ?></a>
				</p>

			<?php endif; ?>

		</div>
		<?php
	}
}
