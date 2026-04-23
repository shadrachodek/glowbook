/**
 * Braiderholic Booking Admin Calendar
 */

(function() {
    'use strict';

    var calendar;
    var currentBooking = null;
    var serviceFilter = '';
    var statusFilter = '';

    document.addEventListener('DOMContentLoaded', function() {
        console.log('GlowBook Calendar: Initializing...');
        console.log('GlowBook Calendar: FullCalendar loaded:', typeof FullCalendar !== 'undefined');
        console.log('GlowBook Calendar: wp.apiFetch loaded:', typeof wp !== 'undefined' && typeof wp.apiFetch !== 'undefined');
        console.log('GlowBook Calendar: Config:', typeof sodekGbCalendar !== 'undefined' ? sodekGbCalendar : 'NOT DEFINED');

        try {
            initCalendar();
            initFilters();
            initModal();
            populateServiceFilter();
        } catch (e) {
            console.error('GlowBook Calendar: Error initializing:', e);
        }
    });

    function initCalendar() {
        var calendarEl = document.getElementById('sodek-gb-calendar');
        if (!calendarEl) return;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            views: {
                timeGridWeek: {
                    slotMinTime: '07:00:00',
                    slotMaxTime: '22:00:00',
                    slotDuration: '00:30:00'
                },
                timeGridDay: {
                    slotMinTime: '07:00:00',
                    slotMaxTime: '22:00:00',
                    slotDuration: '00:30:00'
                }
            },
            editable: true,
            droppable: false,
            eventDurationEditable: false,
            events: fetchEvents,
            eventClick: handleEventClick,
            eventDrop: handleEventDrop,
            eventDidMount: function(info) {
                // Add status class for styling
                info.el.classList.add('sodek-gb-status-' + info.event.extendedProps.status);
            },
            loading: function(isLoading) {
                if (isLoading) {
                    calendarEl.classList.add('sodek-gb-loading');
                } else {
                    calendarEl.classList.remove('sodek-gb-loading');
                }
            },
            eventContent: function(arg) {
                var event = arg.event;
                var timeText = arg.timeText;
                var view = arg.view.type;

                var html = '<div class="sodek-gb-event-content">';

                if (view.indexOf('timeGrid') > -1) {
                    html += '<div class="sodek-gb-event-time">' + timeText + '</div>';
                    html += '<div class="sodek-gb-event-title">' + event.title + '</div>';
                    html += '<div class="sodek-gb-event-service">' + (event.extendedProps.serviceName || '') + '</div>';
                } else if (view === 'listWeek') {
                    html += '<span class="sodek-gb-event-title">' + event.title + '</span>';
                    html += ' - <span class="sodek-gb-event-service">' + (event.extendedProps.serviceName || '') + '</span>';
                } else {
                    // Month view
                    if (timeText) {
                        html += '<span class="sodek-gb-event-time">' + timeText + '</span> ';
                    }
                    html += '<span class="sodek-gb-event-title">' + event.title + '</span>';
                }

                html += '</div>';

                return { html: html };
            }
        });

        calendar.render();
    }

    function fetchEvents(info, successCallback, failureCallback) {
        console.log('GlowBook Calendar: fetchEvents called with:', info.startStr, 'to', info.endStr);

        // Build the REST API path (not full URL)
        var path = 'sodek-gb/v1/bookings/calendar?start=' + info.startStr + '&end=' + info.endStr;

        if (serviceFilter) {
            path += '&service_id=' + serviceFilter;
        }
        if (statusFilter) {
            path += '&status=' + statusFilter;
        }

        console.log('GlowBook Calendar: Fetching from path:', path);

        wp.apiFetch({
            path: path
        }).then(function(events) {
            console.log('GlowBook Calendar: Received events:', events);
            var calendarEvents = events.map(function(booking) {
                return {
                    id: booking.id,
                    title: booking.customer_name,
                    start: booking.date + 'T' + booking.start_time,
                    end: booking.date + 'T' + booking.end_time,
                    backgroundColor: sodekGbCalendar.statusColors[booking.status] || '#999',
                    borderColor: sodekGbCalendar.statusColors[booking.status] || '#999',
                    extendedProps: {
                        bookingId: booking.id,
                        status: booking.status,
                        serviceId: booking.service_id,
                        serviceName: booking.service_name,
                        customerEmail: booking.customer_email,
                        customerPhone: booking.customer_phone,
                        duration: booking.duration,
                        depositAmount: booking.deposit_amount,
                        depositPaid: booking.deposit_paid,
                        notes: booking.notes
                    }
                };
            });
            successCallback(calendarEvents);
        }).catch(function(error) {
            console.error('GlowBook Calendar: Error fetching events:', error);
            console.error('GlowBook Calendar: Path was:', path);
            console.error('GlowBook Calendar: Error details:', JSON.stringify(error));
            failureCallback(error);
        });
    }

    function handleEventClick(info) {
        var event = info.event;
        var props = event.extendedProps;

        currentBooking = {
            id: props.bookingId,
            status: props.status
        };

        // Populate modal
        document.getElementById('sodek-gb-modal-service').textContent = props.serviceName || '-';
        document.getElementById('sodek-gb-modal-customer').innerHTML =
            event.title + '<br><small>' + (props.customerEmail || '') +
            (props.customerPhone ? ' | ' + props.customerPhone : '') + '</small>';
        document.getElementById('sodek-gb-modal-datetime').textContent =
            formatDate(event.start) + ' at ' + formatTime(event.start) + ' - ' + formatTime(event.end);
        document.getElementById('sodek-gb-modal-duration').textContent = props.duration + ' minutes';
        document.getElementById('sodek-gb-modal-status').innerHTML =
            '<span class="sodek-gb-status-badge sodek-gb-status-' + props.status + '">' +
            props.status.charAt(0).toUpperCase() + props.status.slice(1) + '</span>';
        document.getElementById('sodek-gb-modal-deposit').textContent =
            formatCurrency(props.depositAmount) + (props.depositPaid ? ' (Paid)' : ' (Unpaid)');

        // Notes
        var notesRow = document.getElementById('sodek-gb-modal-notes-row');
        var notesSpan = document.getElementById('sodek-gb-modal-notes');
        if (props.notes) {
            notesSpan.textContent = props.notes;
            notesRow.style.display = '';
        } else {
            notesRow.style.display = 'none';
        }

        // Edit link
        document.getElementById('sodek-gb-modal-edit').href = sodekGbCalendar.editUrl + props.bookingId;

        // Show/hide action buttons based on status
        var confirmBtn = document.getElementById('sodek-gb-modal-confirm');
        var completeBtn = document.getElementById('sodek-gb-modal-complete');
        var cancelBtn = document.getElementById('sodek-gb-modal-cancel');

        confirmBtn.style.display = props.status === 'pending' ? '' : 'none';
        completeBtn.style.display = props.status === 'confirmed' ? '' : 'none';
        cancelBtn.style.display = (props.status === 'pending' || props.status === 'confirmed') ? '' : 'none';

        // Show modal
        document.getElementById('sodek-gb-booking-modal').style.display = 'flex';
    }

    function handleEventDrop(info) {
        var event = info.event;
        var props = event.extendedProps;
        var newDate = event.start.toISOString().split('T')[0];
        var newTime = event.start.toTimeString().split(' ')[0].substring(0, 5);

        var confirmMsg = sodekGbCalendar.strings.confirmReschedule
            .replace('%s', formatDate(event.start))
            .replace('%s', formatTime(event.start));

        if (!confirm(confirmMsg)) {
            info.revert();
            return;
        }

        wp.apiFetch({
            path: 'sodek-gb/v1/bookings/' + props.bookingId + '/reschedule',
            method: 'POST',
            data: {
                date: newDate,
                time: newTime
            }
        }).then(function(response) {
            if (response.success) {
                showNotice(sodekGbCalendar.strings.rescheduleSuccess, 'success');
            } else {
                info.revert();
                showNotice(response.message || sodekGbCalendar.strings.rescheduleFailed, 'error');
            }
        }).catch(function(error) {
            info.revert();
            showNotice(error.message || sodekGbCalendar.strings.rescheduleFailed, 'error');
        });
    }

    function initFilters() {
        var serviceSelect = document.getElementById('sodek-gb-service-filter');
        var statusSelect = document.getElementById('sodek-gb-status-filter');

        if (serviceSelect) {
            serviceSelect.addEventListener('change', function() {
                serviceFilter = this.value;
                calendar.refetchEvents();
            });
        }

        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                statusFilter = this.value;
                calendar.refetchEvents();
            });
        }
    }

    function populateServiceFilter() {
        var select = document.getElementById('sodek-gb-service-filter');
        if (!select || !sodekGbCalendar.services) return;

        sodekGbCalendar.services.forEach(function(service) {
            var option = document.createElement('option');
            option.value = service.id;
            option.textContent = service.title;
            select.appendChild(option);
        });
    }

    function initModal() {
        var modal = document.getElementById('sodek-gb-booking-modal');
        if (!modal) return;

        // Close button
        modal.querySelector('.sodek-gb-modal-close').addEventListener('click', closeModal);

        // Click outside to close
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Action buttons
        document.getElementById('sodek-gb-modal-confirm').addEventListener('click', function() {
            updateBookingStatus('confirmed');
        });

        document.getElementById('sodek-gb-modal-complete').addEventListener('click', function() {
            updateBookingStatus('completed');
        });

        document.getElementById('sodek-gb-modal-cancel').addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                updateBookingStatus('cancelled');
            }
        });

        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });
    }

    function closeModal() {
        document.getElementById('sodek-gb-booking-modal').style.display = 'none';
        currentBooking = null;
    }

    function updateBookingStatus(newStatus) {
        if (!currentBooking) return;

        wp.apiFetch({
            path: 'sodek-gb/v1/bookings/' + currentBooking.id + '/status',
            method: 'POST',
            data: { status: newStatus }
        }).then(function(response) {
            if (response.success) {
                showNotice('Booking status updated to ' + newStatus, 'success');
                closeModal();
                calendar.refetchEvents();
            } else {
                showNotice(response.message || 'Failed to update status', 'error');
            }
        }).catch(function(error) {
            showNotice(error.message || 'Failed to update status', 'error');
        });
    }

    function formatDate(date) {
        return date.toLocaleDateString(undefined, {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    function formatTime(date) {
        return date.toLocaleTimeString(undefined, {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: 'USD'
        }).format(amount || 0);
    }

    function showNotice(message, type) {
        var notice = document.createElement('div');
        notice.className = 'notice notice-' + type + ' is-dismissible sodek-gb-notice';
        notice.innerHTML = '<p>' + message + '</p>';

        var wrap = document.querySelector('.sodek-gb-calendar-wrap');
        wrap.insertBefore(notice, wrap.firstChild);

        setTimeout(function() {
            notice.style.opacity = '0';
            setTimeout(function() {
                notice.remove();
            }, 300);
        }, 3000);
    }

})();
