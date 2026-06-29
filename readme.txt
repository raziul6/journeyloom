=== WP Travel Machine ===
Contributors: wptravelmachine
Tags: travel, booking, hotel, trip, tourism
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress into a travel & hotel booking platform — trip packages, hotels, a smart booking engine, search, reviews and bank-transfer checkout.

== Description ==

**WP Travel Machine** transforms your WordPress site into a complete travel and hotel booking platform — trip packages and hotels, a step-by-step booking engine, powerful search, reviews, wishlist & compare, and bank-transfer/manual checkout. Everything you need to start selling trips is free.

= Free Features =

* **Trip Management** — Create trip packages with day-by-day itineraries, pricing tiers, galleries, maps and FAQs
* **Hotel Management** — Hotels with room types, amenities, star ratings and availability
* **Smart Booking Engine** — Step-by-step AJAX booking with availability checking and traveler details
* **Manual / Bank Transfer Payments** — Take bookings with configurable bank-transfer instructions
* **Advanced Search** — AJAX search with filters for destination, activity, date and budget, plus a drag-and-drop search-form builder
* **Wishlist & Compare** — Let visitors save favorites and compare trips/hotels side by side
* **Reviews & Ratings** — Built-in review system with moderation
* **Email Notifications** — Automated booking confirmation and status-update emails
* **Booking Manager** — A dedicated admin dashboard with a slide-in booking detail drawer
* **Gutenberg Blocks & Elementor Widgets** — Native blocks/widgets for trip grids, hotel grids, search forms and more
* **REST API** — Full REST API for headless/mobile integrations
* **Schema Markup** — Automatic SEO-friendly structured data for trips and hotels
* **Modern UI/UX** — Clean, responsive design with smooth animations

= WP Travel Machine Pro =

