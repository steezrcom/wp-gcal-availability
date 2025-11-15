=== GCal Availability ===
Contributors: yourname
Tags: calendar, google calendar, availability, booking, schedule
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display Google Calendar availability with a beautiful, modern calendar interface showing busy/free time blocks.

== Description ==

GCal Availability is a WordPress plugin that reads events from a Google Calendar (via secret .ics link) and displays them in a modern, interactive calendar interface. Perfect for showing availability for bookings, appointments, or venue scheduling.

**Features:**

* 🗓️ Beautiful, responsive calendar interface powered by FullCalendar
* 🔒 Secure integration with Google Calendar via private .ics URL
* ⚡ Smart caching system for optimal performance
* 🌍 Multi-language support (default: Czech)
* 📱 Fully responsive design
* ⚙️ Easy configuration via WordPress admin
* 🎨 Customizable via shortcode attributes
* 🔐 Rate limiting to prevent abuse
* 📊 Optional debug logging
* ⏰ Timezone-aware event handling
* 📅 Support for all-day events

**Use Cases:**

* Show venue availability for bookings
* Display office hours and busy times
* Share team member availability
* Show equipment rental availability
* Display meeting room schedules

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/club-gcal-availability/` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Calendar Availability
4. Enter your Google Calendar secret iCal URL
5. Configure cache duration and other settings
6. Use the shortcode `[club_availability_calendar]` on any page or post

**Getting your Google Calendar iCal URL:**

1. Open Google Calendar
2. Click the three dots next to your calendar
3. Select "Settings and sharing"
4. Scroll to "Integrate calendar"
5. Copy the "Secret address in iCal format"

== Frequently Asked Questions ==

= How do I get my Google Calendar iCal URL? =

1. Open Google Calendar
2. Click settings (gear icon) > Settings
3. Select your calendar from the left sidebar
4. Scroll to "Integrate calendar"
5. Copy the "Secret address in iCal format"

= Can I customize the calendar appearance? =

Yes! Use shortcode attributes:
`[gcal_availability_calendar initial_view="dayGridMonth" locale="en" first_day="0"]`

Available views: dayGridMonth, timeGridWeek, timeGridDay
Locale: any valid locale code (cs, en, de, fr, etc.)
First day: 0 (Sunday) or 1 (Monday)

= How often does the calendar update? =

The plugin caches calendar data for 5 minutes by default (configurable in settings). This reduces load on Google's servers and improves performance.

= Does it support recurring events? =

The plugin displays events as they appear in the iCal feed. Google Calendar expands recurring events in the iCal export.

= Can I show multiple calendars? =

Currently, the plugin supports one calendar per installation. You can combine multiple Google Calendars into one in Google Calendar settings.

= Is it secure? =

Yes! The plugin uses WordPress security best practices including:
- Rate limiting
- Input sanitization and validation
- Nonce verification for admin actions
- Secure API endpoints

== Screenshots ==

1. Calendar display showing busy time blocks
2. Admin settings page
3. Month view with events
4. Week view with detailed time slots

== Changelog ==

= 1.1.0 =
* Added admin settings page for easy configuration
* Implemented caching system for better performance
* Added rate limiting to prevent abuse
* Improved error handling and user feedback
* Added support for all-day events
* Improved timezone handling
* Added shortcode attributes for customization
* Added loading states and error messages
* Added debug logging option
* Added cache management tools
* Improved security with input validation
* Added proper uninstall cleanup

= 1.0.3 =
* Initial release
* Basic iCal parsing
* FullCalendar integration
* Czech locale support

== Upgrade Notice ==

= 1.1.0 =
Major update with admin interface, caching, and security improvements. Please configure your iCal URL in Settings > Calendar Availability after updating.

== Support ==

For support, please visit the plugin's support forum or contact the developer.

== Privacy Policy ==

This plugin fetches calendar data from Google Calendar using the iCal URL you provide. No data is sent to third parties. Calendar data is temporarily cached on your WordPress server.

