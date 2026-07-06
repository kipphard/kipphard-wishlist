=== Kipphard Wishlist for WooCommerce ===
Contributors: kipphard
Tags: wishlist, woocommerce, save for later, ecommerce, wunschliste
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Heart button on shop and product pages. Persistent wishlist for logged-in users and guests. Dedicated page via `[kipphard_wishlist]` shortcode.

== Description ==

**Kipphard Wishlist for WooCommerce** adds a heart button to product loops and single-product pages so customers can save items for later. The wishlist survives page refreshes and return visits — for logged-in users it is stored in the database; guests are identified by a secure cookie (HttpOnly, SameSite=Lax, 90 days). A dedicated wishlist page is rendered by the `[kipphard_wishlist]` shortcode with grid, list, or table layout.

**What this plugin does:**

* Heart "Add to Wishlist" button injected into WooCommerce product loops and single-product pages
* Dedicated wishlist page via the `[kipphard_wishlist]` shortcode — grid, list, and table display layouts available
* Persistent storage: logged-in users via a custom database table (no post-meta overhead); guests via a secure HttpOnly cookie
* Add any wishlist item directly to the cart without leaving the wishlist page
* Duplicate prevention — adding an item that is already on the list is silently ignored
* Customisable button labels, messages, and empty-state text from the admin settings screen
* WooCommerce dependency check: an admin notice is shown if WooCommerce is not active

**Free vs. Pro:**

This plugin is fully functional with no locked features. A separate **Kipphard Wishlist Pro** plugin adds public wishlist sharing (a shareable link for friends and family) as an optional add-on: https://kipphard.com/products/wunschliste

**What this plugin does NOT do:**

This plugin does not inject overlays, trackers, or any external requests. Everything runs locally on your own server.


*Hinweis (DE): Dieses Plugin fügt WooCommerce-Produktseiten und -listen einen Herz-Button hinzu. Kunden können Produkte auf einer Wunschliste speichern und später über eine eigene Seite (Shortcode `[kipphard_wishlist]`) aufrufen, in den Warenkorb legen oder entfernen. Eingeloggte Nutzer erhalten eine datenbankgestützte Wunschliste; Gäste werden über ein sicheres Cookie identifiziert. Die Benutzeroberfläche ist auf Deutsch verfügbar.*

== Installation ==

1. Upload the `kipphard-wishlist` folder to `/wp-content/plugins/`, or install it from the Plugins screen in your WordPress admin.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. WooCommerce must be installed and activated.
4. Create a page, add the shortcode `[kipphard_wishlist]`, and assign it under **WooCommerce → Wishlist → Settings**.
5. Go to **WooCommerce → Wishlist** to configure button labels, display layout, and messages.

== Frequently Asked Questions ==

= Is WooCommerce required? =

Yes. The plugin requires WooCommerce and displays an admin notice if it is not active.

= Does the wishlist work for guests who are not logged in? =

Yes. Guests are identified via a secure cookie (HttpOnly, SameSite=Lax, 90-day lifetime). The cookie is set server-side when the first item is added.

= Which shortcode renders the wishlist page? =

Use `[kipphard_wishlist]` on any WordPress page. The plugin renders the customer's current wishlist with Add-to-Cart and Remove buttons.

= Can customers add items directly to the cart from the wishlist? =

Yes. Each wishlist entry has an "Add to cart" button that adds the product to the WooCommerce cart. The item stays on the wishlist after adding to cart.

= Is data removed when the plugin is uninstalled? =

Yes. On uninstall, plugin settings and the wishlist database table are removed completely.

= Does the plugin send any data to external servers? =

No. All data stays on your own server. No analytics, no external requests, no tracking.

== Screenshots ==

1. Heart button on a WooCommerce product loop — added and not-added states.
2. The wishlist page rendered by `[kipphard_wishlist]` with grid layout, Add-to-Cart, and Remove buttons.
3. Admin settings screen: wishlist page assignment, button labels, and display layout.

== Changelog ==

= 0.4.0 =
* Renamed to Kipphard Wishlist for WooCommerce. Unique `kipphard_wishlist_` prefixes for all options, hooks, and the database table; shortcode renamed to `[kipphard_wishlist]`. Removed license-gated UI — the free plugin has no locked features; wishlist sharing is available as a separate Pro plugin.

= 0.3.1 =
* English plugin title and WordPress.org-ready readme. No functional changes.
* Plugin URI canonicalised to kipphard.com/products/wunschliste.

= 0.3.0 =
* Shared Kipphard design system (kip-ui) rolled out; appearance settings translated to German (de_DE).

= 0.2.6 =
* Minor bug fixes and translation string corrections.

= 0.2.5 =
* Admin settings: display layout selector (grid / list / table).

= 0.2.4 =
* Performance: wishlist table query optimised; duplicate-check moved server-side.

= 0.2.3 =
* Guest cookie lifetime extended to 90 days; SameSite=Lax hardened.

= 0.2.2 =
* Add-to-cart from wishlist: quantity field added.

= 0.2.1 =
* Empty-state customisation: configurable message and optional illustration.

= 0.2.0 =
* English source baseline with a German (de_DE) translation; kip-ui reference wiring.

= 0.1.0 =
* Initial release.
* Heart button on product loops and single-product pages.
* Wishlist page via `[kipphard_wishlist]` shortcode.
* Persistent storage: logged-in users via custom DB table, guests via secure cookie.
* Add-to-cart from the wishlist page.
* Duplicate prevention.
* Admin settings: button labels, messages, empty-state text, wishlist page assignment.
* WooCommerce dependency check with admin notice.

== Upgrade Notice ==

= 0.4.0 =
Renamed to Kipphard Wishlist for WooCommerce; the free version is fully functional with no locked features.
