# WP Travel Machine — Documentation

**AI‑powered Travel & Hotel Booking Plugin for WordPress**
Version 1.0.0 · Requires WordPress 6.0+ · PHP 7.4+

> A formatted, browsable version of this documentation is available in **`Doc/doc.html`** (open it in any browser).

---

## Table of Contents

- [Overview](#overview)
- [Requirements & Installation](#requirements--installation)
- [Quick Start](#quick-start)
- [System Pages](#system-pages)
- [Managing Trips](#managing-trips)
- [Managing Hotels](#managing-hotels)
- [Hotel Facilities](#hotel-facilities)
- [Availability & Calendar](#availability--calendar)
- [Taxonomies](#taxonomies)
- [Shortcodes](#shortcodes)
- [Gutenberg Blocks](#gutenberg-blocks)
- [Elementor Widgets](#elementor-widgets)
- [Search & Filters](#search--filters)
- [Pagination](#pagination)
- [Bookings & Payments](#bookings--payments)
- [Settings](#settings)
- [Demo Importer](#demo-importer)
- [Developer: Template Overrides](#developer-template-overrides)
- [Developer: Using in a Theme](#developer-using-in-a-theme)
- [Developer: Icon System](#developer-icon-system)
- [Action Hooks](#action-hooks)
- [Filter Hooks](#filter-hooks)
- [Helper Functions](#helper-functions)
- [REST API](#rest-api)
- [Database Tables](#database-tables)
- [FAQ & Troubleshooting](#faq--troubleshooting)

---

## Overview

WP Travel Machine adds two content types — **Trips** and **Hotels** — plus a booking engine, payment gateways, an availability/inventory system, AJAX search & filtering, a wishlist and reviews. Everything renders through one shared, style‑aware renderer, so the output is identical from a shortcode, a Gutenberg block or an Elementor widget.

## Requirements & Installation

| Requirement | Minimum |
|---|---|
| WordPress | 6.0+ |
| PHP | 7.4+ |
| MySQL / MariaDB | 5.6 / 10.1+ |
| Elementor (optional) | 3.5+ |

1. Upload `wp-travel-machine/` to `/wp-content/plugins/` (or install the ZIP).
2. Activate — it creates the database tables and the system pages.
3. Go to **Travel Machine → Settings** to set currency, payment and display options.
4. Optionally run the **Demo Importer** from the dashboard.

## Quick Start

1. **Travel Machine → Trips → Add New Trip** (or Hotels). Fill the title, image and the tabbed config panel.
2. Set pricing — Trips use pricing tiers; Hotels use the **Rooms** tab (price/night, sale price, capacity).
3. Publish — the item appears on its archive (`/trips/`, `/hotels/`) and single page.
4. Show a grid anywhere with `[wptm_trips count="6" columns="3"]` or a block/widget.

## System Pages

Auto‑created on activation (reassign under **Settings → Pages**):

| Page | URL | Shortcode |
|---|---|---|
| All Trips | `/all-trips/` | `[wptm_trips]` |
| All Hotels | `/all-hotels/` | `[wptm_hotels]` |
| Checkout | `/checkout/` | `[wptm_checkout]` |
| Booking Confirmation | `/booking-confirmation/` | `[wptm_booking_confirmation]` |
| My Bookings | `/my-bookings/` | `[wptm_my_bookings]` |
| Wishlist / Cart | `/my-wishlist/`, `/cart/` | `[wptm_wishlist]`, `[wptm_cart]` |

> The post‑type **archives** at `/trips/` and `/hotels/` are separate from these shortcode pages — both list items with filtering & pagination.

## Managing Trips

Tabbed meta box: **Overview · Itinerary · Pricing · Location · Gallery · FAQ**.

| Field | Meta key |
|---|---|
| Duration / Unit | `_wptm_duration`, `_wptm_duration_unit` |
| Group min / max | `_wptm_group_min`, `_wptm_group_max` |
| Difficulty | `_wptm_difficulty` (+ `wptm_difficulty` term) |
| Highlights / Includes / Excludes | `_wptm_highlights`, `_wptm_includes`, `_wptm_excludes` |
| Itinerary | `_wptm_itinerary` |
| Pricing tiers | `_wptm_pricing` (mirrored to `_wptm_price`) |
| FAQ | `_wptm_faq` |
| Location | `_wptm_latitude`, `_wptm_longitude`, `_wptm_address`, `_wptm_map_embed` |
| Gallery / Media | `_wptm_gallery`, `_wptm_video_url`, `_wptm_audio_url` |

## Managing Hotels

Tabbed config: **Overview · Facilities · Location · Rooms · Availability · Gallery**.

| Field | Meta key |
|---|---|
| Star rating | `_wptm_star_rating` |
| Address / City / Country | `_wptm_hotel_address`, `_wptm_hotel_city`, `_wptm_hotel_country` |
| Lat / Lng / Map | `_wptm_hotel_lat`, `_wptm_hotel_lng`, `_wptm_hotel_map_embed` |
| Check‑in / out time | `_wptm_check_in_time`, `_wptm_check_out_time` |
| Email / Phone | `_wptm_hotel_email`, `_wptm_hotel_phone` |
| Facilities (groups) | `_wptm_hotel_facilities` |
| Gallery / Media | `_wptm_hotel_gallery`, `_wptm_hotel_video_url`, `_wptm_hotel_audio_url` |

**Rooms** live in the `wptm_rooms` table (name, type, price/night, sale price, max guests, bed type, size). The booking form lets the guest pick a room and computes **room price × nights**.

## Hotel Facilities

Hotel → **Facilities** tab → add **facility groups** (e.g. *General*, *Wellness*) with one facility per line. Each name gets a matching premium icon and is mirrored into the `wptm_hotel_facility` taxonomy for filtering. Extend the icon map with the `wptm_hotel_facilities` filter.

## Availability & Calendar

Hotel → **Availability** tab → add periods with **From/To**, **Rooms** (inventory), **Status** (Available / Blocked) and an optional **Price/night override**. No rules = always available.

The front‑end booking calendar reflects everything:

- Past dates → disabled
- **Blocked** → red striped
- **Sold out** (bookings ≥ rooms) → grey, auto‑disabled
- **Price override** → shown on the day

## Taxonomies

| Taxonomy | Applies to | Slug |
|---|---|---|
| Destinations | Trips & Hotels | `wptm_destination` |
| Activities | Trips | `wptm_activity` |
| Trip Types | Trips | `wptm_trip_type` |
| Difficulty | Trips | `wptm_difficulty` |
| Hotel Types | Hotels | `wptm_hotel_type` |
| Hotel Facilities | Hotels | `wptm_hotel_facility` |

## Shortcodes

| Shortcode | Purpose | Key attributes |
|---|---|---|
| `[wptm_trips]` | Trip grid (paginated) | `count` `columns` `orderby` `order` `destination` `activity` `paginate` `filters` |
| `[wptm_hotels]` | Hotel grid (paginated) | `count` `columns` `orderby` `order` `destination` `paginate` `filters` |
| `[wptm_search_form]` | Search form | `style="horizontal|vertical"` |
| `[wptm_booking_form]` | Booking form | `id` (0 = current) |
| `[wptm_destinations]` | Destination cards | `count` |
| `[wptm_terms]` | Any taxonomy grid | `taxonomy` `count` `columns` `orderby` `order` |
| `[wptm_my_bookings]` | User orders | — |
| `[wptm_wishlist]` `[wptm_cart]` `[wptm_checkout]` `[wptm_booking_confirmation]` | System pages | — |
| `[wptm_ai_chat]` `[wptm_ai_recommend]` | AI assistant (if enabled) | `title` (recommend) |

**Grid attributes:** `count` (12), `columns` (1–4), `orderby` (`date`/`title`/`price`/`rand`/`menu_order`), `order` (`ASC`/`DESC`), `destination`/`activity` (slug), `paginate` (`yes`), `filters` (`no`), `gap`, `cardRadius`, `accent`, `titleColor`, `textColor`, `btnBg`, `btnColor`, `align`.

```
[wptm_trips count="6" columns="3" orderby="price" order="ASC"]
[wptm_trips destination="bali" filters="yes"]
[wptm_hotels count="9" columns="3" accent="#0ea372" cardRadius="20"]
```

## Gutenberg Blocks

Block category **“WP Travel Machine”** with live preview and **Content** + **Style** panels.

| Block | Name |
|---|---|
| Trip Grid | `wptm/trip-grid` |
| Hotel Grid | `wptm/hotel-grid` |
| Travel Search Form | `wptm/search-form` |
| Destinations Grid | `wptm/destinations` |
| Booking Form | `wptm/booking-form` |

**Style controls:** alignment, grid gap, card radius, colors (accent/price, title, text, button bg, button text).

## Elementor Widgets

Same five items under the **WP Travel Machine** category, each with **Content** + **Style** tabs (Destination/Activity are term dropdowns). Render through the same PHP renderer as blocks/shortcodes.

## Search & Filters

- **Search Form Builder** (Travel Machine → Search Form): enable/reorder fields. Output via `[wptm_search_form]`, AJAX‑filtered.
- **Filter Bar**: trip/hotel archives (and shortcodes with `filters="yes"`) get keyword + facets + price/stars + sort, live over AJAX.
- Search params are namespaced under `wptm_search[...]` to avoid query‑var collisions.
- Customize the query with `wptm_trips_query_args` / `wptm_hotels_query_args`.

## Pagination

**Settings → Display:**

- **Items Per Page** — applies to archives and paginated shortcodes.
- **Pagination Type** — Numbered Pagination or AJAX **Load More**.

## Bookings & Payments

Single pages show a booking form (date picker + room/tier selection, live total). A session **cart** + **Checkout** supports multi‑item orders. Manage orders under **Travel Machine → Bookings** (Trip/Hotel + status filters, drawer view).

Gateways (Settings → Payments) only appear at checkout when **enabled and configured**, so customers never pick a method that can't work:

| Gateway | Behaviour |
|---|---|
| Bank Transfer (Manual) | Order created → confirmation page with bank instructions. Status *awaiting*. |
| Stripe | Requires publishable + secret keys. A Stripe Elements card field is shown inline. **SCA / 3‑D Secure ready** via PaymentIntents — `stripe.confirmCardPayment()` runs any issuer authentication, then the intent is verified server‑side (status, booking id, amount) before the booking is marked *paid*. |
| PayPal | Requires client ID + secret (Sandbox or Live mode). Renders PayPal Smart Buttons; the order is created and **captured server‑side via the REST API**, with the captured amount verified against the booking total before confirming. |

**How online payments flow:** the booking row is created first (status *pending*, payment *unpaid*), the gateway then runs its charge/capture, and the customer is only redirected to the confirmation page once payment is verified on the server. A failed or abandoned charge leaves an unpaid booking and surfaces the error — a paid‑looking confirmation is never shown for free.

Prices are always recomputed server‑side; the browser's totals are never trusted. Customize via `wptm_payment_gateways`, `wptm_payment_methods`, `wptm_bank_transfer_instructions`.

**AJAX endpoints** (all behind the `wptm_booking_nonce`): `wptm_create_booking`, `wptm_process_payment`, `wptm_stripe_create_intent`, `wptm_stripe_confirm`, `wptm_paypal_create_order`, `wptm_paypal_capture_order`.

> **Note:** Online card capture currently covers the single‑item booking form. The multi‑item **cart checkout** accepts Bank Transfer only.

## Settings

Currency/tax · Display (items per page, pagination, gallery style) · Features (wishlist, compare, reviews) · AI · Enquiry · Payments · Pages.

## Demo Importer

Dashboard → **Demo Content** card: import ~12 trips and/or hotels, optionally with **Unsplash images** (free Access Key for topic‑matched photos). A **Remove Demo Content** button cleans up.

---

## Developer: Template Overrides

Copy `wp-travel-machine/templates/<name>` → `your-theme/wp-travel-machine/<name>`. The loader prefers the theme copy.

```
your-theme/wp-travel-machine/partials/trip-card.php
your-theme/wp-travel-machine/single-hotel.php
your-theme/wp-travel-machine/partials/booking-form.php
```

Overridable: `single-trip.php`, `single-hotel.php`, `archive-trip.php`, `archive-hotel.php`, `taxonomy.php`, `partials/*` (trip-card, hotel-card, booking-form, search-form, filter-bar, wishlist-button, gallery-hero, location-map…), `booking/*`, `emails/*`.

> **Gotcha:** always include the `.php` extension when calling `wptm_get_template_part('partials/trip-card.php')` — a missing extension renders nothing.

## Developer: Using in a Theme

```php
// Render a grid in a theme file
echo do_shortcode( '[wptm_trips count="6" columns="3" filters="yes"]' );

// Or call the renderer directly
echo \WPTravelMachine\Blocks\Renderer::trips( [
    'count' => 6, 'columns' => 3, 'accent' => '#0ea372',
] );

// Inject content around single pages — no template copy needed
add_action( 'wptm_single_hotel_before_sidebar', function ( $hotel_id ) {
    echo '<div class="my-promo">Free breakfast included!</div>';
} );

// Disable the bundled (self-hosted) fonts
add_filter( 'wptm_enqueue_fonts', '__return_false' );
```

## Developer: Icon System

```php
echo wptm_icon( 'map-pin', [ 'size' => 18 ] );  // inline SVG
echo wptm_stars( 4 );                            // gold star row
echo wptm_facility_icon( 'Free WiFi' );          // name → icon

add_filter( 'wptm_icon_library', function ( $icons ) {
    $icons['rocket'] = '<path d="..."/>';        // inner SVG of a 24×24 icon
    return $icons;
} );
```

## Action Hooks

| Hook | Args |
|---|---|
| `wptm_loaded` | — |
| `wptm_booking_created` | `$booking_id, $data` |
| `wptm_booking_status_changed` | `$booking_id, $status` |
| `wptm_payment_completed` | `$booking_id, $gateway_id` |
| `wptm_manual_payment_pending` | `$booking_id` |
| `wptm_duplicated_post` | `$new_id, $post` |
| `wptm_before_single_trip` / `wptm_after_single_trip` | `$trip_id` |
| `wptm_single_trip_before_content` / `_after_content` / `_after_overview` | `$trip_id` |
| `wptm_single_trip_before_sidebar` / `_after_sidebar` | `$trip_id` |
| `wptm_before_single_hotel` / `wptm_after_single_hotel` | `$hotel_id` |
| `wptm_single_hotel_before_content` / `_after_content` | `$hotel_id` |
| `wptm_single_hotel_before_sidebar` / `_after_sidebar` | `$hotel_id` |

## Filter Hooks

| Filter | Purpose |
|---|---|
| `wptm_trip_post_type_args` / `wptm_hotel_post_type_args` | Post‑type args |
| `wptm_trips_query_args` / `wptm_hotels_query_args` | Grid query args |
| `wptm_terms_grid_args` | Term‑grid `get_terms()` args |
| `wptm_search_fields_registry` | Available search fields |
| `wptm_item_capacity` | Default capacity when no availability rule |
| `wptm_payment_gateways` / `wptm_payment_methods` | Gateways & checkout methods |
| `wptm_bank_transfer_instructions` | Confirmation page bank text |
| `wptm_icon_library` / `wptm_icon` | Icons |
| `wptm_hotel_facilities` | Facility → icon map |
| `wptm_locate_template` / `wptm_template_args` | Template path / vars |
| `wptm_currencies` / `wptm_countries` | Currency & country lists |
| `wptm_allowed_map_hosts` | Map‑embed providers |
| `wptm_enqueue_fonts` / `wptm_fonts_url` | Bundled (self-hosted) fonts |

## Helper Functions

| Function | Does |
|---|---|
| `wptm_get_template( $name, $args )` | Load a template (theme‑overridable; include `.php`) |
| `wptm_get_template_part( $name, $args )` | Alias |
| `wptm_locate_template( $name )` | Resolve a template path |
| `wptm_get_page_url( $type )` | URL of a system page (`'confirmation'`, `'my_bookings'`…) |
| `wptm_format_price( $amount )` | Currency‑formatted price |
| `wptm_icon( $name, $args )` | Inline SVG icon |
| `wptm_stars( $count, $size )` | Gold star row |
| `wptm_facility_icon( $name, $size )` | Facility name → icon |
| `wptm_hotel_facilities()` | Standard facility → icon map |
| `wptm_payment_methods()` | Active checkout methods |

## REST API

Base: `/wp-json/wptm/v1/`

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/trips` | `per_page`, `page`. Public |
| GET | `/trips/{id}` | Public |
| GET | `/hotels` | Public |
| GET | `/search` | `q`. Public |
| GET | `/availability/{id}` | Public |
| GET | `/bookings` | Requires `manage_options` |
| POST | `/bookings` | Create a booking |

```
curl https://example.com/wp-json/wptm/v1/trips?per_page=5
```

## Database Tables

`wptm_bookings`, `wptm_booking_meta`, `wptm_rooms`, `wptm_availability`, `wptm_reviews`, `wptm_wishlist`, `wptm_coupons`.

> Deleting the plugin drops these tables and removes all options/meta. Back up first.

## FAQ & Troubleshooting

**Settings change not visible on the front end?** Page caching — hard‑refresh (Cmd/Ctrl+Shift+R) or clear your cache/CDN.

**Dates always “Available”?** Default with no availability rules — add periods in the hotel's Availability tab.

**Stripe/PayPal missing at checkout?** They only show when enabled *and* keys are filled in.

**“All Trips/Hotels” page won't paginate?** Those use `[wptm_trips]` / `[wptm_hotels]` (paginate by default) — ensure it isn't `paginate="no"`, then set Items Per Page.

**Translations?** All strings use the `wp-travel-machine` text domain.
