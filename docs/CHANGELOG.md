# Changelog

All notable changes to the GCal Availability plugin will be documented in this file.

## [1.1.0] - 2025-11-15

### Added
- **Admin Settings Page**: Easy-to-use WordPress admin interface for configuration
  - Configure iCal URL without editing code
  - Adjustable cache duration
  - Debug logging toggle
  - Cache management tools
- **Caching System**: Smart transient-based caching
  - Configurable cache duration (default: 5 minutes)
  - Reduces API calls to Google Calendar
  - Improves page load performance
  - Cache clearing functionality
- **Security Improvements**:
  - Rate limiting (30 requests per minute per IP)
  - Input validation and sanitization
  - WordPress nonce verification
  - Secure REST API endpoints
- **Enhanced Error Handling**:
  - User-friendly error messages
  - Optional debug logging to WordPress error log
  - Graceful fallbacks for missing configuration
  - Better error feedback in admin
- **Improved iCal Parsing**:
  - Support for all-day events
  - Better timezone handling
  - Support for TZID parameters
  - More robust date parsing
- **Shortcode Customization**:
  - `initial_view` attribute for different calendar views
  - `locale` attribute for language selection
  - `first_day` attribute for week start day
- **Better UI/UX**:
  - Loading states with animations
  - Error messages with helpful information
  - Improved calendar styling
  - Responsive design improvements
  - Custom CSS for better appearance
- **Plugin Structure**:
  - Proper uninstall.php for cleanup
  - Comprehensive readme.txt for WordPress.org
  - Developer documentation (README.md)
  - Changelog tracking
  - .gitignore file

### Changed
- Moved hardcoded iCal URL to admin settings
- Improved REST API with better validation
- Enhanced JavaScript with better error handling
- Updated version to 1.1.0
- Improved code organization and documentation

### Fixed
- Timezone handling for events
- All-day event display
- Error handling for network failures
- Cache key collisions
- Rate limiting edge cases

### Security
- Added rate limiting to prevent abuse
- Implemented input validation on all endpoints
- Added nonce verification for admin actions
- Sanitized all user inputs
- Validated date formats

## [1.0.3] - 2025-11-14

### Added
- Initial release
- Basic iCal parsing from Google Calendar
- FullCalendar integration
- REST API endpoint for availability data
- Shortcode for displaying calendar
- Czech locale support
- Basic event display

### Features
- Read events from Google Calendar .ics URL
- Display busy blocks in calendar view
- Month view with FullCalendar
- Simple shortcode implementation

