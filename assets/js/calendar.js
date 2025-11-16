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

    // Track current view for event fetching
    var currentCalendarView = initialView;

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
        loading: function(isLoading) {
            if (isLoading) {
                console.log('GCal Availability: loading events...');
            } else {
                console.log('GCal Availability: events loaded');
            }
        },
        // Use events function directly instead of eventSources array
        events: function (info, successCallback, failureCallback) {
                    var startDate = info.startStr.slice(0, 10); // "2025-10-27"
                    var endDate = info.endStr.slice(0, 10);     // "2025-12-08"
                    // Get current view type - use tracked variable as fallback
                    var viewType = (info.view && info.view.type) ? info.view.type : currentCalendarView;

                    var url = GcalAvailability.restUrl
                        + '?start=' + encodeURIComponent(startDate)
                        + '&end=' + encodeURIComponent(endDate)
                        + '&view=' + encodeURIComponent(viewType);

                    console.log('GCal Availability: fetching', url, 'for view:', viewType);

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
                            console.log('GCal Availability: current view type:', viewType);

                            var events = [];

                            // Month view: show day-level availability (background color only)
                            if (viewType === 'dayGridMonth') {
                                console.log('GCal Availability: processing MONTH view data');
                                events = (data || []).map(function (day) {
                                    console.log('GCal Availability: day', day.date, 'available:', day.available);
                                    return {
                                        start: day.date,
                                        allDay: true,
                                        display: 'background',
                                        // Higher opacity for better visibility on dark backgrounds
                                        backgroundColor: day.available ? 'rgba(16, 185, 129, 0.5)' : 'rgba(239, 68, 68, 0.5)',
                                        borderColor: 'transparent',
                                        classNames: [day.available ? 'gcal-available-day' : 'gcal-full-day']
                                    };
                                });
                            }
                            // Week/Day view: show actual busy blocks
                            else {
                                console.log('GCal Availability: processing WEEK/DAY view data');
                                events = (data || []).map(function (block) {
                                    console.log('GCal Availability: busy block', block.start, 'to', block.end);
                                    return {
                                        title: GcalAvailability.i18n.busy || 'Busy',
                                        start: block.start,
                                        end: block.end,
                                        backgroundColor: '#ef4444',
                                        borderColor: '#dc2626',
                                        textColor: '#ffffff',
                                        display: 'block'
                                    };
                                });
                            }

                            console.log('GCal Availability: total events created:', events.length);
                            console.log('GCal Availability: events passed to FullCalendar', events);

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
        // Responsive
        windowResize: function() {
            calendar.updateSize();
        },
        // Track view changes and force refetch
        datesSet: function(info) {
            console.log('GCal Availability: view/dates changed to', info.view.type);
            // Update tracked view
            currentCalendarView = info.view.type;
            // FullCalendar will automatically refetch events
        }
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
