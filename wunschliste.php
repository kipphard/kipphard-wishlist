<?php
/**
 * Plugin Name:       Wunschliste – Wishlist für WooCommerce
 * Plugin URI:        https://products.kipphard.com/wunschliste
 * Description:       Ermöglicht Kunden, WooCommerce-Produkte auf einer Wunschliste zu speichern – für eingeloggte Nutzer und Gäste (Cookie). Saubere UX, ehrlicher Umfang.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            André Kipphard
 * Author URI:        https://kipphard.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wunschliste
 * Domain Path:       /languages
 *
 * @package Kipphard\Wunschliste
 */

defined( 'ABSPATH' ) || exit;

define( 'WUN_VERSION', '0.1.0' );
define( 'WUN_FILE', __FILE__ );
define( 'WUN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WUN_URL', plugin_dir_url( __FILE__ ) );
define( 'WUN_SLUG', 'wunschliste' );

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
		$path     = WUN_DIR . 'includes/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( '\Kipphard\Wunschliste\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Kipphard\Wunschliste\Plugin::instance()->boot();
	}
);
