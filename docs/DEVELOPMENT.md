# Development Guide

Quick reference for developing and deploying the GCal Availability plugin.

## 📦 Package for Deployment

Create a production-ready ZIP file:

```bash
./package.sh
```

This will:
- Extract version from `gcal-availability.php`
- Create `gcal-availability-{version}.zip`
- Include only necessary files (no docs, git files, etc.)
- Show file size and ready-to-upload message

**Output:** `gcal-availability-1.1.0.zip` (ready to upload to WordPress)

---

## 🚀 Quick Deploy

1. **Package the plugin:**
   ```bash
   ./package.sh
   ```

2. **Upload to WordPress:**
   - Go to: **WordPress Admin → Plugins → Add New → Upload Plugin**
   - Choose the generated ZIP file
   - Click **Install Now**
   - Activate the plugin

3. **Configure:**
   - Go to: **Settings → Calendar Availability**
   - Add your Google Calendar iCal URL
   - Set daily availability slots
   - Save changes

---

## 🛠️ Development Workflow

### Making Changes

1. **Edit files:**
   - `gcal-availability.php` - Main plugin logic
   - `assets/js/calendar.js` - Frontend calendar
   - `assets/css/calendar.css` - Styling

2. **Test locally:**
   - Install on local WordPress
   - Check browser console for errors
   - Test all three views (Month, Week, Day)

3. **Commit changes:**
   ```bash
   git add .
   git commit -m "feat: your change description"
   ```

4. **Package and deploy:**
   ```bash
   ./package.sh
   ```

---

## 📝 Version Bump

When releasing a new version:

1. **Update version in `gcal-availability.php`:**
   ```php
   * Version: 1.2.0
   ```

2. **Update `CHANGELOG.md`:**
   ```markdown
   ## [1.2.0] - 2025-11-16
   ### Added
   - New feature description
   ```

3. **Commit and tag:**
   ```bash
   git add .
   git commit -m "chore: bump version to 1.2.0"
   git tag v1.2.0
   git push origin main --tags
   ```

4. **Package:**
   ```bash
   ./package.sh
   ```

---

## 🧪 Testing Checklist

Before deploying:

- [ ] Month view shows green/red availability
- [ ] Week view shows booked time blocks
- [ ] Day view shows booked time blocks
- [ ] Admin settings save correctly
- [ ] Cache clearing works
- [ ] No JavaScript errors in console
- [ ] Responsive on mobile devices
- [ ] Works with different locales (en, cs, etc.)

---

## 📂 File Structure

```
gcal-availability/
├── assets/                  # Frontend assets
│   ├── css/
│   │   └── calendar.css    # Calendar styles
│   └── js/
│       └── calendar.js     # Calendar JavaScript
├── includes/               # PHP classes (future use)
├── languages/              # Translation files
├── docs/                   # Documentation (not in package)
│   ├── CHANGELOG.md
│   ├── DEPLOY.md
│   ├── DEVELOPMENT.md
│   ├── EXAMPLES.md
│   ├── INSTALL.md
│   └── QUICK-START.md
├── gcal-availability.php   # Main plugin file
├── uninstall.php          # Cleanup script
├── readme.txt             # WordPress.org format
├── README.md              # Developer docs
├── package.sh             # Packaging script
└── .gitignore             # Git ignore rules
```

**Required files for WordPress:** Main PHP file, uninstall.php, assets/, readme.txt

---

## 🐛 Debugging

Enable debug logging:

1. Go to: **Settings → Calendar Availability**
2. Check **Enable Logging**
3. Save changes
4. Check WordPress debug log for `[GCal Availability]` entries

---

## 🔗 Useful Commands

```bash
# Package plugin
./package.sh

# Check git status
git status

# View recent commits
git log --oneline -5

# Create new branch
git checkout -b feature/new-feature

# Test locally (if using Local by Flywheel or similar)
# Just copy files to: wp-content/plugins/gcal-availability/
```

---

## 💡 Tips

- Always test on a staging site before production
- Keep the iCal URL secret (it's in the database, not in code)
- Cache duration affects how quickly changes appear
- Rate limiting prevents abuse (30 requests/minute per IP)
- Month view counts events per day (multi-day events count for each day)

---

## 📞 Support

For issues or questions:
- Check browser console for JavaScript errors
- Enable debug logging in plugin settings
- Review WordPress error logs
- Check that iCal URL is accessible

