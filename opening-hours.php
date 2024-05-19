function get_opening_hours($api_key, $place_id) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . $place_id . "&fields=opening_hours&key=" . $api_key;
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return "Error: " . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['error_message'])) {
        return "Error: " . $data['error_message'];
    }

    if (isset($data['result']['opening_hours']['weekday_text'])) {
        return $data['result']['opening_hours']['weekday_text'];
    }

    return null;
}

function display_opening_hours_shortcode() {
    date_default_timezone_set('Australia/Sydney'); // Set the timezone to AEST

    $api_key = 'YOUR_API_KEY';  // Securely store and retrieve these credentials
    $place_id = 'YOUR_PLACE_ID';
    $opening_hours = get_opening_hours($api_key, $place_id);

    if (is_array($opening_hours)) {
        $output = "<ul>";

        // Array to map weekday names to their respective dates
        $weekdays = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
        ];

        // Current date in YYYY-MM-DD format
        $current_date = date('Y-m-d');
        $current_day_of_week = date('N', strtotime($current_date));

        foreach ($opening_hours as $index => $day_hours) {
            // Calculate the date for each day starting from the current date
            if ($index + 1 >= $current_day_of_week) {
                // Same or future day in the current week
                $date = date('d F, Y', strtotime("this week " . strtolower($weekdays[$index]), strtotime($current_date)));
            } else {
                // Day has passed in the current week, show the date for the next week
                $date = date('d F, Y', strtotime("next week " . strtolower($weekdays[$index]), strtotime($current_date)));
            }

            // Remove the redundant day label
            $day_hours_cleaned = str_replace($weekdays[$index] . ': ', '', $day_hours);

            // Highlight the current day
            $day_display = $weekdays[$index] . " (" . $date . "): " . $day_hours_cleaned;
            if ($index + 1 == $current_day_of_week) {
                $day_display = "<strong>$day_display</strong>";
            }

            $output .= "<li>{$day_display}</li>";
        }
        
        $output .= "</ul>";
        return $output;
    }

    return "Opening hours not available.";
}
add_shortcode('display_opening_hours', 'display_opening_hours_shortcode');