# Opening Hours Display for WordPress

This project integrates Google My Business data to dynamically display the opening hours of a business on a WordPress site using Elementor. It fetches the current and future opening hours, highlighting the current day and ensuring dates are displayed in the correct format.

## Prerequisites

To retrieve Google My Business data, you’ll need your API Key. Follow these steps:

1. Get your API Key at [Google API Console](https://developers.google.com/maps/documentation/javascript/get-api-key) with the API: **Places API**.
2. Restrict the API key to your site’s IP for security.

You can find your unique Place ID by searching for your business’s name in [Google’s Place ID Finder](https://developers.google.com/places/place-id). Note that only single business locations are accepted; coverage areas are not supported.

## Installation

1. **Add API Key and Place ID**
   - Replace `'YOUR_API_KEY'` with your actual Google Places API key.
   - Replace `'YOUR_PLACE_ID'` with the Place ID of the business you want to get opening hours for.

2. **Add PHP Code to WordPress**
   - Open your theme’s `functions.php` file or create a custom plugin.
   - Copy and paste the provided PHP code into the file.

## Usage

To display the opening hours on a WordPress page using Elementor:
1. Add an HTML widget in Elementor where you want the opening hours to appear.
2. Use the shortcode `[display_opening_hours]`.
