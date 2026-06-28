=== WP Travel Machine ===
Contributors: wptravelmachine
Tags: travel, booking, hotel, trip, tourism, ai, travel-booking
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern, AI-powered travel & hotel booking system for WordPress with advanced booking engine, payment integrations, and stunning UI/UX.

== Description ==

**WP Travel Machine** is a comprehensive travel and hotel booking plugin that transforms your WordPress site into a powerful travel booking platform.

= Key Features =

* **Trip Management** — Create and manage trip packages with itineraries, pricing tiers, galleries, and maps
* **Hotel Management** — Manage hotels with room types, amenities, star ratings, and availability
* **Smart Booking Engine** — Step-by-step AJAX booking with availability checking and traveler management
* **Payment Gateways** — Built-in support for Stripe, PayPal, and manual/bank transfer
* **AI-Powered Features** — Trip recommendations, smart NLP search, itinerary generation, and AI chat assistant
* **Advanced Search** — AJAX-powered search with filters for destination, activity, date, and budget
* **Wishlist & Compare** — Let users save favorites and compare trips/hotels side by side
* **Reviews & Ratings** — User review system with moderation
* **Coupon System** — Create discount coupons with usage limits and expiry dates
* **Email Notifications** — Automated booking confirmation and cancellation emails
* **REST API** — Full REST API for headless/mobile integrations
* **Gutenberg Blocks** — Native blocks for trip grid, hotel grid, search form, and more
* **Schema Markup** — Automatic SEO-friendly structured data for trips and hotels
* **Modern UI/UX** — Glassmorphism design with smooth animations and responsive layout

= Shortcodes =

* `[wptm_trips]` — Display trip grid (attrs: count, columns, destination, activity)
* `[wptm_hotels]` — Display hotel grid (attrs: count, columns)
* `[wptm_search_form]` — Display search form (attrs: style=horizontal|vertical)
* `[wptm_booking_form]` — Display booking form (attrs: id)
* `[wptm_destinations]` — Display destination grid (attrs: count)
* `[wptm_ai_chat]` — Display AI chat widget

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

`wptm_get_template()`, `wptm_locate_template()`, `wptm_format_price()`, `wptm_get_option()`, `wptm_is_feature_enabled()`, `wptm_get_page_url()`, `wptm_get_system_pages()`.

== External Services ==

This plugin connects to the following third-party services. Each is **optional** and only contacted when you enable and configure the related feature. No data is sent anywhere by default.

**Stripe** (only when the Stripe gateway is enabled and a customer pays by card)
Card payments are processed via Stripe. The Stripe.js library is loaded from js.stripe.com on the checkout page, and the booking amount, currency, order number and a payment token are sent to api.stripe.com to create and verify the charge. No card numbers touch your server.
Terms: https://stripe.com/legal — Privacy: https://stripe.com/privacy

**PayPal** (only when the PayPal gateway is enabled and a customer pays with PayPal)
PayPal payments are processed via PayPal. The PayPal JS SDK is loaded from paypal.com on the checkout page, and the booking amount, currency and order reference are sent to PayPal's REST API (api-m.paypal.com / api-m.sandbox.paypal.com) to create and capture the order.
Terms: https://www.paypal.com/legalhub — Privacy: https://www.paypal.com/privacy

**OpenAI** (only when AI features are enabled and you select OpenAI as the provider)
When a visitor uses the AI search/chat/recommendation features, the relevant query text is sent to api.openai.com using the API key you provide.
Terms: https://openai.com/policies/terms-of-use — Privacy: https://openai.com/policies/privacy-policy

**Anthropic (Claude)** (only when AI features are enabled and you select Anthropic as the provider)
When a visitor uses the AI search/chat/recommendation features, the relevant query text is sent to api.anthropic.com using the API key you provide.
Terms: https://www.anthropic.com/legal/consumer-terms — Privacy: https://www.anthropic.com/legal/privacy

**OpenStreetMap** (only when a trip/hotel has a location map)
Map tiles are loaded from the OpenStreetMap tile servers (tile.openstreetmap.org) in the visitor's browser to display the location map. The Leaflet map library itself is bundled with the plugin (not loaded externally).
Terms / Tile Usage Policy: https://operations.osmfoundation.org/policies/tiles/ — Privacy: https://wiki.osmfoundation.org/wiki/Privacy_Policy

== Installation ==

1. Upload the `wp-travel-machine` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to **Travel Machine → Settings** to configure
4. Start creating trips and hotels!

== Frequently Asked Questions ==

= What payment gateways are supported? =
Stripe, PayPal, and Manual/Bank Transfer. Additional gateways can be added via the `wptm_payment_gateways` filter.

= How do I enable AI features? =
Go to Travel Machine → Settings → AI tab. Enable AI and enter your OpenAI or Anthropic API key.

= Can I customize the templates? =
Yes! Copy any file from the plugin's `templates/` folder into `your-theme/wp-travel-machine/` (keeping the same path) and it will be used instead. Examples: `your-theme/wp-travel-machine/single-trip.php`, or just a partial like `your-theme/wp-travel-machine/partials/booking-form.php`. You can also extend the templates without copying them using the action hooks listed in the Developers section.

== Changelog ==

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
* Stripe, PayPal, Manual payment gateways
* AI recommendations, smart search, chat assistant
* Gutenberg blocks
* Search form builder
* Coupon system
* Email notifications
* REST API
* Schema markup
