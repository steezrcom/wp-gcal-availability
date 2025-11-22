<?php
/**
 * Plugin Name: Google Calendar Availability
 * Description: Reads a Google Calendar and exposes free busy blocks for a front end calendar.
 * Version: 1.2.3
 * Author: steezr
 * Text Domain: gcal-availability
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Gcal_Availability {
    private const OPTION_NAME = 'gcal_availability_settings';
    private const CACHE_KEY_PREFIX = 'gcal_cache_';
    private const CACHE_DURATION = 300; // 5 minutes
    private const RATE_LIMIT_KEY = 'gcal_rate_limit_';
    private const MAX_REQUESTS_PER_MINUTE = 30;

    public function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('gcal_availability_calendar', [$this, 'render_calendar_shortcode']);
    }

    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'gcal-availability',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Get plugin settings
     */
    private function get_settings(): array {
        $defaults = [
            'ical_url' => '',
            'cache_duration' => 300,
            'enable_logging' => false,
            'opening_hours_start' => '09:00',
            'opening_hours_end' => '17:00',
            'hide_non_business_hours' => false,
            'month_click_action' => 'day_view',  // 'day_view' or 'redirect'
            'month_click_url' => '',
            'show_cta_button' => false,
            'cta_button_text' => '',
            'cta_button_url' => '',
        ];

        $settings = get_option(self::OPTION_NAME, $defaults);

        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get the iCal URL from settings
     */
    private function get_ical_url(): string {
        $settings = $this->get_settings();
        return $settings['ical_url'] ?? '';
    }

    /**
     * Log message if logging is enabled
     */
    private function log(string $message, string $level = 'info'): void {
        $settings = $this->get_settings();

        if (! $settings['enable_logging']) {
            return;
        }

        error_log(sprintf('[GCal Availability] [%s] %s', strtoupper($level), $message));
    }

    public function register_rest_routes(): void {
        register_rest_route(
            'gcal/v1',
            '/availability',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle_availability_request'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'start' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return $this->validate_date($param);
                        },
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'end' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return $this->validate_date($param);
                        },
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'view' => [
                        'required'          => false,
                        'default'           => 'dayGridMonth',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
    }

    /**
     * Validate date format (Y-m-d)
     */
    private function validate_date(string $date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Check rate limiting
     */
    private function check_rate_limit(): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = self::RATE_LIMIT_KEY . md5($ip);

        $requests = get_transient($key);

        if ($requests === false) {
            set_transient($key, 1, 60);
            return true;
        }

        if ($requests >= self::MAX_REQUESTS_PER_MINUTE) {
            return false;
        }

        set_transient($key, $requests + 1, 60);
        return true;
    }

    public function handle_availability_request(WP_REST_Request $request) {
        // Rate limiting
        if (! $this->check_rate_limit()) {
            $this->log('Rate limit exceeded for IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'warning');
            return new WP_REST_Response(
                ['error' => 'Too many requests. Please try again later.'],
                429
            );
        }

        $start = sanitize_text_field($request->get_param('start'));
        $end   = sanitize_text_field($request->get_param('end'));
        $view  = sanitize_text_field($request->get_param('view')) ?: 'dayGridMonth';

        $this->log("API Request: start=$start, end=$end, view=$view", 'info');

        // Validate iCal URL is configured
        $icalUrl = $this->get_ical_url();
        if (empty($icalUrl)) {
            $this->log('iCal URL not configured', 'error');
            return new WP_REST_Response(
                ['error' => 'Calendar not configured. Please contact the administrator.'],
                500
            );
        }

        // Clamp the range to max 90 days
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $diff = $startDate->diff($endDate);

        if ($diff->days > 90) {
            return new WP_REST_Response(
                ['error' => 'Date range too large. Maximum 90 days allowed.'],
                400
            );
        }

        $events = $this->fetch_gcal_events($start, $end);

        if ($events === null) {
            return new WP_REST_Response(
                ['error' => 'Failed to fetch calendar data. Please try again later.'],
                500
            );
        }

        $this->log("Returning busy blocks for all views", 'info');

        // Return busy blocks for all views
        $busyBlocks = [];
        foreach ($events as $event) {
            $block = [
                'start' => $event['start'],
                'end'   => $event['end'],
                'title' => 'Busy',
            ];

            // Include allDay flag if present
            if (isset($event['allDay']) && $event['allDay']) {
                $block['allDay'] = true;
            }

            $busyBlocks[] = $block;
        }

        return $busyBlocks;
    }

    /**
     * Get month view data - returns day-level availability
     * A day is available if there are at least 2 consecutive hours free within opening hours
     */
    private function get_month_view_data(array $events, string $start, string $end): array {
        $settings = $this->get_settings();
        $openingStart = $settings['opening_hours_start'] ?? '09:00';
        $openingEnd = $settings['opening_hours_end'] ?? '17:00';

        // Group events by day
        $eventsByDay = [];
        foreach ($events as $event) {
            $eventStart = new DateTime($event['start']);
            $eventEnd = new DateTime($event['end']);
            $dayKey = $eventStart->format('Y-m-d');

            if (!isset($eventsByDay[$dayKey])) {
                $eventsByDay[$dayKey] = [];
            }

            $eventsByDay[$dayKey][] = [
                'start' => $eventStart,
                'end' => $eventEnd,
            ];
        }

        // Generate day blocks with availability status
        $dayBlocks = [];
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);

        $currentDate = clone $startDate;
        while ($currentDate < $endDate) {
            $dayKey = $currentDate->format('Y-m-d');
            $dayEvents = $eventsByDay[$dayKey] ?? [];

            // Check if there are at least 2 consecutive hours free
            $available = $this->has_two_hours_free($dayKey, $dayEvents, $openingStart, $openingEnd);

            $dayBlocks[] = [
                'date' => $dayKey,
                'available' => $available,
            ];

            $currentDate->modify('+1 day');
        }

        return $dayBlocks;
    }

    /**
     * Check if a day has at least 2 consecutive hours free within opening hours
     */
    private function has_two_hours_free(string $date, array $events, string $openingStart, string $openingEnd): bool {
        // Create opening hours datetime objects for this day
        $dayStart = new DateTime($date . ' ' . $openingStart);
        $dayEnd = new DateTime($date . ' ' . $openingEnd);

        // If no events, entire day is free
        if (empty($events)) {
            return true;
        }

        // Sort events by start time
        usort($events, function($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        // Check gap before first event
        $firstEvent = $events[0];
        if ($firstEvent['start'] > $dayStart) {
            $gapMinutes = ($firstEvent['start']->getTimestamp() - $dayStart->getTimestamp()) / 60;
            if ($gapMinutes >= 120) {
                return true;
            }
        }

        // Check gaps between events
        for ($i = 0; $i < count($events) - 1; $i++) {
            $currentEnd = $events[$i]['end'];
            $nextStart = $events[$i + 1]['start'];

            $gapMinutes = ($nextStart->getTimestamp() - $currentEnd->getTimestamp()) / 60;
            if ($gapMinutes >= 120) {
                return true;
            }
        }

        // Check gap after last event
        $lastEvent = $events[count($events) - 1];
        if ($lastEvent['end'] < $dayEnd) {
            $gapMinutes = ($dayEnd->getTimestamp() - $lastEvent['end']->getTimestamp()) / 60;
            if ($gapMinutes >= 120) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch events from Google Calendar iCal and return minimal array.
     * Implements caching to reduce API calls.
     *
     * @param string $start Date in Y-m-d format
     * @param string $end   Date in Y-m-d format
     * @return array<int, array{start: string, end: string}>|null Returns null on error
     */
    private function fetch_gcal_events(string $start, string $end): ?array {
        // Check cache first
        $cacheKey = self::CACHE_KEY_PREFIX . md5($start . $end);
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            $this->log("Cache hit for range {$start} to {$end}");
            return $cached;
        }

        $this->log("Cache miss, fetching iCal data for range {$start} to {$end}");

        $icalUrl = $this->get_ical_url();

        $response = wp_remote_get($icalUrl, [
            'timeout' => 15,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            $this->log('Failed to fetch iCal: ' . $response->get_error_message(), 'error');
            return null;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            $this->log("iCal fetch returned status code: {$statusCode}", 'error');
            return null;
        }

        $body = wp_remote_retrieve_body($response);

        if (! $body) {
            $this->log('Empty response body from iCal URL', 'error');
            return null;
        }

        $events = $this->parse_ical($body);

        // Filter events by requested date range
        $startTimestamp = strtotime($start . ' 00:00:00');
        $endTimestamp   = strtotime($end . ' 23:59:59');

        $filtered = [];

        foreach ($events as $event) {
            $eventStart = strtotime($event['start']);
            $eventEnd   = strtotime($event['end']);

            if ($eventEnd < $startTimestamp || $eventStart > $endTimestamp) {
                continue;
            }

            $filtered[] = $event;
        }

        // Cache the results
        $settings = $this->get_settings();
        $cacheDuration = $settings['cache_duration'] ?? self::CACHE_DURATION;
        set_transient($cacheKey, $filtered, $cacheDuration);

        $this->log("Cached " . count($filtered) . " events for range {$start} to {$end}");

        return $filtered;
    }

    /**
     * Parse iCal data into events array
     *
     * @param string $icalData
     * @return array<int, array{start: string, end: string}>
     */
    private function parse_ical(string $icalData): array {
        $lines = preg_split('/\R/', $icalData);
        if (! $lines) {
            return [];
        }

        $events = [];
        $current = [];
        $insideEvent = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $insideEvent = true;
                $current = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                $insideEvent = false;

                if (isset($current['DTSTART']) && isset($current['DTEND'])) {
                    // Check if this is an all-day event
                    // Method 1: Check if VALUE=DATE parameter is present
                    // Method 2: Check if date is 8 digits (date only, no time)
                    $isAllDay = (isset($current['DTSTART_ISDATE']) && $current['DTSTART_ISDATE']) ||
                                (strlen(trim($current['DTSTART'])) === 8 && ctype_digit(trim($current['DTSTART'])));

                    // Debug logging
                    $this->log("Event DTSTART: " . $current['DTSTART'] . " (length: " . strlen(trim($current['DTSTART'])) . ", isAllDay: " . ($isAllDay ? 'true' : 'false') . ")");

                    if ($isAllDay) {
                        // For all-day events, return date-only strings (YYYY-MM-DD format)
                        // This prevents timezone conversion issues
                        $startDate = $this->parse_ical_date_only($current['DTSTART']);
                        $endDate   = $this->parse_ical_date_only($current['DTEND']);

                        if ($startDate && $endDate) {
                            $event = [
                                'start' => $startDate,
                                'end'   => $endDate,
                                'allDay' => true,
                            ];

                            $this->log("All-day event detected: {$startDate} to {$endDate}");
                            $events[] = $event;
                        }
                    } else {
                        // For timed events, use full datetime with timezone
                        $startUtc = $this->parse_ical_datetime($current['DTSTART'], $current['DTSTART_TZID'] ?? null);
                        $endUtc   = $this->parse_ical_datetime($current['DTEND'], $current['DTEND_TZID'] ?? null);

                        if ($startUtc && $endUtc) {
                            // Check if event crosses midnight
                            $startDate = substr($startUtc, 0, 10); // YYYY-MM-DD
                            $endDate = substr($endUtc, 0, 10);     // YYYY-MM-DD

                            $event = [
                                'start' => $startUtc,
                                'end'   => $endUtc,
                            ];

                            // Mark midnight-crossing events so they don't span days in month view
                            if ($startDate !== $endDate) {
                                $event['display'] = 'block';
                                $event['classNames'] = ['gcal-midnight-crossing'];
                            }

                            $events[] = $event;
                        }
                    }
                }

                $current = [];
                continue;
            }

            if ($insideEvent) {
                // Lines can have parameters, we only care about the value part after colon
                $parts = explode(':', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                [$namePart, $value] = $parts;

                // Property name may include parameters like DTSTART;TZID=Europe/Prague
                $nameSubparts = explode(';', $namePart, 2);
                $propName = strtoupper($nameSubparts[0]);

                if ($propName === 'DTSTART' || $propName === 'DTEND') {
                    $current[$propName] = $value;

                    // Extract timezone if present
                    if (isset($nameSubparts[1])) {
                        // Check for TZID parameter
                        if (strpos($nameSubparts[1], 'TZID=') === 0) {
                            $tzid = substr($nameSubparts[1], 5);
                            $current[$propName . '_TZID'] = $tzid;
                        }
                        // Check for VALUE=DATE parameter (indicates all-day event)
                        if (strpos($nameSubparts[1], 'VALUE=DATE') !== false) {
                            $current[$propName . '_ISDATE'] = true;
                        }
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Parse an iCal date-only value into YYYY-MM-DD format.
     * Used for all-day events to avoid timezone conversion issues.
     *
     * @param string $value Date in format YYYYMMDD (e.g., 20251117)
     * @return string|null Date in YYYY-MM-DD format (e.g., 2025-11-17)
     */
    private function parse_ical_date_only(string $value): ?string {
        $value = trim($value);

        // Check if it's a date-only value (8 digits)
        if (strlen($value) !== 8 || !ctype_digit($value)) {
            return null;
        }

        // Parse YYYYMMDD format
        $year = substr($value, 0, 4);
        $month = substr($value, 4, 2);
        $day = substr($value, 6, 2);

        // Validate date
        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            return null;
        }

        // Return in YYYY-MM-DD format (FullCalendar's all-day format)
        return "{$year}-{$month}-{$day}";
    }

    /**
     * Parse an iCal datetime into an ISO string.
     *
     * Supports formats like:
     * 20251115T100000Z (UTC)
     * 20251115T100000 (floating time)
     * 20251115 (all-day event)
     *
     * @param string $value
     * @param string|null $tzid Timezone identifier
     * @return string|null
     */
    private function parse_ical_datetime(string $value, ?string $tzid = null): ?string {
        $value = trim($value);

        // Check if it's an all-day event (date only, no time)
        if (strlen($value) === 8 && ctype_digit($value)) {
            // For all-day events, create date at midnight UTC
            $dt = DateTime::createFromFormat('Ymd', $value, new DateTimeZone('UTC'));
            if (! $dt) {
                return null;
            }
            // Set time to 00:00:00
            $dt->setTime(0, 0, 0);
            return $dt->format(DateTime::ATOM);
        }

        // Values ending with Z are UTC
        $isUtc = str_ends_with($value, 'Z');

        if ($isUtc) {
            $value = substr($value, 0, -1);
        }

        $dt = DateTime::createFromFormat('Ymd\THis', $value);

        if (! $dt) {
            return null;
        }

        if ($isUtc) {
            $dt->setTimezone(new DateTimeZone('UTC'));
        } elseif ($tzid) {
            // Apply the timezone from TZID parameter
            try {
                $dt->setTimezone(new DateTimeZone($tzid));
            } catch (Exception $e) {
                $this->log("Invalid timezone: {$tzid}", 'warning');
            }
        }

        return $dt->format(DateTime::ATOM);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_options_page(
            __('Calendar Availability Settings', 'gcal-availability'),
            __('Calendar Availability', 'gcal-availability'),
            'manage_options',
            'gcal-availability',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting(
            'gcal_availability_group',
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
            ]
        );

        add_settings_section(
            'gcal_availability_main',
            __('Calendar Settings', 'gcal-availability'),
            [$this, 'render_settings_section'],
            'gcal-availability'
        );

        add_settings_field(
            'ical_url',
            __('iCal URL', 'gcal-availability'),
            [$this, 'render_ical_url_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'cache_duration',
            __('Cache Duration (seconds)', 'gcal-availability'),
            [$this, 'render_cache_duration_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'enable_logging',
            __('Enable Logging', 'gcal-availability'),
            [$this, 'render_enable_logging_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'opening_hours_start',
            __('Opening Hours Start', 'gcal-availability'),
            [$this, 'render_opening_hours_start_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'opening_hours_end',
            __('Opening Hours End', 'gcal-availability'),
            [$this, 'render_opening_hours_end_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'hide_non_business_hours',
            __('Hide Non-Business Hours', 'gcal-availability'),
            [$this, 'render_hide_non_business_hours_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'month_click_action',
            __('Month View Click Action', 'gcal-availability'),
            [$this, 'render_month_click_action_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'month_click_url',
            __('Redirect URL', 'gcal-availability'),
            [$this, 'render_month_click_url_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'show_cta_button',
            __('Show CTA Button', 'gcal-availability'),
            [$this, 'render_show_cta_button_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'cta_button_text',
            __('CTA Button Text', 'gcal-availability'),
            [$this, 'render_cta_button_text_field'],
            'gcal-availability',
            'gcal_availability_main'
        );

        add_settings_field(
            'cta_button_url',
            __('CTA Button URL', 'gcal-availability'),
            [$this, 'render_cta_button_url_field'],
            'gcal-availability',
            'gcal_availability_main'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings(array $input): array {
        $sanitized = [];

        if (isset($input['ical_url'])) {
            $sanitized['ical_url'] = esc_url_raw($input['ical_url']);
        }

        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
            if ($sanitized['cache_duration'] < 60) {
                $sanitized['cache_duration'] = 60; // Minimum 1 minute
            }
        }

        $sanitized['enable_logging'] = isset($input['enable_logging']) && $input['enable_logging'] === '1';

        // Validate opening hours (HH:MM format)
        if (isset($input['opening_hours_start'])) {
            $sanitized['opening_hours_start'] = $this->sanitize_time($input['opening_hours_start'], '09:00');
        }

        if (isset($input['opening_hours_end'])) {
            $sanitized['opening_hours_end'] = $this->sanitize_time($input['opening_hours_end'], '17:00');
        }

        $sanitized['hide_non_business_hours'] = isset($input['hide_non_business_hours']) && $input['hide_non_business_hours'] === '1';

        if (isset($input['month_click_action'])) {
            $sanitized['month_click_action'] = in_array($input['month_click_action'], ['day_view', 'redirect'])
                ? $input['month_click_action']
                : 'day_view';
        }

        if (isset($input['month_click_url'])) {
            $sanitized['month_click_url'] = esc_url_raw($input['month_click_url']);
        }

        $sanitized['show_cta_button'] = isset($input['show_cta_button']) && $input['show_cta_button'] === '1';

        if (isset($input['cta_button_text'])) {
            $sanitized['cta_button_text'] = sanitize_text_field($input['cta_button_text']);
        }

        if (isset($input['cta_button_url'])) {
            $sanitized['cta_button_url'] = esc_url_raw($input['cta_button_url']);
        }

        return $sanitized;
    }

    /**
     * Sanitize time input (HH:MM format)
     */
    private function sanitize_time(string $time, string $default): string {
        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return $time;
        }
        return $default;
    }

    /**
     * Render admin page
     */
    public function render_admin_page(): void {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Handle cache clearing
        if (isset($_POST['clear_cache']) && check_admin_referer('gcal_clear_cache')) {
            $this->clear_all_caches();
            echo '<div class="notice notice-success"><p>' . __('Cache cleared successfully.', 'gcal-availability') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gcal_availability_group');
                do_settings_sections('gcal-availability');
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php _e('Cache Management', 'gcal-availability'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('gcal_clear_cache'); ?>
                <p><?php _e('Clear all cached calendar data to force a fresh fetch from the iCal URL.', 'gcal-availability'); ?></p>
                <button type="submit" name="clear_cache" class="button"><?php _e('Clear Cache', 'gcal-availability'); ?></button>
            </form>

            <hr>

            <h2><?php _e('Usage', 'gcal-availability'); ?></h2>
            <p><?php _e('Use the following shortcode to display the calendar:', 'gcal-availability'); ?></p>
            <code>[gcal_availability_calendar]</code>
            <p><?php _e('Optional attributes:', 'gcal-availability'); ?></p>
            <ul>
                <li><code>initial_view="dayGridMonth"</code> - <?php _e('Calendar view (dayGridMonth, timeGridWeek, timeGridDay)', 'gcal-availability'); ?></li>
                <li><code>locale="cs"</code> - <?php _e('Language code', 'gcal-availability'); ?></li>
                <li><code>first_day="1"</code> - <?php _e('First day of week (0=Sunday, 1=Monday)', 'gcal-availability'); ?></li>
            </ul>
        </div>
        <?php
    }

    public function render_settings_section(): void {
        echo '<p>' . __('Configure your Google Calendar integration settings below.', 'gcal-availability') . '</p>';
    }

    public function render_ical_url_field(): void {
        $settings = $this->get_settings();
        $value = $settings['ical_url'] ?? '';
        ?>
        <input type="url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[ical_url]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="https://calendar.google.com/calendar/ical/...">
        <p class="description">
            <?php _e('Enter your secret Google Calendar iCal URL. Get it from Google Calendar > Settings > Integrate calendar.', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_cache_duration_field(): void {
        $settings = $this->get_settings();
        $value = $settings['cache_duration'] ?? 300;
        ?>
        <input type="number" name="<?php echo esc_attr(self::OPTION_NAME); ?>[cache_duration]"
               value="<?php echo esc_attr($value); ?>"
               min="60"
               step="60">
        <p class="description">
            <?php _e('How long to cache calendar data (minimum 60 seconds). Default: 300 seconds (5 minutes).', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_enable_logging_field(): void {
        $settings = $this->get_settings();
        $checked = $settings['enable_logging'] ?? false;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[enable_logging]"
                   value="1" <?php checked($checked, true); ?>>
            <?php _e('Enable debug logging to error log', 'gcal-availability'); ?>
        </label>
        <?php
    }

    public function render_opening_hours_start_field(): void {
        $settings = $this->get_settings();
        $value = $settings['opening_hours_start'] ?? '09:00';
        ?>
        <input type="time" name="<?php echo esc_attr(self::OPTION_NAME); ?>[opening_hours_start]"
               value="<?php echo esc_attr($value); ?>">
        <p class="description">
            <?php _e('Start time for business hours (e.g., 09:00 or 22:00 for nightclub)', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_opening_hours_end_field(): void {
        $settings = $this->get_settings();
        $value = $settings['opening_hours_end'] ?? '17:00';
        ?>
        <input type="time" name="<?php echo esc_attr(self::OPTION_NAME); ?>[opening_hours_end]"
               value="<?php echo esc_attr($value); ?>">
        <p class="description">
            <?php _e('End time for business hours (e.g., 17:00 or 02:00 for nightclub). Supports midnight crossing: if end time is earlier than start time (e.g., 22:00-02:00), the calendar will show the full 24-hour day with business hours highlighted across midnight.', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_hide_non_business_hours_field(): void {
        $settings = $this->get_settings();
        $value = $settings['hide_non_business_hours'] ?? false;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[hide_non_business_hours]"
                   value="1" <?php checked($value, true); ?>>
            <?php _e('Hide/compress non-business hours in week/day views', 'gcal-availability'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, the calendar will only display business hours. For normal hours (e.g., 09:00-17:00), non-business hours are completely hidden. For midnight-crossing hours (e.g., 22:00-02:00), non-business hours are compressed to thin separator lines to save space while showing the full day.', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_month_click_action_field(): void {
        $settings = $this->get_settings();
        $value = $settings['month_click_action'] ?? 'day_view';
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[month_click_action]">
            <option value="day_view" <?php selected($value, 'day_view'); ?>>
                <?php _e('Switch to Day View', 'gcal-availability'); ?>
            </option>
            <option value="redirect" <?php selected($value, 'redirect'); ?>>
                <?php _e('Redirect to URL', 'gcal-availability'); ?>
            </option>
        </select>
        <p class="description">
            <?php _e('Choose what happens when a user clicks on a date or event in month view.', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_month_click_url_field(): void {
        $settings = $this->get_settings();
        $value = $settings['month_click_url'] ?? '';
        ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[month_click_url]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="https://example.com/contact or #contact-form">
        <p class="description">
            <?php _e('URL to redirect to when clicking in month view (only used if "Redirect to URL" is selected above). You can use {date} placeholder which will be replaced with the clicked date in YYYY-MM-DD format. Example: https://example.com/booking?date={date} or #contact-form', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_show_cta_button_field(): void {
        $settings = $this->get_settings();
        $value = $settings['show_cta_button'] ?? false;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_cta_button]"
                   value="1" <?php checked($value, true); ?>>
            <?php _e('Show a call-to-action button at the bottom of the calendar', 'gcal-availability'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, a button will be displayed below the calendar in all views.', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_cta_button_text_field(): void {
        $settings = $this->get_settings();
        $value = $settings['cta_button_text'] ?? '';
        ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[cta_button_text]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="Book Now">
        <p class="description">
            <?php _e('Text to display on the CTA button (e.g., "Book Now", "Contact Us", "Reserve a Table"). You can use {date} placeholder which will be replaced with the currently viewed date in YYYY-MM-DD format (in day/week views) or the first day of the month (in month view).', 'gcal-availability'); ?>
        </p>
        <?php
    }

    public function render_cta_button_url_field(): void {
        $settings = $this->get_settings();
        $value = $settings['cta_button_url'] ?? '';
        ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[cta_button_url]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="https://example.com/booking or #contact-form">
        <p class="description">
            <?php _e('URL to navigate to when the CTA button is clicked. You can use {date} placeholder which will be replaced with the currently viewed date in YYYY-MM-DD format (in day/week views) or the first day of the month (in month view). Example: https://example.com/booking?date={date} or #contact-form', 'gcal-availability'); ?>
        </p>
        <?php
    }

    /**
     * Clear all plugin caches
     */
    private function clear_all_caches(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_KEY_PREFIX . '%'
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . self::CACHE_KEY_PREFIX . '%'
            )
        );

        $this->log('All caches cleared');
    }

    public function enqueue_assets(): void {
        if (! is_singular()) {
            return;
        }

        global $post;

        if (! has_shortcode($post->post_content, 'gcal_availability_calendar')) {
            return;
        }

        wp_enqueue_style(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css',
            [],
            '6.1.15'
        );

        // Use minified files (only minified versions are included in production package)
        // For development, keep non-minified files in your local repo
        wp_enqueue_style(
            'gcal-availability',
            plugins_url('assets/css/calendar.min.css', __FILE__),
            ['fullcalendar'],
            '2.6.0'
        );

        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
            [],
            '6.1.15',
            true
        );

        wp_enqueue_script(
            'gcal-availability',
            plugins_url('assets/js/calendar.min.js', __FILE__),
            ['fullcalendar'],
            '2.6.0',
            true
        );

        $settings = $this->get_settings();

        wp_localize_script(
            'gcal-availability',
            'GcalAvailability',
            [
                'restUrl' => esc_url_raw(rest_url('gcal/v1/availability')),
                'settings' => [
                    'openingHoursStart' => $settings['opening_hours_start'] ?? '09:00',
                    'openingHoursEnd' => $settings['opening_hours_end'] ?? '17:00',
                    'hideNonBusinessHours' => $settings['hide_non_business_hours'] ?? false,
                    'monthClickAction' => $settings['month_click_action'] ?? 'day_view',
                    'monthClickUrl' => $settings['month_click_url'] ?? '',
                    'showCtaButton' => $settings['show_cta_button'] ?? false,
                    'ctaButtonText' => $settings['cta_button_text'] ?? '',
                    'ctaButtonUrl' => $settings['cta_button_url'] ?? '',
                    'enableDebug' => $settings['enable_logging'] ?? false,
                ],
                'i18n' => [
                    'loading' => __('Loading calendar...', 'gcal-availability'),
                    'error' => __('Failed to load calendar. Please try again later.', 'gcal-availability'),
                    'busy' => __('Busy', 'gcal-availability'),
                ],
            ]
        );
    }

    public function render_calendar_shortcode($atts): string {
        $atts = shortcode_atts([
            'initial_view' => 'dayGridMonth',
            'locale' => 'cs',
            'first_day' => '1',
            'custom_css' => '',
        ], $atts, 'gcal_availability_calendar');

        // Check if iCal URL is configured
        $icalUrl = $this->get_ical_url();
        if (empty($icalUrl)) {
            if (current_user_can('manage_options')) {
                return '<div class="gcal-error" style="padding: 20px; background: #fee; border: 1px solid #c33; border-radius: 4px;">'
                    . __('Calendar not configured. Please configure the iCal URL in Settings > Calendar Availability.', 'gcal-availability')
                    . '</div>';
            }
            return '<div class="gcal-error" style="padding: 20px; background: #fee; border: 1px solid #c33; border-radius: 4px;">'
                . __('Calendar is temporarily unavailable.', 'gcal-availability')
                . '</div>';
        }

        $output = '';

        // Add custom CSS if provided
        if (!empty($atts['custom_css'])) {
            $output .= '<style>' . wp_strip_all_tags($atts['custom_css']) . '</style>';
        }

        // Wrap calendar in a unique container to scope CSS
        $output .= '<div class="gcal-availability-wrapper">';
        $output .= sprintf(
            '<div id="gcal-availability-calendar" data-initial-view="%s" data-locale="%s" data-first-day="%s"></div>',
            esc_attr($atts['initial_view']),
            esc_attr($atts['locale']),
            esc_attr($atts['first_day'])
        );
        $output .= '</div>';

        return $output;
    }
}

new Gcal_Availability();
