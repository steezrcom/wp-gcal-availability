# Installation Guide

## Quick Start

### 1. Install the Plugin

**Option A: Manual Installation**
1. Download or clone this repository
2. Upload the entire folder to `/wp-content/plugins/`
3. The folder should be named `gcal-availability`

**Option B: ZIP Installation**
1. Create a ZIP file of the plugin folder
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"

### 2. Activate the Plugin

1. Go to WordPress Admin > Plugins
2. Find "GCal Availability"
3. Click "Activate"

### 3. Get Your Google Calendar iCal URL

1. Open [Google Calendar](https://calendar.google.com)
2. Find the calendar you want to display
3. Click the three dots (⋮) next to the calendar name
4. Select **"Settings and sharing"**
5. Scroll down to **"Integrate calendar"** section
6. Find **"Secret address in iCal format"**
7. Click the copy icon to copy the URL

**Important:** This URL is secret! Anyone with this URL can view your calendar events. Don't share it publicly.

### 4. Configure the Plugin

1. Go to WordPress Admin > **Settings > Calendar Availability**
2. Paste your iCal URL in the **"iCal URL"** field
3. (Optional) Adjust the **Cache Duration** (default: 300 seconds / 5 minutes)
4. (Optional) Enable **Debug Logging** if you need to troubleshoot
5. Click **"Save Changes"**

### 5. Add the Calendar to a Page

1. Create a new page or edit an existing one
2. Add the shortcode:
   ```
   [gcal_availability_calendar]
   ```
3. Publish or update the page
4. View the page to see your calendar!

## Advanced Configuration

### Shortcode Attributes

Customize the calendar appearance with these attributes:

```
[gcal_availability_calendar initial_view="dayGridMonth" locale="cs" first_day="1"]
```

**Available Attributes:**

| Attribute | Default | Options | Description |
|-----------|---------|---------|-------------|
| `initial_view` | `dayGridMonth` | `dayGridMonth`, `timeGridWeek`, `timeGridDay` | Initial calendar view |
| `locale` | `cs` | `en`, `cs`, `de`, `fr`, etc. | Language code |
| `first_day` | `1` | `0` (Sunday), `1` (Monday) | First day of week |

**Examples:**

English calendar starting on Sunday:
```
[gcal_availability_calendar locale="en" first_day="0"]
```

Week view in German:
```
[gcal_availability_calendar initial_view="timeGridWeek" locale="de"]
```

### Cache Management

**Why Caching?**
- Reduces load on Google's servers
- Improves page load speed
- Prevents rate limiting

**Adjusting Cache Duration:**
1. Go to Settings > Calendar Availability
2. Change "Cache Duration" value (minimum 60 seconds)
3. Lower values = more up-to-date data, but more API calls
4. Higher values = better performance, but less frequent updates

**Clearing Cache:**
1. Go to Settings > Calendar Availability
2. Scroll to "Cache Management"
3. Click "Clear Cache" button
4. This forces a fresh fetch of calendar data

### Debug Logging

If you're experiencing issues:

1. Enable logging in Settings > Calendar Availability
2. Check your WordPress debug log at `wp-content/debug.log`
3. Look for entries starting with `[GCal Availability]`

To enable WordPress debug logging, add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Troubleshooting

### Calendar Not Showing

**Check:**
1. Is the plugin activated?
2. Is the iCal URL configured in settings?
3. Is the shortcode correct on the page?
4. Check browser console for JavaScript errors

### Events Not Displaying

**Check:**
1. Does your Google Calendar have events in the date range?
2. Is the iCal URL correct and accessible?
3. Try clearing the plugin cache
4. Enable debug logging and check for errors

### "Calendar not configured" Error

**Solution:**
1. Go to Settings > Calendar Availability
2. Make sure the iCal URL field is filled in
3. Save the settings

### Performance Issues

**Solutions:**
1. Increase cache duration (e.g., 600 seconds = 10 minutes)
2. Use a caching plugin for WordPress
3. Optimize your hosting environment

## Security Best Practices

1. **Keep iCal URL Secret**: Never share your secret iCal URL publicly
2. **Regular Updates**: Keep WordPress and the plugin updated
3. **Backup**: Regularly backup your WordPress site
4. **Monitor Logs**: Check logs periodically for suspicious activity

## Uninstalling

To completely remove the plugin:

1. Deactivate the plugin in WordPress Admin > Plugins
2. Click "Delete" on the plugin
3. All settings and cached data will be automatically removed

## Support

If you need help:
1. Check this installation guide
2. Review the README.md for technical details
3. Enable debug logging to diagnose issues
4. Contact the plugin developer

## Next Steps

- Customize the calendar appearance with CSS
- Adjust shortcode attributes for your needs
- Set up multiple pages with different calendar views
- Monitor performance and adjust cache settings

