<?php
/**
 * Plugin-Deinstallation: Option und benutzerdefinierte Tabelle entfernen.
 *
 * @package Kipphard\Wunschliste
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'wun_settings' );
delete_option( 'wun_share_tokens' );

$table = $wpdb->prefix . 'wun_items';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
