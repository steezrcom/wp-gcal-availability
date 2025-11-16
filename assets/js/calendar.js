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
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5, 6, 0], // Monday - Sunday
            startTime: '09:00',
            endTime: '17:00'
        },
        slotMinTime: '08:00',
        slotMaxTime: '20:00',
        slotDuration: '01:00',
        slotLabelInterval: '01:00',

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

                                // Handle all-day events differently
                                if (block.allDay) {
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
                                    start: block.start,
                                    end: block.end,
                                    allDay: block.allDay || false,
                                    backgroundColor: '#ef4444',
                                    borderColor: '#dc2626',
                                    textColor: '#ffffff',
                                    extendedProps: {
                                        timeRange: timeRange,
                                        isAllDay: block.allDay || false
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

            // Month view: show time range or "Busy" for all-day, hide title
            if (view === 'dayGridMonth') {
                // For all-day events, show just the title without dot
                if (isAllDay) {
                    return {
                        html: '<div class="fc-event-title">' + arg.event.extendedProps.timeRange + '</div>'
                    };
                }

                // For timed events, show dot + time range
                return {
                    html: '<div class="fc-daygrid-event-dot" style="border-color: ' + arg.borderColor + ';"></div>' +
                          '<div class="fc-event-time">' + arg.event.extendedProps.timeRange + '</div>'
                };
            }

            // Week/Day view: for all-day events, show title only
            if (isAllDay) {
                return {
                    html: '<div class="fc-event-title">' + (arg.event.title || '') + '</div>'
                };
            }

            // Week/Day view: for timed events, show time + title
            return {
                html: '<div class="fc-event-time">' + arg.timeText + '</div>' +
                      '<div class="fc-event-title">' + (arg.event.title || '') + '</div>'
            };
        },

        // Click on a date in month view to go to day view
        dateClick: function(info) {
            if (info.view.type === 'dayGridMonth') {
                console.log('GCal Availability: date clicked, navigating to day view:', info.dateStr);
                calendar.changeView('timeGridDay', info.date);
            }
        },

        // Click on an event in month view to go to day view
        eventClick: function(info) {
            if (info.view.type === 'dayGridMonth') {
                info.jsEvent.preventDefault(); // Prevent default action
                var eventDate = info.event.start;
                console.log('GCal Availability: event clicked, navigating to day view:', eventDate);
                calendar.changeView('timeGridDay', eventDate);
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
