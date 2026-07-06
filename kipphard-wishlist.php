<?php
/**
 * Plugin Name:       Kipphard Wishlist for WooCommerce
 * Plugin URI:        https://kipphard.com/products/wunschliste
 * Description:       Lets customers save WooCommerce products to a persistent wishlist — for logged-in users and guests (secure cookie). Heart button on shop and product pages; dedicated wishlist page via the `[kipphard_wishlist]` shortcode.
 * Version:           0.4.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            André Kipphard
 * Author URI:        https://kipphard.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kipphard-wishlist
 * Domain Path:       /languages
 *
 * @package Kipphard\Wunschliste
 */

defined( 'ABSPATH' ) || exit;

define( 'KIPPHARD_WISHLIST_VERSION', '0.4.0' );
define( 'KIPPHARD_WISHLIST_FILE', __FILE__ );
define( 'KIPPHARD_WISHLIST_DIR', plugin_dir_path( __FILE__ ) );
define( 'KIPPHARD_WISHLIST_URL', plugin_dir_url( __FILE__ ) );
define( 'KIPPHARD_WISHLIST_SLUG', 'kipphard-wishlist' );

/**
 * Minimaler PSR-4-Autoloader für den Kipphard\Wunschliste\-Namespace.
 * Kipphard\Wunschliste\Foo_Bar → includes/class-foo-bar.php
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kipphard\\Wunschliste\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		$path     = KIPPHARD_WISHLIST_DIR . 'includes/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

// Shared design system (kip-ui). Injected into the build at /shared by build-zip;
// guarded so the plugin still runs unstyled if it's absent.
$kipphard_wishlist_shared_autoload = KIPPHARD_WISHLIST_DIR . 'shared/autoload.php';
if ( is_readable( $kipphard_wishlist_shared_autoload ) ) {
	require_once $kipphard_wishlist_shared_autoload;
}

register_activation_hook( __FILE__, array( '\Kipphard\Wunschliste\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Kipphard\Wunschliste\Plugin::instance()->boot();
	}
);
