=== Wunschliste – Wishlist für WooCommerce ===
Contributors: andrekipphard
Tags: woocommerce, wishlist, wunschliste, save for later, e-commerce
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ermöglicht Kunden, WooCommerce-Produkte auf einer Wunschliste zu speichern – für eingeloggte Nutzer und Gäste. / Lets customers save WooCommerce products to a wishlist – for logged-in users and guests.

== Description ==

**Deutsch:**

„Wunschliste" fügt WooCommerce-Produktseiten und -listen einen „Auf die Wunschliste"-Button hinzu. Kunden können Produkte speichern und später über eine eigene Wunschlisten-Seite (Shortcode `[wunschliste]`) aufrufen, in den Warenkorb legen oder entfernen.

Eingeloggte Nutzer erhalten eine nutzergebundene Wunschliste; Gäste werden über ein sicheres Cookie (HttpOnly, SameSite=Lax) erkannt.

**Funktionen (Free):**

* „Auf die Wunschliste"-Button in Produktlisten und auf Einzelprodukt-Seiten
* Wunschlisten-Seite via Shortcode `[wunschliste]`
* Persistenz: eingeloggte Nutzer per Account, Gäste per Cookie
* Direkt aus der Wunschliste in den Warenkorb legen
* Anpassbare Button-Beschriftungen, Meldungen und Leer-Hinweis
* Duplikatschutz (kein doppeltes Speichern)
* Benutzerdefinierte Datenbanktabelle (kein Overhead durch Post-Meta)

**Englisch:**

"Wunschliste" (Wishlist) adds a "Add to Wishlist" button to WooCommerce product pages and shop loops. Customers can save products and view, add-to-cart, or remove them later via a dedicated wishlist page (shortcode `[wunschliste]`).

Logged-in users get a user-bound wishlist; guests are identified via a secure cookie (HttpOnly, SameSite=Lax).

**Features (Free):**

* "Add to Wishlist" button on product loops and single product pages
* Wishlist page via shortcode `[wunschliste]`
* Persistence: logged-in users via account, guests via cookie
* Add directly to cart from the wishlist
* Customisable button labels, messages and empty state text
* Duplicate prevention
* Custom database table (no post-meta overhead)

== Pro Version ==

Wunschliste Pro adds:

* Share wishlist: public shareable link for friends & family
* Variation support: wishlist entries at product variant level
* Analytics: most-wished products dashboard in admin

Upgrade at: https://products.kipphard.com/wunschliste

== Installation ==

1. Upload the `wunschliste` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. WooCommerce must be installed and activated.
4. Create a page and add the shortcode `[wunschliste]`.
5. Go to WooCommerce → Wunschliste to configure labels and settings.

== Frequently Asked Questions ==

= Wird WooCommerce benötigt? / Is WooCommerce required? =

Ja. Das Plugin setzt WooCommerce voraus und zeigt einen Admin-Hinweis wenn WooCommerce nicht aktiv ist.
Yes. The plugin requires WooCommerce and shows an admin notice if it is not active.

= Funktioniert die Wunschliste für nicht eingeloggte Gäste? / Does the wishlist work for guests? =

Ja. Gäste werden über ein sicheres Cookie (HttpOnly, SameSite=Lax, 90 Tage) identifiziert. Beim Login geht die Gast-Wunschliste nicht automatisch auf das Konto über (Pro-Funktion in Planung).
Yes. Guests are identified via a secure cookie (HttpOnly, SameSite=Lax, 90 days). Merging the guest wishlist on login is a planned Pro feature.

= Werden Daten beim Deinstallieren entfernt? / Is data removed on uninstall? =

Ja. Beim Deinstallieren werden die Plugin-Einstellungen und die Wunschlisten-Tabelle vollständig entfernt.
Yes. On uninstall, the plugin settings and wishlist table are completely removed.

== Changelog ==

= 0.1.0 =
* Erste Veröffentlichung / Initial release.

== Upgrade Notice ==

= 0.1.0 =
Erste Version. / First version.