Upgrade to [WP Travel Machine Pro](https://wptravelmachine.com/pro/) to unlock:

* **AI Suite** — The AI Trip Builder writes a whole trip (description, highlights, itinerary, inclusions & FAQ) in one click; AI-drafted customer replies in the booking screen; an AI concierge chat, smart recommendations and natural-language search. Works with OpenAI, Anthropic (Claude), Groq, Gemini, OpenRouter, Ollama or any OpenAI-compatible endpoint.
* **Stripe & PayPal Checkout** — Accept online card and PayPal payments with SCA / 3-D Secure and server-side verification.
* **Printable Invoices** — Generate and print branded invoices for any booking.
* **Coupons & Discounts** — Percentage or fixed-amount coupons with usage limits and expiry.

Pro features are unlocked by installing the **WP Travel Machine Pro** add-on alongside this free plugin — there is nothing to migrate. See **Travel Machine → Upgrade** in the admin for a full comparison.

= Shortcodes =

* `[wptm_trips]` — Display trip grid (attrs: count, columns, destination, activity)
* `[wptm_hotels]` — Display hotel grid (attrs: count, columns)
* `[wptm_search_form]` — Display search form (attrs: style=horizontal|vertical)
* `[wptm_booking_form]` — Display booking form (attrs: id)
* `[wptm_destinations]` — Display destination grid (attrs: count)
* `[wptm_ai_chat]` — Display the AI chat widget (requires WP Travel Machine Pro)

== For Developers ==

WP Travel Machine is built to be theme-developer friendly. Integrate it three ways: template overrides, action/filter hooks, and helper functions.

= Template overrides =

Drop a file into `your-theme/wp-travel-machine/<path>` to override the plugin default (same path under `templates/`):

* `single-trip.php`, `single-hotel.php`, `archive-trip.php`, `archive-hotel.php`
* `partials/booking-form.php`, `partials/gallery-hero.php`, `partials/trip-card.php`, `partials/hotel-card.php`, `partials/search-form.php`, `partials/calendar.php`

In your own templates you can load any template/partial (theme-overridable) with:
`wptm_get_template( 'partials/trip-card.php', array( 'foo' => $bar ) );`

= Action hooks (extend without copying templates) =

Single trip: `wptm_before_single_trip`, `wptm_single_trip_before_content`, `wptm_single_trip_after_overview`, `wptm_single_trip_after_content`, `wptm_single_trip_before_sidebar`, `wptm_single_trip_after_sidebar`, `wptm_after_single_trip` — each passes the trip ID.

Single hotel: `wptm_before_single_hotel`, `wptm_single_hotel_before_content`, `wptm_single_hotel_after_content`, `wptm_single_hotel_before_sidebar`, `wptm_single_hotel_after_sidebar`, `wptm_after_single_hotel` — each passes the hotel ID.

Lifecycle: `wptm_loaded` (passes the Plugin instance), `wptm_booking_created`, `wptm_booking_status_changed`, `wptm_payment_completed`, `wptm_manual_payment_pending`.

Pro/licensing: `wptm_is_pro` (bool) — filter to programmatically toggle Pro features; `wptm_pro_upgrade_url` — change the upgrade/purchase URL.

Example:
`add_action( 'wptm_single_trip_after_content', function ( $trip_id ) { echo do_shortcode( '[related_trips id="' . $trip_id . '"]' ); } );`

= Filter hooks =

* `wptm_trip_post_type_args`, `wptm_hotel_post_type_args` — adjust CPT registration (slug, supports, archive…)
* `wptm_destination_taxonomy_args`, `wptm_destination_taxonomy_objects`, `wptm_activity_taxonomy_args`, `wptm_trip_type_taxonomy_args`
* `wptm_trips_query_args`, `wptm_hotels_query_args` — modify the WP_Query for the grid shortcodes
* `wptm_locate_template`, `wptm_template_args` — override resolved template path / passed variables
* `wptm_enqueue_fonts` (bool), `wptm_fonts_url` — disable the bundled (self-hosted) fonts or point to your own stylesheet
* `wptm_payment_gateways` — register custom payment gateways

= Re-skinning =

All front-end styling is driven by CSS custom properties on `:root` (e.g. `--wptm-primary`, `--wptm-font`, `--wptm-radius`). Override them in your theme to re-brand without touching plugin CSS.

= Helper functions =

`wptm_get_template()`, `wptm_locate_template()`, `wptm_format_price()`, `wptm_get_option()`, `wptm_is_feature_enabled()`, `wptm_get_page_url()`, `wptm_get_system_pages()`, `wptm_is_pro()`.

== External Services ==

This plugin connects to the following third-party services. Each is **optional** and only contacted when you enable and configure the related feature. The map service applies to the free plugin; the payment and AI services are part of **WP Travel Machine Pro** and are only contacted when that add-on is active and the feature is configured. No data is sent anywhere by default.

**OpenStreetMap** (free; only when a trip/hotel has a location map)
Map tiles are loaded from the OpenStreetMap tile servers (tile.openstreetmap.org) in the visitor's browser to display the location map. The Leaflet map library itself is bundled with the plugin (not loaded externally).
Terms / Tile Usage Policy: https://operations.osmfoundation.org/policies/tiles/ — Privacy: https://wiki.osmfoundation.org/wiki/Privacy_Policy

**Stripe** (Pro; only when the Stripe gateway is enabled and a customer pays by card)
Card payments are processed via Stripe. The Stripe.js library is loaded from js.stripe.com on the checkout page, and the booking amount, currency, order number and a payment token are sent to api.stripe.com to create and verify the charge. No card numbers touch your server.
Terms: https://stripe.com/legal — Privacy: https://stripe.com/privacy

**PayPal** (Pro; only when the PayPal gateway is enabled and a customer pays with PayPal)
PayPal payments are processed via PayPal. The PayPal JS SDK is loaded from paypal.com on the checkout page, and the booking amount, currency and order reference are sent to PayPal's REST API (api-m.paypal.com / api-m.sandbox.paypal.com) to create and capture the order.
Terms: https://www.paypal.com/legalhub — Privacy: https://www.paypal.com/privacy

**OpenAI** (Pro; only when AI features are enabled and you select OpenAI as the provider)
When you or a visitor use the AI features, the relevant query/content is sent to api.openai.com using the API key you provide.
Terms: https://openai.com/policies/terms-of-use — Privacy: https://openai.com/policies/privacy-policy

**Anthropic (Claude)** (Pro; only when AI features are enabled and you select Anthropic as the provider)
When you or a visitor use the AI features, the relevant query/content is sent to api.anthropic.com using the API key you provide.
Terms: https://www.anthropic.com/legal/consumer-terms — Privacy: https://www.anthropic.com/legal/privacy

== Installation ==

1. Upload the `wp-travel-machine` folder to `/wp-content/plugins/` (or install from the Plugins screen)
2. Activate the plugin through the 'Plugins' menu
3. Follow the setup wizard, then go to **Travel Machine → Settings** to configure
4. Start creating trips and hotels!

To unlock Pro features (AI Suite, Stripe & PayPal, invoices, coupons), install and activate the **WP Travel Machine Pro** add-on alongside this plugin.

== Frequently Asked Questions ==

= What's free and what's Pro? =
Everything for building and selling trips is free: trips & hotels, the booking engine, Manual / Bank Transfer payments, search, wishlist, compare, reviews, blocks, Elementor widgets, the REST API and email notifications. **Pro** adds the AI Suite (AI Trip Builder, customer-reply drafting, concierge chat, recommendations & smart search), Stripe & PayPal online checkout, printable invoices, and coupons. See **Travel Machine → Upgrade** for a side-by-side comparison.

= What payment gateways are supported? =
The free plugin includes Manual / Bank Transfer. **Stripe** and **PayPal** online checkout are part of WP Travel Machine Pro. Additional gateways can be added via the `wptm_payment_gateways` filter.

= How do I use the AI features? =
The AI Suite is part of WP Travel Machine Pro. With Pro active, go to Travel Machine → Settings → AI, enable AI and enter your provider API key (OpenAI, Anthropic, or any OpenAI-compatible endpoint).

= Can I customize the templates? =
Yes! Copy any file from the plugin's `templates/` folder into `your-theme/wp-travel-machine/` (keeping the same path) and it will be used instead. Examples: `your-theme/wp-travel-machine/single-trip.php`, or just a partial like `your-theme/wp-travel-machine/partials/booking-form.php`. You can also extend the templates without copying them using the action hooks listed in the Developers section.

== Changelog ==

= 1.0.2 =
* Free / Pro split: the AI Suite, Stripe & PayPal checkout, invoices and coupons are now part of the optional WP Travel Machine Pro add-on; the free plugin keeps trips, hotels, the booking engine, Manual / Bank Transfer, search, reviews, wishlist, compare, blocks, Elementor and the REST API
* Pro features are hidden in the free plugin and unlock automatically when the Pro add-on is active (no settings to migrate)
* Added a single "Upgrade" page with a Free vs Pro comparison
* New developer filters: `wptm_is_pro`, `wptm_pro_upgrade_url`
* Stripe: added a webhook endpoint so bookings are reliably marked paid even if the customer closes the tab
* New: customers receive a payment-received email when their payment completes

= 1.0.1 =
* New: branded full-screen setup wizard on activation (currency, email, system pages, payment methods)
* Stripe: full SCA / 3-D Secure card payments via PaymentIntents (inline Stripe Elements card field, server-side intent verification)
* PayPal: complete Smart Buttons checkout with server-side order create + capture and amount verification
* Online charges are verified on the server before a booking is marked paid
* wp.org compliance: bundled the Leaflet map library and Sora + Plus Jakarta Sans fonts locally (no third-party CDN); only payment SDKs load from their required official domains
* Added an External Services disclosure to the readme
* Renamed font filters: wptm_enqueue_google_fonts -> wptm_enqueue_fonts, wptm_google_fonts_url -> wptm_fonts_url

= 1.0.0 =
* Initial release
* Trip and Hotel custom post types
* Booking engine with cart system
* Manual payment gateway, plus Stripe & PayPal
* AI recommendations, smart search, chat assistant
* Gutenberg blocks
* Search form builder
* Coupon system
* Email notifications
* REST API
* Schema markup

== Upgrade Notice ==

= 1.0.2 =
Introduces the free/Pro split. All existing free functionality stays free; AI, Stripe/PayPal, invoices and coupons move to the optional WP Travel Machine Pro add-on.
