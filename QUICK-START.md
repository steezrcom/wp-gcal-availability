# Quick Start Guide

Get your calendar up and running in 5 minutes!

## ⚡ 5-Minute Setup

### Step 1: Install & Activate (1 min)
```
WordPress Admin → Plugins → Add New → Upload Plugin
→ Choose ZIP file → Install → Activate
```

### Step 2: Get Google Calendar URL (2 min)
1. Open [Google Calendar](https://calendar.google.com)
2. Click ⋮ next to your calendar
3. Settings and sharing
4. Scroll to "Integrate calendar"
5. Copy "Secret address in iCal format"

### Step 3: Configure Plugin (1 min)
```
WordPress Admin → Settings → Calendar Availability
→ Paste iCal URL → Save Changes
```

### Step 4: Add to Page (1 min)
```
Create/Edit Page → Add shortcode:
[gcal_availability_calendar]
→ Publish
```

### Step 5: Done! ✅
Visit your page to see the calendar in action.

---

## 📋 Shortcode Cheat Sheet

### Basic
```
[gcal_availability_calendar]
```

### English Calendar
```
[gcal_availability_calendar locale="en"]
```

### Week View
```
[gcal_availability_calendar initial_view="timeGridWeek"]
```

### Start on Sunday
```
[gcal_availability_calendar first_day="0"]
```

### All Options
```
[gcal_availability_calendar initial_view="dayGridMonth" locale="en" first_day="0"]
```

---

## 🎨 Shortcode Attributes

| Attribute | Options | Default |
|-----------|---------|---------|
| `initial_view` | `dayGridMonth`, `timeGridWeek`, `timeGridDay` | `dayGridMonth` |
| `locale` | `en`, `cs`, `de`, `fr`, etc. | `cs` |
| `first_day` | `0` (Sun), `1` (Mon) | `1` |

---

## 🔧 Common Tasks

### Clear Cache
```
Settings → Calendar Availability → Clear Cache button
```

### Enable Debug Logging
```
Settings → Calendar Availability → Enable Logging → Save
Check: wp-content/debug.log
```

### Change Cache Duration
```
Settings → Calendar Availability → Cache Duration → Save
Recommended: 300 seconds (5 minutes)
```

---

## ❓ Troubleshooting

### Calendar Not Showing?
✅ Check: Plugin activated?  
✅ Check: iCal URL configured?  
✅ Check: Shortcode on page?  
✅ Check: Browser console for errors  

### Events Not Displaying?
✅ Check: Events exist in date range?  
✅ Check: iCal URL is correct?  
✅ Try: Clear cache  
✅ Try: Enable debug logging  

### "Calendar not configured" Error?
✅ Go to: Settings → Calendar Availability  
✅ Add: Your iCal URL  
✅ Click: Save Changes  

---

## 🎯 Best Practices

### Security
- ✅ Keep iCal URL secret
- ✅ Don't share URL publicly
- ✅ Use HTTPS on your site

### Performance
- ✅ Use caching (5-10 minutes)
- ✅ Don't set cache too low
- ✅ Monitor server resources

### User Experience
- ✅ Add explanation text
- ✅ Include legend for colors
- ✅ Provide contact info
- ✅ Test on mobile devices

---

## 📱 Mobile Friendly

The calendar is fully responsive and works great on:
- ✅ Phones
- ✅ Tablets
- ✅ Desktops
- ✅ All screen sizes

---

## 🌍 Supported Languages

Use any valid locale code:
- `en` - English
- `cs` - Czech
- `de` - German
- `fr` - French
- `es` - Spanish
- `it` - Italian
- `pl` - Polish
- `ru` - Russian
- And many more!

---

## 🎨 Customization

### Change Busy Color
Add to your theme's CSS:
```css
.fc-event {
    background-color: #your-color !important;
}
```

### Add Legend
```html
<div style="margin: 20px 0;">
    <span style="background: #ef4444; padding: 5px 10px; color: white;">Busy</span>
    <span style="padding: 5px 10px;">Available</span>
</div>
[gcal_availability_calendar]
```

---

## 📞 Need Help?

1. Check INSTALL.md for detailed instructions
2. Check EXAMPLES.md for usage examples
3. Check README.md for technical details
4. Enable debug logging
5. Contact plugin developer

---

## ✨ Pro Tips

💡 **Tip 1:** Use month view for general availability  
💡 **Tip 2:** Use week view for detailed schedules  
💡 **Tip 3:** Set cache to 5-10 minutes for best performance  
💡 **Tip 4:** Add explanation text above calendar  
💡 **Tip 5:** Test on mobile before going live  

---

## 🚀 Next Steps

- [ ] Customize calendar appearance
- [ ] Add to multiple pages
- [ ] Set up contact form for bookings
- [ ] Test on different devices
- [ ] Monitor performance
- [ ] Adjust cache settings if needed

---

**That's it! You're ready to go! 🎉**

