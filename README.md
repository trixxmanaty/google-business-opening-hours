# Opening Hours Display for WordPress

This project integrates Google **Places** data to dynamically display the opening hours of a business on a WordPress site (works great with Elementor). It fetches current and future opening hours, highlights **today**, respects the **site timezone** (Settings → General → Timezone), and **caches** responses to reduce API calls.

## Features

* Uses WordPress timezone (no hardcoded `date_default_timezone_set`)
* Highlights the current day with a CSS class (`.today`)
* Transient caching (12h by default) to improve performance
* Secrets stored in `wp-config.php` (no API keys in code)
* Flexible shortcode options

## Prerequisites

You’ll need a Google API Key with the **Places API** enabled and your business **Place ID**.

1. Create an API key in the [Google API Console](https://developers.google.com/maps/documentation/javascript/get-api-key) and enable **Places API**.
2. **Restrict** the API key:

   * Restrict **API** usage to *Places API*.
   * Because this runs **server-side** in WordPress, restrict by your server’s **IP address(es)** if possible.
3. Find your Place ID via Google’s [Place ID Finder](https://developers.google.com/places/place-id).

   > Only single business locations are supported (coverage areas aren’t).

## Installation

### 1) Add API Key and Place ID to `wp-config.php`

Add these near the end of `wp-config.php` (above the line that says “That’s all, stop editing!”):

```php
/** Google Places (server-side) */
define('GOOGLE_MAPS_API_KEY', 'YOUR_REAL_API_KEY_HERE');
define('GOOGLE_PLACE_ID',    'YOUR_REAL_PLACE_ID_HERE'); // optional; can be overridden per-shortcode
```

> **Do not** commit real keys to version control.

### 2) Add the PHP to WordPress

* Either place the provided PHP in your theme’s `functions.php` **or** (recommended) create a small **must-use plugin** so updates to your theme won’t affect it.

## Usage

Add the shortcode where you want the schedule to appear:

```
[display_opening_hours]
```

If you didn’t set `GOOGLE_PLACE_ID` in `wp-config.php`, you can pass it inline:

```
[display_opening_hours place_id="YOUR_PLACE_ID"]
```

### Shortcode Options

| Attribute         | Values     | Default | Description                                |
| ----------------- | ---------- | ------- | ------------------------------------------ |
| `place_id`        | string     | (const) | Overrides `GOOGLE_PLACE_ID`.               |
| `show_dates`      | `yes`/`no` | `yes`   | Append the calendar date for each weekday. |
| `highlight_today` | `yes`/`no` | `yes`   | Bold + `.today` class on the current day.  |

**Elementor**: Use the **Shortcode** widget and paste the shortcode above.

## Styling

A couple of helpful hooks:

* `<ul class="gmb-hours">…</ul>`
* The current day gets: `<li class="today">…</li>`

Example CSS:

```css
.gmb-hours { list-style: none; margin: 0; padding: 0; }
.gmb-hours li { padding: .4rem .6rem; }
.gmb-hours li.today { background: #fff7cc; border-radius: .35rem; }
.gmb-hours li.today strong { font-weight: 700; }
.gmb-hours__date { opacity: .75; }
```

## Caching

* Results are cached using WordPress **transients** for **12 hours** by default.
* You can change the TTL with a filter in your theme or plugin:

```php
add_filter('mysite_gmb_hours_ttl', function () {
    return 6 * HOUR_IN_SECONDS; // cache for 6 hours
});
```

* To force-refresh a specific place’s cache, delete the transient:

  ```php
  delete_transient('gmb_hours_' . md5('YOUR_PLACE_ID'));
  ```

## Timezone

All dates/times are computed using the **site’s timezone** (`Settings → General → Timezone`) via `current_datetime()` / `wp_timezone()` / `wp_date()`—no manual timezone setting required.

## Troubleshooting

* **“Opening hours not available at the moment.”**
  Check that your API key is valid, **Places API** is enabled, and key restrictions are correct.
* **Rate limits / `OVER_QUERY_LIMIT`**
  Reduce requests or increase cache TTL.
* **Wrong dates**
  Verify your site’s timezone setting is correct.

## Security

* Keep API keys in **`wp-config.php`**, not in code or the database.
* Restrict the key to **Places API** and your server’s **IP(s)** when possible.

## Changelog

### 1.1

* 🔒 API key + Place ID moved to `wp-config.php`
* ⚡ Added transient caching (12h, filterable)
* 🌍 Switched to WordPress timezone utilities
* ✨ Shortcode improvements: highlight current day; options for `show_dates` and `highlight_today`; consistent Mon→Sun order; safer output escaping
* 🧩 Added CSS hooks (`.gmb-hours`, `.today`, `.__date`, `.__time`, `.__day`)

### 1.0

* Initial release: display opening hours via Google Places `opening_hours.weekday_text`.

---
