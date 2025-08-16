<?php
/**
 * Get opening hours from Google Places, cached via transients.
 *
 * @param string $place_id
 * @return array|WP_Error  Array of weekday_text lines or WP_Error
 */
function mysite_get_opening_hours( $place_id ) {
    $api_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';

    if ( empty( $api_key ) || empty( $place_id ) ) {
        return new WP_Error( 'config_error', 'Missing Google API key or Place ID.' );
    }

    // Cache: unique per place, easy to invalidate by changing TTL/filter.
    $cache_key = 'gmb_hours_' . md5( $place_id );
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    $endpoint = 'https://maps.googleapis.com/maps/api/place/details/json';
    $query    = array(
        'place_id' => $place_id,
        'fields'   => 'opening_hours', // returns opening_hours.weekday_text
        'key'      => $api_key,
    );
    $url = add_query_arg( $query, $endpoint );

    $response = wp_remote_get( $url, array(
        'timeout'    => 12,
        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    if ( 200 !== $code ) {
        return new WP_Error( 'http_error', 'HTTP error: ' . $code );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error( 'json_error', 'Invalid JSON from Google Places.' );
    }

    if ( ! empty( $data['error_message'] ) ) {
        return new WP_Error( 'api_error', $data['error_message'] );
    }

    if ( isset( $data['status'] ) && 'OK' !== $data['status'] ) {
        return new WP_Error( 'api_status', 'Places API status: ' . $data['status'] );
    }

    $weekday_text = $data['result']['opening_hours']['weekday_text'] ?? null;
    if ( is_array( $weekday_text ) ) {
        // Cache for 12 hours (filterable).
        $ttl = (int) apply_filters( 'mysite_gmb_hours_ttl', 12 * HOUR_IN_SECONDS );
        set_transient( $cache_key, $weekday_text, $ttl );
        return $weekday_text;
    }

    return new WP_Error( 'no_hours', 'No opening hours available.' );
}

/**
 * Shortcode: [display_opening_hours place_id="..."]
 *
 * Attributes:
 * - place_id (optional if defined in wp-config.php as GOOGLE_PLACE_ID)
 * - show_dates: yes|no  (default yes) — append actual calendar date for each day (site timezone)
 * - highlight_today: yes|no (default yes) — bold + CSS class for the current day
 */
function mysite_display_opening_hours_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'place_id'        => defined('GOOGLE_PLACE_ID') ? GOOGLE_PLACE_ID : '',
            'show_dates'      => 'yes',
            'highlight_today' => 'yes',
        ),
        $atts,
        'display_opening_hours'
    );

    $place_id        = sanitize_text_field( $atts['place_id'] );
    $show_dates      = ( 'yes' === strtolower( $atts['show_dates'] ) );
    $highlight_today = ( 'yes' === strtolower( $atts['highlight_today'] ) );

    if ( empty( $place_id ) ) {
        return esc_html__( 'Place ID is missing.', 'mysite' );
    }

    $hours = mysite_get_opening_hours( $place_id );
    if ( is_wp_error( $hours ) ) {
        // Don’t leak details to visitors; admins can investigate logs if needed.
        return esc_html__( 'Opening hours not available at the moment.', 'mysite' );
    }

    if ( ! is_array( $hours ) || empty( $hours ) ) {
        return esc_html__( 'Opening hours not available.', 'mysite' );
    }

    // WordPress timezone-aware "now"
    $now          = current_datetime();        // DateTimeImmutable in the site’s timezone
    $tz           = wp_timezone();             // DateTimeZone object
    $today_index  = (int) $now->format( 'N' ) - 1; // 0=Mon ... 6=Sun
    $day_order    = array( 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday' );

    // Normalize returned lines into an associative map by day label.
    // Example line: "Monday: 9:00 AM – 5:00 PM"
    $by_day = array();
    foreach ( $hours as $line ) {
        $parts = explode( ': ', $line, 2 );
        if ( 2 === count( $parts ) ) {
            $label = $parts[0];
            $times = $parts[1];
            $by_day[ $label ] = $times;
        }
    }

    // Build list in consistent Mon→Sun order, computing the correct date for each day
    // relative to "now" in the site’s timezone.
    $out  = '<ul class="gmb-hours">';
    foreach ( $day_order as $i => $label ) {
        $times  = $by_day[ $label ] ?? '';
        $offset = ( $i - $today_index + 7 ) % 7;                    // 0 for today, 1..6 ahead
        $date_i = $now->modify( "+{$offset} days" );

        $date_str = $show_dates
            ? wp_date( 'd F, Y', $date_i->getTimestamp(), $tz )
            : '';

        $is_today = ( $i === $today_index );
        $li_class = $is_today && $highlight_today ? ' class="today"' : '';

        $label_esc = esc_html( $label );
        $time_esc  = esc_html( $times );
        $date_html = $show_dates ? ' <span class="gmb-hours__date">(' . esc_html( $date_str ) . ')</span>' : '';

        $content = '<span class="gmb-hours__day">' . $label_esc . '</span>' . $date_html . ': ' .
                   '<span class="gmb-hours__time">' . $time_esc . '</span>';

        if ( $is_today && $highlight_today ) {
            $content = '<strong>' . $content . '</strong>';
        }

        $out .= "<li{$li_class}>{$content}</li>";
    }
    $out .= '</ul>';

    return $out;
}
add_shortcode( 'display_opening_hours', 'mysite_display_opening_hours_shortcode' );