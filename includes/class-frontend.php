<?php
/**
 * Frontend-Hooks: Wunschliste-Button, AJAX-Handler und Shortcode.
 *
 * @package Kipphard\Wunschliste
 */

namespace Kipphard\Wunschliste;

defined( 'ABSPATH' ) || exit;

/**
 * Rendert den Wunschliste-Button, verarbeitet AJAX-Anfragen und den Shortcode.
 */
class Frontend {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		if ( Helpers::get( 'show_on_loop' ) ) {
			if ( 'icon' === Helpers::get( 'button_style' ) ) {
				// Heart overlaid on the product image (fires at the start of each <li>).
				add_action( 'woocommerce_before_shop_loop_item', array( $this, 'render_button' ), 9 );
			} else {
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_button' ), 15 );
			}
		}

		if ( Helpers::get( 'show_on_single' ) ) {
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_button' ) );
		}

		add_action( 'wp_ajax_kipphard_wishlist_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_kipphard_wishlist_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_kipphard_wishlist_remove', array( $this, 'ajax_remove' ) );
		add_action( 'wp_ajax_nopriv_kipphard_wishlist_remove', array( $this, 'ajax_remove' ) );

		add_shortcode( 'kipphard_wishlist', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Assets einbinden – auf Produktseiten und der Wunschlisten-Seite.
	 */
	public function enqueue_assets() {
		$page_id = (int) Helpers::get( 'page_id' );
		$load    = is_product() || is_shop() || is_product_category() || is_product_tag();
		if ( ! $load && $page_id > 0 ) {
			$load = is_page( $page_id );
		}

		if ( ! $load ) {
			return;
		}

		// Shared design system (kip-ui) — load it + the user's token overrides first,
		// then the wishlist layout CSS on top (it reads the kip-ui variables).
		$settings = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$has_kip  = class_exists( '\Kipphard\Shared\Appearance' );
		$styled   = ! $has_kip || \Kipphard\Shared\Appearance::is_enabled( $settings );
		$deps     = array();

		if ( $has_kip && $styled && is_readable( KIPPHARD_WISHLIST_DIR . 'shared/kip-ui.css' ) ) {
			wp_enqueue_style( 'kip-ui', KIPPHARD_WISHLIST_URL . 'shared/kip-ui.css', array(), KIPPHARD_WISHLIST_VERSION );
			wp_add_inline_style( 'kip-ui', \Kipphard\Shared\Appearance::css( $settings, '.kip-ui.kip-wishlist' ) );
			$deps[] = 'kip-ui';
		}

		wp_enqueue_style(
			'kipphard-wishlist-frontend',
			KIPPHARD_WISHLIST_URL . 'assets/frontend.css',
			$deps,
			KIPPHARD_WISHLIST_VERSION
		);

		wp_enqueue_script(
			'kipphard-wishlist-frontend',
			KIPPHARD_WISHLIST_URL . 'assets/frontend.js',
			array(),
			KIPPHARD_WISHLIST_VERSION,
			true
		);

		$owner   = Helpers::current_owner();
		$item_ids = Wishlist::items( $owner );

		wp_localize_script(
			'kipphard-wishlist-frontend',
			'wunData',
			array(
				'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'kipphard_wishlist_action' ),
				'itemIds' => $item_ids,
				'i18n'    => array(
					'added'     => Helpers::get( 'msg_added' ),
					'removed'   => Helpers::get( 'msg_removed' ),
					'error'     => Helpers::get( 'msg_error' ),
					'button'    => Helpers::get( 'button_label' ),
					'in_list'   => Helpers::get( 'in_list_label' ),
					'remove'    => Helpers::get( 'remove_label' ),
					'view_list' => __( 'View wishlist', 'kipphard-wishlist' ),
				),
			)
		);
	}

	/**
	 * Rendert den „Auf die Wunschliste"-Button.
	 * Kontextabhängig: im Loop wird die Produkt-ID über $product ermittelt, auf Einzelseiten über global.
	 */
	public function render_button() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_id  = absint( $product->get_id() );
		$owner       = Helpers::current_owner();
		$in_wishlist = Wishlist::has( $owner, $product_id );
		$add_label   = (string) Helpers::get( 'button_label' );
		$in_label    = (string) Helpers::get( 'in_list_label' );
		$is_icon     = ( 'icon' === Helpers::get( 'button_style' ) );
		$active      = $in_wishlist ? ' wun-active' : '';
		$aria        = $in_wishlist ? 'true' : 'false';
		$cur_label   = $in_wishlist ? $in_label : $add_label;

		if ( $is_icon ) {
			?>
			<button
				type="button"
				class="wun-toggle-btn wun-toggle-btn--icon<?php echo esc_attr( $active ); ?>"
				data-product-id="<?php echo esc_attr( $product_id ); ?>"
				aria-pressed="<?php echo esc_attr( $aria ); ?>"
				aria-label="<?php echo esc_attr( $cur_label ); ?>"
				title="<?php echo esc_attr( $cur_label ); ?>"
			><span class="wun-heart" aria-hidden="true"><?php echo self::heart_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span></button>
			<?php
			return;
		}
		?>
		<button
			type="button"
			class="wun-toggle-btn<?php echo esc_attr( $active ); ?>"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			aria-pressed="<?php echo esc_attr( $aria ); ?>"
		>
			<span class="wun-heart" aria-hidden="true"><?php echo self::heart_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
			<span class="wun-btn-label" data-add="<?php echo esc_attr( $add_label ); ?>" data-added="<?php echo esc_attr( $in_label ); ?>"><?php echo esc_html( $cur_label ); ?></span>
		</button>
		<?php
	}

