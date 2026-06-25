<?php
/**
 * Datenbank-Speicher für Wunschlisten-Einträge.
 *
 * @package Kipphard\Wunschliste
 */

namespace Kipphard\Wunschliste;

defined( 'ABSPATH' ) || exit;

/**
 * Verwaltet die benutzerdefinierte Tabelle für Wunschlisten-Einträge.
 */
class Wishlist {

	/**
	 * Liefert das CREATE TABLE Statement für dbDelta.
	 *
	 * @return string
	 */
	public static function schema() {
		global $wpdb;
		$table           = Helpers::table();
		$charset_collate = $wpdb->get_charset_collate();

		// Doppeltes Leerzeichen nach Feldname ist dbDelta-Pflicht.
		return "CREATE TABLE {$table} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_key VARCHAR(64) NOT NULL DEFAULT '',
  product_id BIGINT(20) UNSIGNED NOT NULL,
  created DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY owner_key (owner_key)
) {$charset_collate};";
	}

	/**
	 * Legt die Tabelle an (oder aktualisiert sie via dbDelta).
	 */
	public static function create_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::schema() );
	}

	/**
	 * Liefert alle Einträge einer Wunschliste als Array von Produkt-IDs.
	 *
	 * @param string $owner Owner-Key.
	 * @return int[]
	 */
	public static function items( $owner ) {
		global $wpdb;
		$table = Helpers::table();

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT product_id FROM {$table} WHERE owner_key = %s ORDER BY id DESC",
				$owner
			)
		);

		return is_array( $rows ) ? array_map( 'absint', $rows ) : array();
	}

	/**
	 * Fügt ein Produkt zur Wunschliste hinzu. Duplikate werden verhindert.
	 *
	 * @param string $owner      Owner-Key.
	 * @param int    $product_id Produkt-ID.
	 * @return bool True bei Erfolg, false wenn bereits vorhanden oder DB-Fehler.
	 */
	public static function add( $owner, $product_id ) {
		global $wpdb;
		$table = Helpers::table();

		// Doppeleintrag verhindern.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE owner_key = %s AND product_id = %d LIMIT 1",
				$owner,
				$product_id
			)
		);

		if ( $existing ) {
			return false;
		}

		$inserted = $wpdb->insert(
			$table,
			array(
				'owner_key'  => $owner,
				'product_id' => $product_id,
				'created'    => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s' )
		);

		return false !== $inserted;
	}

	/**
	 * Entfernt ein Produkt aus der Wunschliste.
	 *
	 * @param string $owner      Owner-Key.
	 * @param int    $product_id Produkt-ID.
	 * @return bool True wenn gelöscht, false bei Fehler.
	 */
	public static function remove( $owner, $product_id ) {
		global $wpdb;
		$table = Helpers::table();

		$deleted = $wpdb->delete(
			$table,
			array(
				'owner_key'  => $owner,
				'product_id' => $product_id,
			),
			array( '%s', '%d' )
		);

		return false !== $deleted;
	}

	/**
	 * Prüft ob ein Produkt auf der Wunschliste steht.
	 *
	 * @param string $owner      Owner-Key.
	 * @param int    $product_id Produkt-ID.
	 * @return bool
	 */
	public static function has( $owner, $product_id ) {
		global $wpdb;
		$table = Helpers::table();

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE owner_key = %s AND product_id = %d LIMIT 1",
				$owner,
				$product_id
			)
		);

		return ! empty( $existing );
	}

	/**
	 * Gibt die Anzahl der Einträge einer Wunschliste zurück.
	 *
	 * @param string $owner Owner-Key.
	 * @return int
	 */
	public static function count( $owner ) {
		global $wpdb;
		$table = Helpers::table();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE owner_key = %s",
				$owner
			)
		);
	}

	/**
	 * Entfernt alle Einträge einer Wunschliste.
	 *
	 * @param string $owner Owner-Key.
	 */
	public static function clear( $owner ) {
		global $wpdb;
		$table = Helpers::table();

		$wpdb->delete(
			$table,
			array( 'owner_key' => $owner ),
			array( '%s' )
		);
	}

	/**
	 * Liefert die meistgewünschten Produkte (für Pro-Analytik).
	 *
	 * @param int $limit Maximale Anzahl Ergebnisse.
	 * @return array<int,array{product_id:int,cnt:int}>
	 */
	public static function most_wished( $limit = 10 ) {
		global $wpdb;
		$table = Helpers::table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, COUNT(*) AS cnt FROM {$table} GROUP BY product_id ORDER BY cnt DESC LIMIT %d",
				absint( $limit )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}
}
