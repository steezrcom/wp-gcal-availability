// Helper function to convert time string (HH:MM) to minutes since midnight
function timeToMinutes(timeStr) {
    var parts = timeStr.split(':');
    return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
}

// Helper function to convert minutes since midnight to time string (HH:MM)
function minutesToTime(minutes) {
    // Handle wrapping around midnight
    minutes = minutes % (24 * 60);
    if (minutes < 0) minutes += 24 * 60;

    var hours = Math.floor(minutes / 60);
    var mins = minutes % 60;
    return hours.toString().padStart(2, '0') + ':' + mins.toString().padStart(2, '0');
}

// Helper function to subtract an hour from a time string (HH:MM)
function subtractHour(timeStr) {
    var minutes = timeToMinutes(timeStr);
    return minutesToTime(minutes - 60);
}

// Helper function to add an hour to a time string (HH:MM)
function addHour(timeStr) {
    var minutes = timeToMinutes(timeStr);
    return minutesToTime(minutes + 60);
}

document.addEventListener('DOMContentLoaded', function () {
    var element = document.getElementById('gcal-availability-calendar');

    if (!element) {
        console.error('GCal Availability: element #gcal-availability-calendar not found');
        return;
    }

    if (typeof FullCalendar === 'undefined') {
        console.error('GCal Availability: FullCalendar is not loaded');
        return;
    }

    console.log('GCal Availability: init calendar on', element);

    // Get configuration from data attributes
    var initialView = element.getAttribute('data-initial-view') || 'dayGridMonth';
    var locale = element.getAttribute('data-locale') || 'cs';
    var firstDay = parseInt(element.getAttribute('data-first-day') || '1', 10);

    // Get opening hours from settings (passed from PHP)
    var openingStart = GcalAvailability.settings.openingHoursStart || '09:00';
    var openingEnd = GcalAvailability.settings.openingHoursEnd || '17:00';
    var hideNonBusinessHours = GcalAvailability.settings.hideNonBusinessHours || false;

    // Check if hours cross midnight (e.g., 10:00 to 02:00 for nightclub)
    var startMinutes = timeToMinutes(openingStart);
    var endMinutes = timeToMinutes(openingEnd);
    var crossesMidnight = endMinutes < startMinutes;

    // Calculate slot times
    var slotMinTime, slotMaxTime;

    if (crossesMidnight) {
        // For midnight-crossing hours (e.g., 10:00-02:00), show full day
        // This is the best approach since FullCalendar doesn't support
        // slotMinTime/slotMaxTime crossing midnight
        slotMinTime = '00:00';
        slotMaxTime = '24:00';
        console.log('GCal Availability: opening hours', openingStart, 'to', openingEnd,
                    '(crosses midnight - showing full day)');
    } else if (hideNonBusinessHours) {
        // Hide non-business hours: only show business hours (no buffer)
        slotMinTime = openingStart;
        slotMaxTime = openingEnd;
        console.log('GCal Availability: opening hours', openingStart, 'to', openingEnd,
                    '(hiding non-business hours)');
    } else {
        // Normal hours: show 1 hour before/after
        slotMinTime = subtractHour(openingStart);
        slotMaxTime = addHour(openingEnd);
        console.log('GCal Availability: opening hours', openingStart, 'to', openingEnd);
    }

    console.log('GCal Availability: slot times', slotMinTime, 'to', slotMaxTime);

    // Show loading state
    element.innerHTML = '<div class="gcal-loading" style="padding: 40px; text-align: center; color: #666;">' +
        '<div style="font-size: 18px; margin-bottom: 10px;">⏳</div>' +
        '<div>' + (GcalAvailability.i18n.loading || 'Loading calendar...') + '</div>' +
        '</div>';

    var calendar = new FullCalendar.Calendar(element, {
        initialView: initialView,
        height: 'auto',
        firstDay: firstDay,
        locale: locale,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: locale === 'cs' ? 'Dnes' : 'Today',
            month: locale === 'cs' ? 'Měsíc' : 'Month',
            week: locale === 'cs' ? 'Týden' : 'Week',
            day: locale === 'cs' ? 'Den' : 'Day'
        },
        // Show only business hours in week/day views
        businessHours: crossesMidnight ? [
            // For midnight-crossing hours (e.g., 10:00-02:00), split into two blocks
            {
                daysOfWeek: [1, 2, 3, 4, 5, 6, 0], // All days
                startTime: openingStart,  // e.g., 10:00
                endTime: '24:00'          // Until midnight
            },
            {
                daysOfWeek: [1, 2, 3, 4, 5, 6, 0], // All days (next day)
                startTime: '00:00',       // From midnight
                endTime: openingEnd       // e.g., 02:00
            }
        ] : {
            // Normal hours (e.g., 09:00-17:00)
            daysOfWeek: [1, 2, 3, 4, 5, 6, 0], // Monday - Sunday
            startTime: openingStart,
            endTime: openingEnd
        },
        slotMinTime: slotMinTime,
        slotMaxTime: slotMaxTime,
        slotDuration: '01:00',
        slotLabelInterval: '01:00',
        slotMinHeight: 30,  // Compress time slots to 30px height (default is ~40px)

        // Compact view settings
        expandRows: false,
        contentHeight: 'auto',

        // Mobile responsive
        handleWindowResize: true,
        windowResizeDelay: 100,
        loading: function(isLoading) {
            if (isLoading) {
                console.log('GCal Availability: loading events...');
            } else {
                console.log('GCal Availability: events loaded');
            }
        },
        // Simple event source - just show busy blocks
        events: function (info, successCallback, failureCallback) {
                    // Safety check - info should always be defined
                    if (!info || !info.startStr || !info.endStr) {
                        console.error('GCal Availability: invalid info object', info);
                        failureCallback(new Error('Invalid date range'));
                        return;
                    }

                    var startDate = info.startStr.slice(0, 10);
                    var endDate = info.endStr.slice(0, 10);

                    var url = GcalAvailability.restUrl
                        + '?start=' + encodeURIComponent(startDate)
                        + '&end=' + encodeURIComponent(endDate);

                    console.log('GCal Availability: fetching', url);

                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                if (response.status === 429) {
                                    throw new Error('Too many requests. Please wait a moment and try again.');
                                }
                                throw new Error('Network response was not ok, status ' + response.status);
                            }
                            return response.json();
                        })
                        .then(function (data) {
                            // Check if response is an error object
                            if (data && data.error) {
                                throw new Error(data.error);
                            }

                            console.log('GCal Availability: data from API', data);

                            // Simply show all events as busy blocks
                            var events = (data || []).map(function (block) {
                                console.log('GCal Availability: busy block', block.start, 'to', block.end, 'allDay:', block.allDay);

                                var timeRange;
                                var title;
                                var eventStart = block.start;
                                var eventEnd = block.end;
                                var isAllDay = block.allDay || false;

                                // Handle all-day events differently
                                if (isAllDay) {
                                    // For all-day events, convert to full-day timed event (00:00 to 23:59)
                                    // This makes them appear as big blocks in week/day view instead of in the all-day row
                                    var startDate = new Date(block.start);
                                    var endDate = new Date(block.end);

                                    // Set start to 00:00 of the start date
                                    eventStart = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate(), 0, 0, 0).toISOString();

                                    // Set end to 23:59 of the day before the end date (since iCal end dates are exclusive)
                                    endDate.setDate(endDate.getDate() - 1);
                                    eventEnd = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate(), 23, 59, 59).toISOString();

                                    timeRange = GcalAvailability.i18n.busy || 'Busy';
                                    title = GcalAvailability.i18n.busy || 'Busy';
                                } else {
                                    // Format time range for display
                                    var startTime = new Date(block.start);
                                    var endTime = new Date(block.end);
                                    var startHour = startTime.getHours().toString().padStart(2, '0');
                                    var startMin = startTime.getMinutes().toString().padStart(2, '0');
                                    var endHour = endTime.getHours().toString().padStart(2, '0');
                                    var endMin = endTime.getMinutes().toString().padStart(2, '0');
                                    timeRange = startHour + ':' + startMin + ' - ' + endHour + ':' + endMin;
                                    title = GcalAvailability.i18n.busy || 'Busy';
                                }

                                return {
                                    title: title,
                                    start: eventStart,
                                    end: eventEnd,
                                    allDay: false,  // Always false - we convert all-day to timed events
                                    backgroundColor: '#ef4444',
                                    borderColor: '#dc2626',
                                    textColor: '#ffffff',
                                    extendedProps: {
                                        timeRange: timeRange,
                                        isAllDay: isAllDay  // Keep track of original all-day status
                                    }
                                };
                            });

                            console.log('GCal Availability: total events:', events.length);

                            successCallback(events);
                        })
                        .catch(function (error) {
                            console.error('GCal Availability: error loading availability', error);

                            // Show error message to user
                            var errorMsg = GcalAvailability.i18n.error || 'Failed to load calendar. Please try again later.';
                            element.innerHTML = '<div class="gcal-error" style="padding: 20px; background: #fee; border: 1px solid #c33; border-radius: 4px; color: #c33; text-align: center;">' +
                                '<div style="font-size: 18px; margin-bottom: 10px;">⚠️</div>' +
                                '<div>' + errorMsg + '</div>' +
                                '<div style="margin-top: 10px; font-size: 12px; color: #999;">' + error.message + '</div>' +
                                '</div>';

                            failureCallback(error);
                        });
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        // Styling
        eventClassNames: 'gcal-busy-event',
        dayMaxEvents: true,
        nowIndicator: true,
        // Customize event content
        eventContent: function(arg) {
            var view = arg.view.type;
            var isAllDay = arg.event.extendedProps.isAllDay;

            console.log('Event content:', {
                title: arg.event.title,
                view: view,
                isAllDay: isAllDay,
                timeRange: arg.event.extendedProps.timeRange
            });

            // Month view: show dot + time range or "Busy" for all-day
            if (view === 'dayGridMonth') {
                // For all-day events, show dot + "Obsazeno"
                if (isAllDay) {
                    return {
                        html: '<div class="fc-daygrid-event-dot" style="border-color: ' + arg.borderColor + ';"></div>' +
                              '<div class="fc-event-time">' + arg.event.extendedProps.timeRange + '</div>'
                    };
                }

                // For timed events, show dot + time range
                return {
                    html: '<div class="fc-daygrid-event-dot" style="border-color: ' + arg.borderColor + ';"></div>' +
                          '<div class="fc-event-time">' + arg.event.extendedProps.timeRange + '</div>'
                };
            }

            // Week/Day view: for all-day events (now displayed as full-day blocks), show title only
            if (isAllDay) {
                return {
                    html: '<div class="fc-event-title" style="text-align: center; font-weight: bold;">' +
                          (arg.event.title || '') + '</div>'
                };
            }

            // Week/Day view: for timed events, show time + title
            return {
                html: '<div class="fc-event-time">' + arg.timeText + '</div>' +
                      '<div class="fc-event-title">' + (arg.event.title || '') + '</div>'
            };
        },

        // Click on a date in month view
        dateClick: function(info) {
            if (info.view.type === 'dayGridMonth') {
                var clickAction = GcalAvailability.settings.monthClickAction || 'day_view';
                var clickUrl = GcalAvailability.settings.monthClickUrl || '';

                if (clickAction === 'redirect' && clickUrl) {
                    // Replace {date} placeholder with clicked date
                    var url = clickUrl.replace('{date}', info.dateStr);
                    console.log('GCal Availability: date clicked, redirecting to:', url);

                    // Check if it's an anchor link
                    if (url.startsWith('#')) {
                        // Scroll to anchor
                        var element = document.querySelector(url);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth' });
                        }
                    } else {
                        // Navigate to URL
                        window.location.href = url;
                    }
                } else {
                    // Default: switch to day view
                    console.log('GCal Availability: date clicked, navigating to day view:', info.dateStr);
                    calendar.changeView('timeGridDay', info.date);
                }
            }
        },

        // Click on an event in month view
        eventClick: function(info) {
            if (info.view.type === 'dayGridMonth') {
                info.jsEvent.preventDefault(); // Prevent default action

                var clickAction = GcalAvailability.settings.monthClickAction || 'day_view';
                var clickUrl = GcalAvailability.settings.monthClickUrl || '';
                var eventDate = info.event.start;

                // Format date as YYYY-MM-DD
                var dateStr = eventDate.toISOString().split('T')[0];

                if (clickAction === 'redirect' && clickUrl) {
                    // Replace {date} placeholder with event date
                    var url = clickUrl.replace('{date}', dateStr);
                    console.log('GCal Availability: event clicked, redirecting to:', url);

                    // Check if it's an anchor link
                    if (url.startsWith('#')) {
                        // Scroll to anchor
                        var element = document.querySelector(url);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth' });
                        }
                    } else {
                        // Navigate to URL
                        window.location.href = url;
                    }
                } else {
                    // Default: switch to day view
                    console.log('GCal Availability: event clicked, navigating to day view:', eventDate);
                    calendar.changeView('timeGridDay', eventDate);
                }
            }
        },

        // Responsive
        windowResize: function() {
            calendar.updateSize();
        },

    });

    try {
        calendar.render();
        console.log('GCal Availability: calendar rendered successfully');
    } catch (error) {
        console.error('GCal Availability: failed to render calendar', error);
        element.innerHTML = '<div class="gcal-error" style="padding: 20px; background: #fee; border: 1px solid #c33; border-radius: 4px; color: #c33; text-align: center;">' +
            '<div style="font-size: 18px; margin-bottom: 10px;">⚠️</div>' +
            '<div>Failed to initialize calendar.</div>' +
            '</div>';
    }
});