	/**
	 * Inline heart SVG. Outline by default; filled when its button is .wun-active
	 * (via --wun-heart-fill in CSS).
	 *
	 * @return string
	 */
	private static function heart_svg() {
		return '<svg class="wun-heart-svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
			. '<path d="M12 20.7l-1.45-1.32C5.4 14.74 2 11.65 2 7.95 2 5.2 4.16 3 6.9 3c1.54 0 3.04.72 4 1.86l1.1 1.3 1.1-1.3A5.27 5.27 0 0 1 17.1 3C19.84 3 22 5.2 22 7.95c0 3.7-3.4 6.79-8.55 11.43L12 20.7z" '
			. 'fill="var(--wun-heart-fill,none)" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';
	}

	/**
	 * AJAX: Produkt zur Wunschliste hinzufügen oder entfernen (Toggle).
	 */
	public function ajax_toggle() {
		check_ajax_referer( 'kipphard_wishlist_action', 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in check_ajax_referer() above.
		$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );

		if ( $product_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'kipphard-wishlist' ) ), 400 );
		}

		$post = get_post( $product_id );
		if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'kipphard-wishlist' ) ), 400 );
		}

		$owner = Helpers::current_owner();

		if ( Wishlist::has( $owner, $product_id ) ) {
			Wishlist::remove( $owner, $product_id );
			$in = false;
		} else {
			Wishlist::add( $owner, $product_id );
			$in = true;
		}

		$pid          = (int) Helpers::get( 'page_id' );
		$redirect_url = $pid > 0 ? get_permalink( $pid ) : '';

		wp_send_json_success(
			array(
				'in'           => $in,
				'count'        => Wishlist::count( $owner ),
				'redirect_url' => $redirect_url,
			)
		);
	}

	/**
	 * AJAX: Produkt explizit von der Wunschliste entfernen (z.B. aus der Wunschlisten-Seite).
	 */
	public function ajax_remove() {
		check_ajax_referer( 'kipphard_wishlist_action', 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in check_ajax_referer() above.
		$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );

		if ( $product_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'kipphard-wishlist' ) ), 400 );
		}

		$owner = Helpers::current_owner();
		Wishlist::remove( $owner, $product_id );

		wp_send_json_success(
			array(
				'in'    => false,
				'count' => Wishlist::count( $owner ),
			)
		);
	}

	/**
	 * Shortcode [kipphard_wishlist]: Gibt die gespeicherten Produkte des aktuellen Besuchers aus.
	 *
	 * @return string HTML-Ausgabe.
	 */
	public function render_shortcode() {
		$owner        = Helpers::current_owner();
		$item_ids     = Wishlist::items( $owner );
		$empty_text   = esc_html( Helpers::get( 'empty_text' ) );
		$remove_label = esc_html( Helpers::get( 'remove_label' ) );

		// Scoped design wrapper + chosen layout (graceful when kip-ui is absent/off).
		$settings   = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$styled     = class_exists( '\Kipphard\Shared\Appearance' ) && \Kipphard\Shared\Appearance::is_enabled( $settings );
		$wrap_class = $styled ? 'kip-ui kip-wishlist wun-wishlist-wrap' : 'wun-wishlist-wrap';
		$layout     = $styled ? \Kipphard\Shared\Appearance::layout( $settings ) : 'grid';
		$kip_atts   = $styled ? \Kipphard\Shared\Appearance::data_atts( $settings ) : '';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrap_class ); ?>"<?php echo $kip_atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in Appearance::data_atts(). ?>>
			<?php if ( empty( $item_ids ) ) : ?>
				<p class="wun-empty kip-empty"><?php echo $empty_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></p>
			<?php else : ?>
				<ul class="wun-wishlist" data-layout="<?php echo esc_attr( $layout ); ?>">
					<?php foreach ( $item_ids as $product_id ) : ?>
						<?php
						$product_id = absint( $product_id );
						$product    = wc_get_product( $product_id );
						if ( ! $product || ! $product->is_visible() ) {
							continue;
						}
						$product_url  = get_permalink( $product_id );
						$product_name = $product->get_name();
						$thumbnail    = $product->get_image( 'woocommerce_thumbnail' );
						?>
						<li class="wun-item kip-card" data-product-id="<?php echo esc_attr( $product_id ); ?>">
							<a class="wun-item__thumb" href="<?php echo esc_url( $product_url ); ?>">
								<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC image markup. ?>
							</a>
							<div class="wun-item__body">
								<h3 class="wun-item__name">
									<a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_name ); ?></a>
								</h3>
								<div class="wun-item__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
								<div class="wun-item__actions">
									<?php echo do_shortcode( '[add_to_cart id="' . absint( $product_id ) . '" show_price="false" style=""]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC shortcode output. ?>
									<button
										type="button"
										class="wun-remove kip-btn kip-btn--ghost kip-btn--sm"
										data-product-id="<?php echo esc_attr( $product_id ); ?>"
									><?php echo $remove_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></button>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
