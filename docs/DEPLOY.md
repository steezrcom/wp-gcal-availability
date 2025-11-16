# Deployment Guide

Step-by-step guide to deploy the GCal Availability plugin to production.

## Pre-Deployment Checklist

### 1. Verify Files
Ensure all required files are present:
```
✅ gcal-availability.php (main plugin file)
✅ gcal-availability.js (frontend JavaScript)
✅ gcal-availability.css (custom styles)
✅ uninstall.php (cleanup script)
✅ readme.txt (WordPress.org readme)
✅ README.md (developer documentation)
```

### 2. Test Locally First
- [ ] Install on local WordPress
- [ ] Configure iCal URL
- [ ] Test all calendar views
- [ ] Test on mobile
- [ ] Check browser console for errors
- [ ] Verify caching works
- [ ] Test error states

### 3. Backup Production Site
```bash
# Backup database
wp db export backup-$(date +%Y%m%d).sql

# Backup files
tar -czf backup-$(date +%Y%m%d).tar.gz wp-content/
```

## Deployment Methods

### Method 1: Manual Upload (Recommended for First Deploy)

#### Step 1: Create Plugin ZIP
```bash
# Navigate to plugin directory
cd /path/to/gcal-availability

# Create ZIP file (exclude unnecessary files)
zip -r gcal-availability.zip . \
  -x "*.git*" \
  -x "*.DS_Store" \
  -x "*.idea*" \
  -x "Archive.zip"
```

#### Step 2: Upload to WordPress
1. Log in to WordPress Admin
2. Go to **Plugins > Add New**
3. Click **Upload Plugin**
4. Choose the ZIP file
5. Click **Install Now**
6. Click **Activate Plugin**

#### Step 3: Configure Settings
1. Go to **Settings > Calendar Availability**
2. Enter your Google Calendar iCal URL
3. Set cache duration (recommended: 300 seconds)
4. Save changes

#### Step 4: Add to Page
1. Create or edit a page
2. Add shortcode: `[gcal_availability_calendar]`
3. Publish the page
4. Test the calendar

### Method 2: FTP/SFTP Upload

#### Step 1: Prepare Files
```bash
# Create clean directory
mkdir gcal-availability-deploy
cd gcal-availability-deploy

# Copy only necessary files
cp ../gcal-availability.php .
cp ../gcal-availability.js .
cp ../gcal-availability.css .
cp ../uninstall.php .
cp ../readme.txt .
```

#### Step 2: Upload via FTP
1. Connect to your server via FTP/SFTP
2. Navigate to `/wp-content/plugins/`
3. Create folder: `gcal-availability`
4. Upload all files to this folder

#### Step 3: Activate
1. Log in to WordPress Admin
2. Go to **Plugins**
3. Find "GCal Availability"
4. Click **Activate**

### Method 3: WP-CLI (For Advanced Users)

```bash
# SSH into your server
ssh user@yourserver.com

# Navigate to WordPress root
cd /var/www/html

# Install plugin from ZIP
wp plugin install /path/to/gcal-availability.zip

# Activate plugin
wp plugin activate gcal-availability

# Configure settings (optional)
wp option update gcal_availability_settings \
  '{"ical_url":"YOUR_ICAL_URL","cache_duration":300,"enable_logging":false}' \
  --format=json
```

## Post-Deployment Steps

### 1. Verify Installation
```bash
# Check plugin is active
wp plugin list --status=active | grep club-gcal

# Check for errors
tail -f wp-content/debug.log
```

### 2. Configure Plugin
- [ ] Add iCal URL in settings
- [ ] Set appropriate cache duration
- [ ] Disable debug logging (unless troubleshooting)
- [ ] Test cache clearing function

### 3. Test Functionality
- [ ] Visit page with shortcode
- [ ] Verify calendar displays
- [ ] Check events are showing
- [ ] Test on mobile device
- [ ] Test different calendar views
- [ ] Verify loading states work
- [ ] Test error handling (temporarily use invalid URL)

### 4. Performance Check
- [ ] Check page load time
- [ ] Verify caching is working (check transients in database)
- [ ] Monitor server resources
- [ ] Test with multiple concurrent users

