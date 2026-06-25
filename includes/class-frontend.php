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
			add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_button' ), 15 );
		}

		if ( Helpers::get( 'show_on_single' ) ) {
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_button' ) );
		}

		add_action( 'wp_ajax_wun_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_wun_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_wun_remove', array( $this, 'ajax_remove' ) );
		add_action( 'wp_ajax_nopriv_wun_remove', array( $this, 'ajax_remove' ) );

		add_shortcode( 'wunschliste', array( $this, 'render_shortcode' ) );
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

		wp_enqueue_style(
			'wun-frontend',
			WUN_URL . 'assets/frontend.css',
			array(),
			WUN_VERSION
		);

		wp_enqueue_script(
			'wun-frontend',
			WUN_URL . 'assets/frontend.js',
			array(),
			WUN_VERSION,
			true
		);

		$owner   = Helpers::current_owner();
		$item_ids = Wishlist::items( $owner );

		wp_localize_script(
			'wun-frontend',
			'wunData',
			array(
				'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'wun_action' ),
				'itemIds' => $item_ids,
				'i18n'    => array(
					'added'   => Helpers::get( 'msg_added' ),
					'removed' => Helpers::get( 'msg_removed' ),
					'error'   => Helpers::get( 'msg_error' ),
					'button'  => Helpers::get( 'button_label' ),
					'remove'  => Helpers::get( 'remove_label' ),
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

		$product_id   = absint( $product->get_id() );
		$owner        = Helpers::current_owner();
		$in_wishlist  = Wishlist::has( $owner, $product_id );
		$button_label = esc_html( Helpers::get( 'button_label' ) );
		$active_class = $in_wishlist ? ' wun-active' : '';
		$aria_pressed = $in_wishlist ? 'true' : 'false';
		?>
		<button
			type="button"
			class="wun-toggle-btn<?php echo esc_attr( $active_class ); ?>"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			aria-pressed="<?php echo esc_attr( $aria_pressed ); ?>"
		>
			<span class="wun-heart" aria-hidden="true">&#10084;</span>
			<span class="wun-btn-label"><?php echo $button_label; ?></span>
		</button>
		<?php
	}

	/**
	 * AJAX: Produkt zur Wunschliste hinzufügen oder entfernen (Toggle).
	 */
	public function ajax_toggle() {
		check_ajax_referer( 'wun_action', 'nonce' );

		$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );

		if ( $product_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiges Produkt.', 'wunschliste' ) ), 400 );
		}

		$post = get_post( $product_id );
		if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiges Produkt.', 'wunschliste' ) ), 400 );
		}

		$owner = Helpers::current_owner();

		if ( Wishlist::has( $owner, $product_id ) ) {
			Wishlist::remove( $owner, $product_id );
			$in = false;
		} else {
			Wishlist::add( $owner, $product_id );
			$in = true;
		}

		wp_send_json_success(
			array(
				'in'    => $in,
				'count' => Wishlist::count( $owner ),
			)
		);
	}

	/**
	 * AJAX: Produkt explizit von der Wunschliste entfernen (z.B. aus der Wunschlisten-Seite).
	 */
	public function ajax_remove() {
		check_ajax_referer( 'wun_action', 'nonce' );

		$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );

		if ( $product_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Ungültiges Produkt.', 'wunschliste' ) ), 400 );
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
	 * Shortcode [wunschliste]: Gibt die gespeicherten Produkte des aktuellen Besuchers aus.
	 *
	 * @return string HTML-Ausgabe.
	 */
	public function render_shortcode() {
		$owner      = Helpers::current_owner();
		$item_ids   = Wishlist::items( $owner );
		$empty_text = esc_html( Helpers::get( 'empty_text' ) );
		$remove_label = esc_html( Helpers::get( 'remove_label' ) );

		ob_start();

		if ( empty( $item_ids ) ) {
			echo '<p class="wun-empty">' . $empty_text . '</p>';
			return ob_get_clean();
		}
		?>
		<div class="wun-wishlist-wrap">
			<ul class="wun-wishlist-list">
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
					<li class="wun-wishlist-item" data-product-id="<?php echo esc_attr( $product_id ); ?>">
						<div class="wun-item-thumb">
							<a href="<?php echo esc_url( $product_url ); ?>">
								<?php echo $thumbnail; ?>
							</a>
						</div>
						<div class="wun-item-details">
							<h3 class="wun-item-name">
								<a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_name ); ?></a>
							</h3>
							<div class="wun-item-price">
								<?php echo wp_kses_post( $product->get_price_html() ); ?>
							</div>
							<div class="wun-item-actions">
								<?php
								echo wc_get_template_html(
									'loop/add-to-cart.php',
									array( 'product' => $product )
								);
								?>
								<button
									type="button"
									class="wun-remove-btn"
									data-product-id="<?php echo esc_attr( $product_id ); ?>"
								><?php echo $remove_label; ?></button>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php

		return ob_get_clean();
	}
}
