/* Wunschliste – Frontend JS */
( function () {
	'use strict';

	var data = window.wunData || {};

	// Aktive Produkt-IDs als Set für schnellen Lookup (als Zahlen).
	var activeIds = {};
	if ( data.itemIds && Array.isArray( data.itemIds ) ) {
		data.itemIds.forEach( function ( id ) {
			activeIds[ parseInt( id, 10 ) ] = true;
		} );
	}

	/**
	 * Zeigt eine kurze Status-Meldung (toast) am unteren Bildschirmrand.
	 *
	 * @param {string}  message Anzuzeigender Text.
	 * @param {boolean} success True = Erfolg, false = Fehler.
	 */
	function showNotice( message, success ) {
		var notice = document.getElementById( 'wun-notice' );
		if ( ! notice ) {
			notice = document.createElement( 'div' );
			notice.id = 'wun-notice';
			notice.className = 'wun-notice';
			document.body.appendChild( notice );
		}

		notice.textContent = message;
		notice.className   = 'wun-notice ' + ( success ? 'wun-success' : 'wun-error' );

		// Kurz warten, dann einblenden (CSS-Transition).
		setTimeout( function () {
			notice.classList.add( 'wun-notice-visible' );
		}, 10 );

		// Nach 3 Sekunden ausblenden.
		clearTimeout( notice._hideTimer );
		notice._hideTimer = setTimeout( function () {
			notice.classList.remove( 'wun-notice-visible' );
		}, 3000 );
	}

	/**
	 * Aktualisiert alle Buttons für ein Produkt.
	 *
	 * @param {number}  productId Produkt-ID.
	 * @param {boolean} inList    True wenn auf der Wunschliste.
	 */
	function updateButtons( productId, inList ) {
		var buttons = document.querySelectorAll( '.wun-toggle-btn[data-product-id="' + productId + '"]' );
		buttons.forEach( function ( btn ) {
			if ( inList ) {
				btn.classList.add( 'wun-active' );
				btn.setAttribute( 'aria-pressed', 'true' );
			} else {
				btn.classList.remove( 'wun-active' );
				btn.setAttribute( 'aria-pressed', 'false' );
			}
		} );
	}

	/**
	 * Sendet eine AJAX-Anfrage an den WP-Backend.
	 *
	 * @param {string}   action     AJAX-Action.
	 * @param {number}   productId  Produkt-ID.
	 * @param {Function} callback   Callback mit (success, data).
	 */
	function doAjax( action, productId, callback ) {
		var params = 'action=' + encodeURIComponent( action )
			+ '&nonce=' + encodeURIComponent( data.nonce || '' )
			+ '&product_id=' + encodeURIComponent( productId );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', data.ajaxUrl, true );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		xhr.onreadystatechange = function () {
			if ( xhr.readyState !== 4 ) {
				return;
			}
			try {
				var result = JSON.parse( xhr.responseText );
				callback( result.success, result.data || {} );
			} catch ( ex ) {
				callback( false, {} );
			}
		};
		xhr.send( params );
	}

	/**
	 * Verarbeitet den Klick auf den Toggle-Button.
	 *
	 * @param {Event} e Click-Event.
	 */
	function handleToggle( e ) {
		var btn       = e.currentTarget;
		var productId = parseInt( btn.getAttribute( 'data-product-id' ), 10 );

		if ( ! productId ) {
			return;
		}

		btn.disabled = true;

		doAjax( 'wun_toggle', productId, function ( success, result ) {
			btn.disabled = false;

			if ( success ) {
				var inList = !! result.in;
				activeIds[ productId ] = inList ? true : undefined;
				updateButtons( productId, inList );

				var i18n   = data.i18n || {};
				var msg    = inList ? ( i18n.added || '' ) : ( i18n.removed || '' );
				showNotice( msg, true );
			} else {
				var i18n  = data.i18n || {};
				showNotice( i18n.error || 'Fehler', false );
			}
		} );
	}

	/**
	 * Verarbeitet den Klick auf den Entfernen-Button in der Wunschliste.
	 *
	 * @param {Event} e Click-Event.
	 */
	function handleRemove( e ) {
		var btn       = e.currentTarget;
		var productId = parseInt( btn.getAttribute( 'data-product-id' ), 10 );
		var item      = btn.closest( '.wun-wishlist-item' );

		if ( ! productId ) {
			return;
		}

		btn.disabled = true;

		doAjax( 'wun_remove', productId, function ( success ) {
			btn.disabled = false;

			if ( success ) {
				if ( item ) {
					item.style.transition = 'opacity 0.3s';
					item.style.opacity    = '0';
					setTimeout( function () {
						item.parentNode && item.parentNode.removeChild( item );
					}, 300 );
				}
				delete activeIds[ productId ];
				updateButtons( productId, false );

				var i18n = data.i18n || {};
				showNotice( i18n.removed || '', true );
			} else {
				var i18n = data.i18n || {};
				showNotice( i18n.error || 'Fehler', false );
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		// Toggle-Buttons initialisieren.
		var toggleBtns = document.querySelectorAll( '.wun-toggle-btn' );
		toggleBtns.forEach( function ( btn ) {
			var productId = parseInt( btn.getAttribute( 'data-product-id' ), 10 );
			// Initialzustand sicherstellen (serverseitig bereits gesetzt, aber JS-State synchronisieren).
			if ( activeIds[ productId ] ) {
				btn.classList.add( 'wun-active' );
				btn.setAttribute( 'aria-pressed', 'true' );
			}
			btn.addEventListener( 'click', handleToggle );
		} );

		// Entfernen-Buttons in der Wunschlisten-Seite initialisieren.
		var removeBtns = document.querySelectorAll( '.wun-remove-btn' );
		removeBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', handleRemove );
		} );
	} );
}() );
