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
	function showNotice( message, success, linkUrl, linkLabel ) {
		var notice = document.getElementById( 'wun-notice' );
		if ( ! notice ) {
			notice = document.createElement( 'div' );
			notice.id = 'wun-notice';
			notice.className = 'wun-notice';
			document.body.appendChild( notice );
		}

		notice.innerHTML = '';
		// 'kip-ui' on the element itself so the design tokens resolve (the toast lives
		// on <body>, outside the scoped wrapper).
		notice.className = 'kip-ui kip-notice ' + ( success ? 'kip-notice--success' : 'kip-notice--error' );

		var textNode = document.createTextNode( message );
		notice.appendChild( textNode );

		if ( linkUrl && linkLabel ) {
			var link = document.createElement( 'a' );
			link.href = linkUrl;
			link.textContent = linkLabel;
			link.className = 'kip-notice__link';
			notice.appendChild( link );
		}

		// Kurz warten, dann einblenden (CSS-Transition).
		setTimeout( function () {
			notice.classList.add( 'is-visible' );
		}, 10 );

		// Auto-hide; keep it up longer when an actionable link is shown so it can be clicked.
		clearTimeout( notice._hideTimer );
		notice._hideTimer = setTimeout( function () {
			notice.classList.remove( 'is-visible' );
		}, ( linkUrl && linkLabel ) ? 6000 : 3000 );
	}

	/**
	 * Aktualisiert alle Buttons für ein Produkt.
	 *
	 * @param {number}  productId Produkt-ID.
	 * @param {boolean} inList    True wenn auf der Wunschliste.
	 */
	function updateButtons( productId, inList ) {
		var i18n    = data.i18n || {};
		var buttons = document.querySelectorAll( '.wun-toggle-btn[data-product-id="' + productId + '"]' );
		buttons.forEach( function ( btn ) {
			btn.classList.toggle( 'wun-active', inList );
			btn.setAttribute( 'aria-pressed', inList ? 'true' : 'false' );

			// Button style: swap the visible label ("Add to wishlist" ↔ "In wishlist").
			var label = btn.querySelector( '.wun-btn-label' );
			if ( label ) {
				var add   = label.getAttribute( 'data-add' );
				var added = label.getAttribute( 'data-added' );
				if ( add && added ) {
					label.textContent = inList ? added : add;
				}
			}

			// Icon style: update the accessible label/tooltip.
			if ( btn.classList.contains( 'wun-toggle-btn--icon' ) ) {
				var al = inList ? ( i18n.in_list || '' ) : ( i18n.button || '' );
				if ( al ) {
					btn.setAttribute( 'aria-label', al );
					btn.setAttribute( 'title', al );
				}
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

		doAjax( 'kipphard_wishlist_toggle', productId, function ( success, result ) {
			btn.disabled = false;

			if ( success ) {
				var inList = !! result.in;
				activeIds[ productId ] = inList ? true : undefined;
				updateButtons( productId, inList );

				var i18n      = data.i18n || {};
				var msg       = inList ? ( i18n.added || '' ) : ( i18n.removed || '' );
				var linkUrl   = ( inList && result.redirect_url ) ? result.redirect_url : '';
				var linkLabel = linkUrl ? ( i18n.view_list || '' ) : '';
				showNotice( msg, true, linkUrl, linkLabel );
			} else {
				var i18n = data.i18n || {};
				showNotice( i18n.error || 'Error', false );
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
		var item      = btn.closest( '.wun-item' );

		if ( ! productId ) {
			return;
		}

		btn.disabled = true;

		doAjax( 'kipphard_wishlist_remove', productId, function ( success ) {
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
				showNotice( i18n.error || 'Error', false );
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
		var removeBtns = document.querySelectorAll( '.wun-remove' );
		removeBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', handleRemove );
		} );

		// Icon style: anchor each heart to its own product cell so the overlay sits on
		// the image (theme-agnostic — the loop hook makes the heart the cell's first child).
		document.querySelectorAll( '.wun-toggle-btn--icon' ).forEach( function ( btn ) {
			var cell = btn.parentElement;
			if ( cell && window.getComputedStyle( cell ).position === 'static' ) {
				cell.style.position = 'relative';
			}
		} );
	} );
}() );
