# Usage Examples

This document provides practical examples of how to use the GCal Availability plugin.

## Basic Examples

### 1. Simple Calendar Display

The most basic usage - displays a month view calendar:

```
[gcal_availability_calendar]
```

**Result:** Month view calendar in Czech language, starting on Monday.

---

### 2. English Calendar

Display the calendar in English:

```
[gcal_availability_calendar locale="en"]
```

**Supported Languages:** Any valid locale code (en, cs, de, fr, es, it, pl, ru, etc.)

---

### 3. Week View

Show a weekly schedule view:

```
[gcal_availability_calendar initial_view="timeGridWeek"]
```

**Available Views:**
- `dayGridMonth` - Month view (default)
- `timeGridWeek` - Week view with time slots
- `timeGridDay` - Single day view with time slots

---

### 4. Start Week on Sunday

For US-style calendars starting on Sunday:

```
[gcal_availability_calendar first_day="0"]
```

**Options:**
- `0` - Sunday
- `1` - Monday (default)

---

## Advanced Examples

### 5. Complete Custom Configuration

Combine multiple attributes:

```
[gcal_availability_calendar initial_view="timeGridWeek" locale="en" first_day="0"]
```

**Result:** Week view, English language, starting on Sunday.

---

### 6. Multiple Calendars on Different Pages

You can use the same shortcode on multiple pages with different configurations:

**Page 1 - "Monthly Overview":**
```
[gcal_availability_calendar initial_view="dayGridMonth" locale="en"]
```

**Page 2 - "Weekly Schedule":**
```
[gcal_availability_calendar initial_view="timeGridWeek" locale="en"]
```

**Page 3 - "Today's Availability":**
```
[gcal_availability_calendar initial_view="timeGridDay" locale="en"]
```

---

## Real-World Use Cases

### Venue Booking Page

```html
<h2>Check Venue Availability</h2>
<p>Red blocks indicate the venue is booked. White spaces are available for booking.</p>

[gcal_availability_calendar initial_view="dayGridMonth" locale="en" first_day="1"]

<p><a href="/contact">Contact us to book your event</a></p>
```

---

### Office Hours Display

```html
<h2>Office Hours & Availability</h2>
<p>View when our office is open and when staff members are available.</p>

[gcal_availability_calendar initial_view="timeGridWeek" locale="en" first_day="1"]
```

---

### Equipment Rental Schedule

```html
<h2>Equipment Rental Calendar</h2>
<p>Check when our equipment is available for rent.</p>

[gcal_availability_calendar locale="en"]

<div class="legend">
  <span style="background: #ef4444; padding: 5px 10px; color: white; border-radius: 3px;">Busy</span>
  <span style="padding: 5px 10px;">= Equipment is rented</span>
</div>
```

---

## Styling Examples

### Custom CSS for Calendar Container

Add this to your theme's custom CSS:

```css
/* Wrap calendar in a card */
#club-availability-calendar {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 1200px;
    margin: 40px auto;
}

/* Custom colors for busy events */
.fc-event {
    background-color: #dc2626 !important;
    border-color: #b91c1c !important;
}

/* Highlight today */
.fc .fc-daygrid-day.fc-day-today {
    background-color: #fef3c7 !important;
}
```

---

### Add a Legend

```html
<style>
.availability-legend {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin: 20px 0;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}
</style>

<div class="availability-legend">
    <div class="legend-item">
        <div class="legend-color" style="background: #ef4444;"></div>
        <span>Busy / Reserved</span>
    </div>
    <div class="legend-item">
        <div class="legend-color" style="background: white; border: 1px solid #ccc;"></div>
        <span>Available</span>
    </div>
</div>

[gcal_availability_calendar]
```

---

## Integration Examples

### With Contact Form

```html
<h2>Book Your Event</h2>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <div>
        <h3>Check Availability</h3>
        [gcal_availability_calendar]
    </div>
    <div>
        <h3>Request Booking</h3>
        [contact-form-7 id="123"]
    </div>
</div>
```

---

### With Pricing Table

```html
<h2>Venue Rental</h2>

<div class="pricing">
    <h3>Rates</h3>
    <ul>
        <li>Weekday: $500/day</li>
        <li>Weekend: $750/day</li>
        <li>Full Week: $2,500</li>
    </ul>
</div>

<h3>Availability Calendar</h3>
[gcal_availability_calendar locale="en"]

<p><strong>Note:</strong> Red blocks indicate dates that are already booked.</p>
```

---

## Multilingual Sites

### Using with WPML or Polylang

**English Page:**
```
[gcal_availability_calendar locale="en" first_day="0"]
```

**Czech Page:**
```
[gcal_availability_calendar locale="cs" first_day="1"]
```

**German Page:**
```
[gcal_availability_calendar locale="de" first_day="1"]
```

---

## Mobile-Optimized Example

The calendar is responsive by default, but you can add custom breakpoints:

```html
<style>
@media (max-width: 768px) {
    #club-availability-calendar {
        font-size: 14px;
    }
}
</style>

[gcal_availability_calendar]
```

---

## Testing Your Setup

### Quick Test Page

Create a test page with this content:

```html
<h1>Calendar Test Page</h1>

<h2>Month View</h2>
[gcal_availability_calendar initial_view="dayGridMonth"]

<hr>

<h2>Week View</h2>
[gcal_availability_calendar initial_view="timeGridWeek"]

<hr>

<h2>Day View</h2>
[gcal_availability_calendar initial_view="timeGridDay"]
```

This helps you see all three views and verify the plugin is working correctly.

---

## Tips & Best Practices

1. **Choose the Right View**: 
   - Month view for general availability overview
   - Week view for detailed scheduling
   - Day view for hour-by-hour availability

2. **Set Appropriate Cache Duration**:
   - High-traffic sites: 10-15 minutes
   - Low-traffic sites: 5 minutes
   - Real-time needs: 1-2 minutes (minimum 60 seconds)

3. **Use Clear Labels**:
   - Always explain what the red blocks mean
   - Add a legend if needed
   - Provide contact information for bookings

4. **Test on Mobile**:
   - The calendar is responsive
   - Test on actual devices
   - Consider simpler views for mobile

5. **Combine with Other Plugins**:
   - Contact forms for booking requests
   - Payment gateways for deposits
   - Email notifications for confirmations

