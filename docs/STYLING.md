# Calendar Styling Guide

## Default Styling

The calendar now includes **dark theme support** by default with:
- Transparent/adaptive background
- Glass morphism effect (backdrop blur)
- Semi-transparent borders that work on any background
- Text colors that inherit from your site theme
- Subtle shadows for depth

## Custom CSS via Shortcode

You can customize the calendar appearance using the `custom_css` attribute:

```
[gcal_availability_calendar custom_css="YOUR CSS HERE"]
```

## Example Customizations

### 1. Fully Transparent Calendar

```
[gcal_availability_calendar custom_css="
    .fc { 
        background: transparent !important; 
        box-shadow: none !important;
    }
"]
```

### 2. Solid Dark Background

```
[gcal_availability_calendar custom_css="
    .fc { 
        background: #1a1a1a !important; 
        border: 1px solid #333;
    }
    .fc .fc-daygrid-day {
        background-color: #0a0a0a !important;
    }
"]
```

### 3. Custom Button Colors

```
[gcal_availability_calendar custom_css="
    .fc .fc-button {
        background-color: #10b981 !important;
        border-color: #10b981 !important;
    }
    .fc .fc-button:hover {
        background-color: #059669 !important;
    }
"]
```

### 4. Larger Calendar

```
[gcal_availability_calendar custom_css="
    #gcal-availability-calendar {
        max-width: 1200px !important;
    }
"]
```

### 5. Hide Toolbar (Month/Week/Day buttons)

```
[gcal_availability_calendar custom_css="
    .fc .fc-toolbar-chunk:last-child {
        display: none !important;
    }
"]
```

### 6. Custom Availability Colors

```
[gcal_availability_calendar custom_css="
    /* Available days - blue instead of green */
    .fc-daygrid-day.fc-day-available {
        background-color: rgba(59, 130, 246, 0.3) !important;
    }
    /* Full days - orange instead of red */
    .fc-daygrid-day.fc-day-full {
        background-color: rgba(249, 115, 22, 0.3) !important;
    }
"]
```

### 7. Rounded Corners

```
[gcal_availability_calendar custom_css="
    .fc {
        border-radius: 16px !important;
    }
"]
```

### 8. Custom Font

```
[gcal_availability_calendar custom_css="
    #gcal-availability-calendar {
        font-family: 'Your Font', sans-serif !important;
    }
"]
```

### 9. Compact Mobile View

```
[gcal_availability_calendar custom_css="
    @media (max-width: 768px) {
        .fc .fc-toolbar {
            font-size: 0.8em;
        }
        .fc .fc-button {
            padding: 0.3em 0.6em;
        }
    }
"]
```

### 10. Complete Dark Theme Override

```
[gcal_availability_calendar custom_css="
    .fc {
        background: #0f172a !important;
        border: 1px solid #1e293b;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
    }
    .fc .fc-toolbar-title {
        color: #f1f5f9;
    }
    .fc .fc-col-header-cell {
        background-color: #1e293b;
        color: #94a3b8;
        border-color: #334155;
    }
    .fc .fc-daygrid-day {
        background-color: #0f172a;
        border-color: #1e293b;
    }
    .fc .fc-daygrid-day:hover {
        background-color: #1e293b;
    }
    .fc .fc-daygrid-day-number {
        color: #e2e8f0;
    }
    .fc-theme-standard td,
    .fc-theme-standard th {
        border-color: #1e293b;
    }
"]
```

## CSS Classes Reference

### Main Container
- `#gcal-availability-calendar` - Main wrapper
- `.fc` - FullCalendar root element

### Toolbar
- `.fc-toolbar` - Toolbar container
- `.fc-toolbar-title` - Month/Year title
- `.fc-button` - Navigation and view buttons

### Calendar Grid
- `.fc-daygrid-day` - Individual day cell
- `.fc-daygrid-day-number` - Day number (1, 2, 3...)
- `.fc-day-today` - Today's date
- `.fc-col-header-cell` - Day name headers (Mon, Tue, etc.)

### Events
- `.fc-event` - Event element
- `.fc-daygrid-event` - Day grid event

## Tips

1. **Use `!important`** when your site's CSS is overriding the calendar styles
2. **Test on mobile** - use responsive CSS with `@media` queries
3. **Check contrast** - ensure text is readable on your backgrounds
4. **Use browser DevTools** - inspect elements to find the right selectors
5. **Combine with shortcode attributes** - use `locale`, `initial_view`, etc.

## Need Help?

If you need custom styling that's not covered here, inspect the calendar with your browser's DevTools to find the specific CSS classes you need to target.

