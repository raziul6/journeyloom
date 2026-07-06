=== Byteflows Travel & Hotel Booking ===
Contributors: byteflows
Tags: travel, booking, hotel, trip, tourism
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress into a complete travel & hotel booking platform — trip packages, hotels, a smart booking engine, search and manual checkout.

== Description ==

**Byteflows Travel & Hotel Booking** transforms your WordPress site into a complete travel and hotel booking platform — trip packages and hotels, a step-by-step booking engine, powerful search, reviews, wishlist & compare and manual/bank-transfer checkout. Everything in this plugin is free and fully functional — there is no locked code.

Online card/PayPal/Razorpay checkout, printable invoices, coupons, priced pickup points and an AI assistant are available through the optional **Byteflows Travel & Hotel Booking Pro** add-on (see below).

= Features =

* **Trip Management** — Create trip packages with day-by-day itineraries, pricing tiers, galleries, maps and FAQs
* **Hotel Management** — Hotels with room types, amenities, star ratings and availability
* **Smart Booking Engine** — Step-by-step AJAX booking with availability checking, traveler details and server-side price validation
* **Manual / Bank Transfer Payments** — Take bookings with configurable bank-transfer instructions
* **Advanced Search** — AJAX search with filters for destination, activity, date and budget, plus a drag-and-drop search-form builder
* **Wishlist & Compare** — Let visitors save favorites and compare trips/hotels side by side
* **Reviews & Ratings** — Built-in review system with moderation
* **Email Notifications** — Automated booking confirmation and status-update emails
* **Booking Manager** — A dedicated admin dashboard with a slide-in booking detail drawer and reports
* **Gutenberg Blocks & Elementor Widgets** — Native blocks/widgets for trip grids, hotel grids, search forms and more
* **REST API** — REST API for headless/mobile integrations
* **Schema Markup** — Automatic SEO-friendly structured data for trips and hotels
* **Modern UI/UX** — Clean, responsive design with smooth animations

= Byteflows Travel & Hotel Booking Pro =

The optional **Pro add-on** (hosted at byteflows.net, not on WordPress.org) adds:

* **AI Assistant** — Bring your own API key (OpenAI, Anthropic, or any OpenAI-compatible endpoint) for natural-language search, a concierge chat with bookable cards, smart recommendations, an AI Trip Builder, itinerary generation and AI-drafted customer replies
* **Online payments** — Stripe (cards, SCA / 3-D Secure), PayPal and Razorpay checkout with server-side verification
* **Printable invoices** — Branded, print-ready invoices for any booking
* **Coupons & Discounts** — Percentage or fixed-amount coupon codes with usage limits and expiry
* **Pickup points** — Free or priced pickup locations chosen per traveler at checkout

Install the Pro add-on alongside this free plugin; the extra options appear automatically. No settings to migrate.

= Shortcodes =

* `[wptm_trips]` — Display trip grid (attrs: count, columns, destination, activity)
* `[wptm_hotels]` — Display hotel grid (attrs: count, columns)
* `[wptm_search_form]` — Display search form (attrs: style=horizontal|vertical)
* `[wptm_booking_form]` — Display booking form (attrs: id)
* `[wptm_destinations]` — Display destination grid (attrs: count)
* `[wptm_ai_chat]` — Display the AI chat widget (requires the Pro add-on)

== For Developers ==

Byteflows Travel & Hotel Booking is built to be theme-developer friendly. Integrate it three ways: template overrides, action/filter hooks, and helper functions.

= Template overrides =

Drop a file into `your-theme/journeyloom/<path>` to override the plugin default (same path under `templates/`):

* `single-trip.php`, `single-hotel.php`, `archive-trip.php`, `archive-hotel.php`
* `partials/booking-form.php`, `partials/gallery-hero.php`, `partials/trip-card.php`, `partials/hotel-card.php`, `partials/search-form.php`, `partials/calendar.php`

In your own templates you can load any template/partial (theme-overridable) with:
`wptm_get_template( 'partials/trip-card.php', array( 'foo' => $bar ) );`

= Action hooks =

Single trip: `wptm_before_single_trip`, `wptm_single_trip_before_content`, `wptm_single_trip_after_overview`, `wptm_single_trip_after_content`, `wptm_single_trip_before_sidebar`, `wptm_single_trip_after_sidebar`, `wptm_after_single_trip` — each passes the trip ID.

Single hotel: `wptm_before_single_hotel`, `wptm_single_hotel_before_content`, `wptm_single_hotel_after_content`, `wptm_single_hotel_before_sidebar`, `wptm_single_hotel_after_sidebar`, `wptm_after_single_hotel` — each passes the hotel ID.

Lifecycle: `wptm_loaded` (passes the Plugin instance), `wptm_booking_created`, `wptm_booking_status_changed`, `wptm_payment_completed`, `wptm_manual_payment_pending`.

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

== External services ==

