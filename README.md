# GCal Availability

A production-ready WordPress plugin that displays Google Calendar availability with a modern, interactive interface.

## Features

- 🗓️ **Beautiful Calendar UI** - Powered by FullCalendar 6.x
- ⚡ **Smart Caching** - Configurable transient caching (default: 5 minutes)
- 🔒 **Secure** - Rate limiting, input validation, and WordPress security best practices
- 🌍 **Internationalized** - Multi-language support with translation-ready code
- 📱 **Responsive** - Works perfectly on all devices
- ⚙️ **Easy Configuration** - WordPress admin interface for settings
- 🎨 **Customizable** - Shortcode attributes for flexible display options
- 📊 **Debug Logging** - Optional error logging for troubleshooting
- ⏰ **Timezone Aware** - Proper handling of timezones and all-day events

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Go to **Settings > Calendar Availability**
4. Enter your Google Calendar secret iCal URL
5. Configure cache duration and other settings
6. Add the shortcode to any page: `[club_availability_calendar]`

## Getting Your Google Calendar iCal URL

1. Open [Google Calendar](https://calendar.google.com)
2. Click the three dots next to your calendar name
3. Select **Settings and sharing**
4. Scroll to **Integrate calendar**
5. Copy the **Secret address in iCal format**

⚠️ **Important:** Keep this URL secret! Anyone with this URL can view your calendar events.

## Usage

### Basic Shortcode

```
[gcal_availability_calendar]
```

### With Custom Attributes

```
[gcal_availability_calendar initial_view="timeGridWeek" locale="en" first_day="0"]
```

### Available Attributes

| Attribute | Default | Options | Description |
|-----------|---------|---------|-------------|
| `initial_view` | `dayGridMonth` | `dayGridMonth`, `timeGridWeek`, `timeGridDay` | Initial calendar view |
| `locale` | `cs` | Any valid locale code | Language for calendar interface |
| `first_day` | `1` | `0` (Sunday), `1` (Monday) | First day of the week |

## Configuration

### Admin Settings

Navigate to **Settings > Calendar Availability** to configure:

- **iCal URL** - Your Google Calendar secret iCal link
- **Cache Duration** - How long to cache calendar data (minimum 60 seconds)
- **Enable Logging** - Turn on debug logging to WordPress error log

### Cache Management

Use the **Clear Cache** button in admin settings to force a fresh fetch of calendar data.

## Technical Details

### Caching Strategy

- Calendar data is cached using WordPress transients
- Default cache duration: 5 minutes (300 seconds)
- Configurable via admin settings (minimum 60 seconds)
- Cache keys are based on date range to optimize performance

### Rate Limiting

- Maximum 30 requests per minute per IP address
- Prevents abuse and excessive API calls
- Returns HTTP 429 when limit exceeded

### Security Features

- Input sanitization and validation
- WordPress nonce verification for admin actions
- Rate limiting on REST API endpoints
- Secure URL handling
- No hardcoded credentials

### REST API Endpoint

```
GET /wp-json/gcal/v1/availability?start=2025-01-01&end=2025-01-31
```

**Parameters:**
- `start` (required) - Start date in Y-m-d format
- `end` (required) - End date in Y-m-d format
- Maximum range: 90 days

**Response:**
```json
[
  {
    "start": "2025-01-15T10:00:00+00:00",
    "end": "2025-01-15T11:00:00+00:00"
  }
]
```

## Development

### File Structure

```
gcal-availability/
├── assets/
│   ├── css/
│   │   └── calendar.css    # Calendar styles
│   └── js/
│       └── calendar.js     # Calendar JavaScript
├── includes/               # PHP classes (future use)
├── languages/              # Translation files
├── docs/                   # Documentation
├── gcal-availability.php   # Main plugin file
├── uninstall.php          # Cleanup on uninstall
├── readme.txt             # WordPress.org readme
├── README.md              # Developer documentation
└── package.sh             # Packaging script
```

### Requirements

- WordPress 5.8+
- PHP 7.4+
- FullCalendar 6.x (loaded via CDN)

### Debugging

Enable logging in **Settings > Calendar Availability** to write debug messages to the WordPress error log.

View logs:
```bash
tail -f wp-content/debug.log
```

## Troubleshooting

### Calendar not displaying

1. Check that iCal URL is configured in settings
2. Verify the iCal URL is accessible
3. Enable logging and check error log
4. Clear cache and try again

### Events not showing

1. Verify date range includes events
2. Check timezone settings in Google Calendar
3. Clear plugin cache
4. Enable logging to see API responses

### Performance issues

1. Increase cache duration in settings
2. Reduce date range displayed
3. Check server resources

## Support

For issues, questions, or contributions, please contact the developer or open an issue in the repository.

## License

GPLv2 or later

## Credits

- Built with [FullCalendar](https://fullcalendar.io/)
- Developed for WordPress

