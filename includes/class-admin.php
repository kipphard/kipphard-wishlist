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
		add_action( 'admin_post_wun_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Untermenüs unter WooCommerce registrieren.
	 */
	public function register_menus() {
		add_submenu_page(
			'woocommerce',
			__( 'Wunschliste – Einstellungen', 'wunschliste' ),
			__( 'Wunschliste', 'wunschliste' ),
			Helpers::CAP,
			WUN_SLUG . '-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Assets nur auf den Plugin-Seiten einbinden.
	 *
	 * @param string $hook Aktueller Admin-Seiten-Hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_' . WUN_SLUG . '-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'wun-admin',
			WUN_URL . 'assets/admin.css',
			array(),
			WUN_VERSION
		);
		wp_enqueue_script(
			'wun-admin',
			WUN_URL . 'assets/admin.js',
			array(),
			WUN_VERSION,
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
		Helpers::guard_post( 'wun_save_settings' );

		$clean = Helpers::sanitize_settings( $_POST );
		update_option( Helpers::OPT_SETTINGS, $clean );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => WUN_SLUG . '-settings',
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

		$notice       = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$is_pro       = Helpers::is_pro();
		$settings     = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$defaults     = Helpers::defaults();

		$button_label  = isset( $settings['button_label'] ) ? $settings['button_label'] : $defaults['button_label'];
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
			<h1><?php esc_html_e( 'Wunschliste – Einstellungen', 'wunschliste' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Einstellungen gespeichert.', 'wunschliste' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wun_save_settings">
				<?php wp_nonce_field( 'wun_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="wun-button-label"><?php esc_html_e( 'Button-Beschriftung', 'wunschliste' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-button-label" name="button_label" class="regular-text"
								value="<?php echo esc_attr( $button_label ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-remove-label"><?php esc_html_e( 'Entfernen-Beschriftung', 'wunschliste' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-remove-label" name="remove_label" class="regular-text"
								value="<?php echo esc_attr( $remove_label ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button anzeigen', 'wunschliste' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="show_on_loop" value="1" <?php checked( $show_on_loop ); ?>>
									<?php esc_html_e( 'In Produktlisten (Shop, Kategorie)', 'wunschliste' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="show_on_single" value="1" <?php checked( $show_on_single ); ?>>
									<?php esc_html_e( 'Auf Einzelprodukt-Seiten', 'wunschliste' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-page-id"><?php esc_html_e( 'Wunschlisten-Seite', 'wunschliste' ); ?></label>
						</th>
						<td>
							<select id="wun-page-id" name="page_id">
								<option value="0"><?php esc_html_e( '— Keine Seite ausgewählt —', 'wunschliste' ); ?></option>
								<?php foreach ( $pages as $p ) : ?>
									<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $page_id, $p->ID ); ?>>
										<?php echo esc_html( $p->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Seite mit dem Shortcode [wunschliste].', 'wunschliste' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-empty-text"><?php esc_html_e( 'Leer-Hinweis', 'wunschliste' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-empty-text" name="empty_text" class="large-text"
								value="<?php echo esc_attr( $empty_text ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-msg-added"><?php esc_html_e( 'Meldung: Hinzugefügt', 'wunschliste' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-msg-added" name="msg_added" class="large-text"
								value="<?php echo esc_attr( $msg_added ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-msg-removed"><?php esc_html_e( 'Meldung: Entfernt', 'wunschliste' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-msg-removed" name="msg_removed" class="large-text"
								value="<?php echo esc_attr( $msg_removed ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wun-msg-error"><?php esc_html_e( 'Fehlermeldung', 'wunschliste' ); ?></label>
						</th>
						<td>
							<input type="text" id="wun-msg-error" name="msg_error" class="large-text"
								value="<?php echo esc_attr( $msg_error ); ?>">
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Einstellungen speichern', 'wunschliste' ) ); ?>
			</form>

			<?php if ( $is_pro ) : ?>

				<hr>
				<div class="card wun-pro-settings" style="max-width:680px;padding:20px 24px;margin-top:20px;">
					<h2><?php esc_html_e( 'Pro-Funktionen', 'wunschliste' ); ?></h2>
					<p><?php esc_html_e( 'Wunschliste Pro ist aktiv.', 'wunschliste' ); ?></p>
					<ul>
						<li><?php esc_html_e( 'Wunschliste teilen: öffentlicher Link zum Teilen', 'wunschliste' ); ?></li>
						<li><?php esc_html_e( 'Variationsunterstützung: Wunschliste auf Variantenebene', 'wunschliste' ); ?></li>
						<li><?php esc_html_e( 'Analytik: meistgewünschte Produkte im Admin', 'wunschliste' ); ?></li>
					</ul>

					<?php if ( Helpers::is_pro() ) : ?>
						<h3><?php esc_html_e( 'Meistgewünschte Produkte', 'wunschliste' ); ?></h3>
						<?php
						$most_wished = Wishlist::most_wished( 10 );
						if ( empty( $most_wished ) ) :
							?>
							<p><?php esc_html_e( 'Noch keine Daten vorhanden.', 'wunschliste' ); ?></p>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped" style="max-width:480px;">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Produkt', 'wunschliste' ); ?></th>
										<th><?php esc_html_e( 'Einträge', 'wunschliste' ); ?></th>
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
					<?php endif; ?>
				</div>

			<?php else : ?>

				<hr>
				<div class="card wun-pro-teaser" style="max-width:680px;padding:20px 24px;margin-top:20px;background:#f6f7f7;border:1px dashed #a7aaad;">
					<h2><?php esc_html_e( 'Wunschliste Pro', 'wunschliste' ); ?></h2>
					<ul class="wun-pro-features">
						<li>
							<span class="dashicons dashicons-share"></span>
							<?php esc_html_e( 'Wunschliste teilen: öffentlicher Link für Freunde & Familie', 'wunschliste' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-networking"></span>
							<?php esc_html_e( 'Variationsunterstützung: Einträge auf Produktvariantenebene', 'wunschliste' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-chart-bar"></span>
							<?php esc_html_e( 'Analytik: meistgewünschte Produkte im Admin-Dashboard', 'wunschliste' ); ?>
						</li>
					</ul>
					<p>
						<a href="https://products.kipphard.com/wunschliste" target="_blank" rel="noopener noreferrer" class="button button-secondary">
							<?php esc_html_e( 'Jetzt upgraden', 'wunschliste' ); ?>
						</a>
					</p>
				</div>

			<?php endif; ?>

		</div>
		<?php
	}
}