This plugin can connect to the third-party services below. **Each is optional and only contacted when you enable and configure the related feature.** No data is sent anywhere by default. Demo images are bundled with the plugin — the importer contacts no external service.

**OpenStreetMap** (only when a trip/hotel has a location map)
Used to display an interactive location map. Map tiles are loaded from the OpenStreetMap tile servers (tile.openstreetmap.org) in the visitor's browser. The Leaflet map library itself is bundled with the plugin (not loaded externally).
Terms / Tile Usage Policy: https://operations.osmfoundation.org/policies/tiles/ — Privacy: https://wiki.osmfoundation.org/wiki/Privacy_Policy

**Stripe** (Pro add-on only; when the Stripe gateway is enabled and a customer pays by card)
Provided by the Byteflows Travel & Hotel Booking Pro add-on. Used to process card payments. Stripe.js is loaded from js.stripe.com on the checkout page, and the booking amount, currency, order number and a payment token are sent to api.stripe.com to create and verify the charge. No card numbers touch your server.
Terms: https://stripe.com/legal — Privacy: https://stripe.com/privacy

**PayPal** (Pro add-on only; when the PayPal gateway is enabled and a customer pays with PayPal)
Provided by the Pro add-on. The PayPal JS SDK is loaded from paypal.com on the checkout page, and the booking amount, currency and order reference are sent to PayPal's REST API (api-m.paypal.com / api-m.sandbox.paypal.com) to create and capture the order.
Terms: https://www.paypal.com/legalhub — Privacy: https://www.paypal.com/privacy

**Razorpay** (Pro add-on only; when the Razorpay gateway is enabled and a customer pays via Razorpay)
Provided by the Pro add-on. Used to process payments (cards, UPI, netbanking, wallets). The Razorpay Checkout script is loaded from checkout.razorpay.com on the checkout page. The booking amount, currency and order reference are sent to Razorpay's API (api.razorpay.com) to create an order, and the returned payment id/signature are sent back to verify and capture the payment.
Terms: https://razorpay.com/terms/ — Privacy: https://razorpay.com/privacy/

**OpenAI** (Pro add-on only; when AI features are enabled and OpenAI is the provider)
The AI assistant is part of the Pro add-on. When you or a visitor use an AI feature (search, chat, recommendations, trip/itinerary/reply generation), the relevant query and a short catalogue of your public trips/hotels are sent to api.openai.com using the API key you provide. Opt-in: nothing is sent until you install Pro, enable AI and enter your own key.
Terms: https://openai.com/policies/terms-of-use — Privacy: https://openai.com/policies/privacy-policy

**Anthropic (Claude)** (Pro add-on only; when AI features are enabled and Anthropic is the provider)
Same AI features as above, sent to api.anthropic.com using the API key you provide. Opt-in: nothing is sent until you install Pro, enable AI and enter your own key.
Terms: https://www.anthropic.com/legal/consumer-terms — Privacy: https://www.anthropic.com/legal/privacy

**Custom / OpenAI-compatible endpoint** (Pro add-on only; when AI features are enabled and "Custom" is the provider)
For any OpenAI-compatible API you configure by Base URL (for example Groq at https://api.groq.com/openai/v1, Google Gemini's OpenAI-compatible endpoint, OpenRouter, or a self-hosted Ollama). The same AI query data described above is sent to the Base URL you enter, using the API key you provide. Opt-in: nothing is sent until you install Pro, enable AI, choose Custom, and enter a Base URL and key. Please review the terms and privacy policy of whichever provider you configure.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` (or install from the Plugins screen)
2. Activate the plugin through the 'Plugins' menu
3. Follow the setup wizard, then go to **Byteflows Travel → Settings** to configure
4. Start creating trips and hotels!

== Frequently Asked Questions ==

= What payment gateways are supported? =
Manual / Bank Transfer works out of the box. Stripe, PayPal and Razorpay online checkout are provided by the optional Byteflows Travel & Hotel Booking Pro add-on. Additional gateways can be added via the `wptm_payment_gateways` filter.

= How do I use the AI features? =
The AI assistant is part of the Byteflows Travel & Hotel Booking Pro add-on and is bring-your-own-key. With Pro installed, go to Byteflows Travel → Settings → AI, enable AI and enter your provider API key (OpenAI, Anthropic, or any OpenAI-compatible endpoint such as Groq). Nothing is sent to any AI provider until you do this.

= What does the Pro add-on add? =
Online payments (Stripe, PayPal, Razorpay), printable invoices, coupons/discount codes, priced pickup points and the AI assistant. It's a separate plugin from byteflows.net; install it alongside this free plugin and the extra options appear automatically.

= Where do demo images come from? =
The demo importer uses placeholder images bundled inside the plugin. It does not download images from any external service.

= Can I customize the templates? =
Yes! Copy any file from the plugin's `templates/` folder into `your-theme/journeyloom/` (keeping the same path) and it will be used instead. You can also extend templates without copying them using the action hooks listed in the Developers section.

== Changelog ==

= 1.0.0 =
* Initial release
