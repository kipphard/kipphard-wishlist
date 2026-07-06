<?php
/**
 * Plugin-Deinstallation: Option und benutzerdefinierte Tabelle entfernen.
 *
 * @package Kipphard\Wunschliste
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'kipphard_wishlist_settings' );
delete_option( 'kipphard_wishlist_share_tokens' );

$table = $wpdb->prefix . 'kipphard_wishlist_items';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