### 5. Security Verification
- [ ] Verify iCal URL is not exposed in source code
- [ ] Test rate limiting (make rapid requests)
- [ ] Check that admin settings require proper permissions
- [ ] Verify all inputs are sanitized

## Monitoring

### Check Plugin Health
```bash
# View error log
tail -f wp-content/debug.log | grep "GCal"

# Check transients (cached data)
wp transient list | grep gcal

# Check plugin options
wp option get gcal_availability_settings
```

### Performance Monitoring
```bash
# Check database queries
wp db query "SELECT * FROM wp_options WHERE option_name LIKE '%gcal%'"

# Monitor cache hit rate (enable logging first)
grep "Cache hit" wp-content/debug.log | wc -l
grep "Cache miss" wp-content/debug.log | wc -l
```

## Troubleshooting Deployment Issues

### Plugin Not Showing in Admin
**Solution:**
```bash
# Check file permissions
chmod 644 gcal-availability.php
chmod 755 /wp-content/plugins/gcal-availability/

# Verify file ownership
chown -R www-data:www-data /wp-content/plugins/gcal-availability/
```

### Calendar Not Displaying
**Check:**
1. Is plugin activated?
2. Is shortcode correct on page?
3. Is iCal URL configured?
4. Check browser console for JavaScript errors
5. Verify FullCalendar CDN is accessible

### Events Not Loading
**Check:**
1. Is iCal URL valid and accessible?
2. Enable debug logging
3. Check error log for API errors
4. Try clearing cache
5. Verify date range includes events

### Performance Issues
**Solutions:**
1. Increase cache duration
2. Use a caching plugin (WP Super Cache, W3 Total Cache)
3. Enable object caching (Redis, Memcached)
4. Optimize database
5. Use a CDN

## Rollback Plan

If something goes wrong:

### Quick Rollback
```bash
# Deactivate plugin
wp plugin deactivate gcal-availability

# Remove plugin
wp plugin delete gcal-availability

# Restore from backup if needed
wp db import backup-YYYYMMDD.sql
```

### Manual Rollback
1. Log in to WordPress Admin
2. Go to Plugins
3. Deactivate "GCal Availability"
4. Delete the plugin
5. Restore from backup if necessary

## Production Best Practices

### 1. Use Staging Environment
- Test all changes on staging first
- Verify functionality before production deploy
- Test with production-like data

### 2. Monitor After Deployment
- Watch error logs for 24-48 hours
- Monitor server resources
- Check user feedback
- Verify analytics/metrics

### 3. Keep Backups
- Backup before every deployment
- Keep multiple backup versions
- Test backup restoration regularly

### 4. Document Changes
- Update CHANGELOG.md
- Document any custom configurations
- Note any issues encountered

### 5. Security
- Keep WordPress updated
- Use strong passwords
- Limit admin access
- Monitor for suspicious activity

## Updating the Plugin

### For Future Updates

1. **Backup First**
   ```bash
   wp db export backup-before-update.sql
   ```

2. **Deactivate Plugin**
   ```bash
   wp plugin deactivate gcal-availability
   ```

3. **Replace Files**
   - Upload new version files
   - Overwrite existing files

4. **Reactivate Plugin**
   ```bash
   wp plugin activate gcal-availability
   ```

5. **Test Thoroughly**
   - Check all functionality
   - Verify settings preserved
   - Test on frontend

## Support After Deployment

### User Training
- Provide QUICK-START.md to users
- Show how to use admin settings
- Demonstrate shortcode usage
- Explain cache management

### Documentation
- Share INSTALL.md for reference
- Provide EXAMPLES.md for use cases
- Keep README.md updated

### Maintenance
- Monitor error logs weekly
- Check for WordPress updates
- Review cache performance
- Optimize as needed

## Success Criteria

Deployment is successful when:
- ✅ Plugin is active and visible in admin
- ✅ Settings page is accessible
- ✅ Calendar displays on frontend
- ✅ Events load correctly
- ✅ No errors in console or logs
- ✅ Performance is acceptable
- ✅ Mobile display works properly
- ✅ Caching is functioning

---

**Congratulations! Your plugin is now deployed! 🚀**

