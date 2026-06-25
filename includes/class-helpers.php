<?php
/**
 * Gemeinsame Hilfsmethoden: Rechte, Optionen, Sanitisierung, Owner-Key.
 *
 * @package Kipphard\Wunschliste
 */

namespace Kipphard\Wunschliste;

defined( 'ABSPATH' ) || exit;

/**
 * Zustandslose Hilfsmethoden, die im gesamten Plugin genutzt werden.
 */
class Helpers {

	/** Erforderliche Berechtigung für alle Admin-Aktionen. */
	const CAP = 'manage_options';

	/** Options-Key für die Plugin-Einstellungen. */
	const OPT_SETTINGS = 'wun_settings';

	/** Cookie-Name für den Gast-Token. */
	const COOKIE_NAME = 'wun_token';

	/**
	 * Prüft ob die Pro-Lizenz aktiv ist. Standardmäßig false.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return (bool) apply_filters( 'wun_is_pro', defined( 'WUN_PRO' ) && WUN_PRO );
	}

	/**
	 * Gibt den vollständigen Tabellennamen zurück.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'wun_items';
	}

	/**
	 * Liefert die Standard-Einstellungen des Plugins.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'button_label'  => 'Auf die Wunschliste',
			'remove_label'  => 'Von der Wunschliste entfernen',
			'show_on_loop'  => true,
			'show_on_single' => true,
			'page_id'       => 0,
			'empty_text'    => 'Deine Wunschliste ist leer.',
			'msg_added'     => 'Zur Wunschliste hinzugefügt.',
			'msg_removed'   => 'Von der Wunschliste entfernt.',
			'msg_error'     => 'Es ist ein Fehler aufgetreten. Bitte versuche es erneut.',
		);
	}

	/**
	 * Liest eine einzelne Einstellung (mit Fallback auf den Standardwert).
	 *
	 * @param string $key Einstellungsschlüssel.
	 * @return mixed
	 */
	public static function get( $key ) {
		$settings = (array) get_option( self::OPT_SETTINGS, array() );
		$defaults = self::defaults();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : null );
	}

	/**
	 * Sanitisiert die Einstellungsfelder streng pro Feld.
	 *
	 * @param array<string,mixed> $raw Rohe $_POST-Daten.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( array $raw ) {
		$defaults = self::defaults();

		$page_id = isset( $raw['page_id'] ) ? absint( $raw['page_id'] ) : 0;

		return array(
			'button_label'   => isset( $raw['button_label'] ) ? sanitize_text_field( wp_unslash( $raw['button_label'] ) ) : $defaults['button_label'],
			'remove_label'   => isset( $raw['remove_label'] ) ? sanitize_text_field( wp_unslash( $raw['remove_label'] ) ) : $defaults['remove_label'],
			'show_on_loop'   => ! empty( $raw['show_on_loop'] ),
			'show_on_single' => ! empty( $raw['show_on_single'] ),
			'page_id'        => $page_id,
			'empty_text'     => isset( $raw['empty_text'] ) ? sanitize_text_field( wp_unslash( $raw['empty_text'] ) ) : $defaults['empty_text'],
			'msg_added'      => isset( $raw['msg_added'] ) ? sanitize_text_field( wp_unslash( $raw['msg_added'] ) ) : $defaults['msg_added'],
			'msg_removed'    => isset( $raw['msg_removed'] ) ? sanitize_text_field( wp_unslash( $raw['msg_removed'] ) ) : $defaults['msg_removed'],
			'msg_error'      => isset( $raw['msg_error'] ) ? sanitize_text_field( wp_unslash( $raw['msg_error'] ) ) : $defaults['msg_error'],
		);
	}

	/**
	 * Prüft einen Admin-POST-Request: Berechtigung + Nonce. Bricht bei Fehler ab.
	 *
	 * @param string $action Nonce-Aktion.
	 * @param string $field  Nonce-Feldname.
	 */
	public static function guard_post( $action, $field = '_wpnonce' ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'wunschliste' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action, $field );
	}

	/**
	 * Gibt den Owner-Key des aktuellen Besuchers zurück.
	 * Eingeloggte Nutzer: 'user:<id>'; Gäste: 'guest:<token>' (Cookie gesetzt/gelesen).
	 *
	 * @return string
	 */
	public static function current_owner() {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			return 'user:' . $user_id;
		}

		// Gast: Token aus Cookie lesen oder neu anlegen.
		$token = '';
		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$token = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
			// Nur alphanumerische Zeichen zulassen (wp_generate_password gibt nur alnum zurück).
			if ( ! preg_match( '/^[a-zA-Z0-9]{20}$/', $token ) ) {
				$token = '';
			}
		}

		if ( empty( $token ) ) {
			$token = wp_generate_password( 20, false );
			setcookie(
				self::COOKIE_NAME,
				$token,
				array(
					'expires'  => time() + ( 90 * DAY_IN_SECONDS ),
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
			$_COOKIE[ self::COOKIE_NAME ] = $token;
		}

		return 'guest:' . $token;
	}
}
