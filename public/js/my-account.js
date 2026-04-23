/**
 * GlowBook - My Account Scripts
 */

(function($) {
    'use strict';

    var SodekGbMyAccount = {
        selectedDate: null,
        selectedTime: null,
        bookingId: null,
        serviceId: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Open reschedule modal
            $(document).on('click', '.sodek-gb-reschedule-btn', this.openRescheduleModal.bind(this));

            // Close modal
            $(document).on('click', '.sodek-gb-modal-close, .sodek-gb-modal-overlay, .sodek-gb-modal-cancel', this.closeModal.bind(this));

            // Date selection
            $(document).on('change', '#sodek-gb-reschedule-date', this.onDateSelect.bind(this));

            // Time slot selection
            $(document).on('click', '#sodek-gb-reschedule-time-slots .sodek-gb-time-slot', this.onTimeSelect.bind(this));

            // Confirm reschedule
            $(document).on('click', '#sodek-gb-confirm-reschedule', this.confirmReschedule.bind(this));

            // Escape key to close modal
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            }.bind(this));
        },

        openRescheduleModal: function(e) {
            e.preventDefault();
            var $btn = $(e.target);

            this.bookingId = $btn.data('booking-id');
            this.serviceId = $btn.data('service-id');
            this.selectedDate = null;
            this.selectedTime = null;

            $('#sodek-gb-reschedule-booking-id').val(this.bookingId);
            $('#sodek-gb-reschedule-service-id').val(this.serviceId);
            $('#sodek-gb-reschedule-date').val('');
            $('#sodek-gb-reschedule-time-slots').html('');
            $('.sodek-gb-reschedule-slots').hide();
            $('#sodek-gb-confirm-reschedule').prop('disabled', true);

            $('#sodek-gb-reschedule-modal').fadeIn(200);
            $('body').addClass('sodek-gb-modal-open');
        },

        closeModal: function(e) {
            if (e && $(e.target).hasClass('sodek-gb-modal-content')) {
                return;
            }
            $('#sodek-gb-reschedule-modal').fadeOut(200);
            $('body').removeClass('sodek-gb-modal-open');
        },

        onDateSelect: function(e) {
            var date = $(e.target).val();
            if (!date) return;

            this.selectedDate = date;
            this.selectedTime = null;
            $('#sodek-gb-confirm-reschedule').prop('disabled', true);

            this.loadTimeSlots(date);
        },

        loadTimeSlots: function(date) {
            var self = this;
            var $slots = $('#sodek-gb-reschedule-time-slots');
            var $container = $('.sodek-gb-reschedule-slots');

            $container.show();
            $slots.html('<div class="sodek-gb-loading">' + sodekGbMyAccount.strings.loading + '</div>');

            $.ajax({
                url: sodekGbMyAccount.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sodek_gb_get_available_slots',
                    nonce: sodekGbMyAccount.nonce,
                    booking_id: this.bookingId,
                    date: date
                },
                success: function(response) {
                    if (response.success && response.data.slots && response.data.slots.length > 0) {
                        self.renderTimeSlots(response.data.slots);
                    } else {
                        $slots.html('<div class="sodek-gb-no-slots">' + sodekGbMyAccount.strings.noSlots + '</div>');
                    }
                },
                error: function() {
                    $slots.html('<div class="sodek-gb-error">Error loading time slots</div>');
                }
            });
        },

        renderTimeSlots: function(slots) {
            var html = '';
            for (var i = 0; i < slots.length; i++) {
                var slot = slots[i];
                html += '<div class="sodek-gb-time-slot" data-time="' + slot.start + '">';
                html += this.formatTime(slot.start);
                html += '</div>';
            }
            $('#sodek-gb-reschedule-time-slots').html(html);
        },

        onTimeSelect: function(e) {
            var $slot = $(e.target);
            this.selectedTime = $slot.data('time');

            $('#sodek-gb-reschedule-time-slots .sodek-gb-time-slot').removeClass('selected');
            $slot.addClass('selected');

            $('#sodek-gb-confirm-reschedule').prop('disabled', false);
        },

        confirmReschedule: function(e) {
            e.preventDefault();

            if (!this.selectedDate || !this.selectedTime) {
                alert(sodekGbMyAccount.strings.selectTime);
                return;
            }

            if (!confirm(sodekGbMyAccount.strings.confirmReschedule)) {
                return;
            }

            var self = this;
            var $btn = $(e.target);
            $btn.prop('disabled', true).text(sodekGbMyAccount.strings.loading);

            $.ajax({
                url: sodekGbMyAccount.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sodek_gb_reschedule_booking',
                    nonce: sodekGbMyAccount.nonce,
                    booking_id: this.bookingId,
                    new_date: this.selectedDate,
                    new_time: this.selectedTime
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page with success message
                        window.location.href = window.location.pathname + '?sodek_gb_message=rescheduled';
                    } else {
                        alert(response.data.message || 'Error rescheduling booking');
                        $btn.prop('disabled', false).text('Confirm Reschedule');
                    }
                },
                error: function() {
                    alert('Error rescheduling booking');
                    $btn.prop('disabled', false).text('Confirm Reschedule');
                }
            });
        },

        formatTime: function(timeStr) {
            var parts = timeStr.split(':');
            var hours = parseInt(parts[0], 10);
            var minutes = parts[1];
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            return hours + ':' + minutes + ' ' + ampm;
        }
    };

    $(function() {
        SodekGbMyAccount.init();
    });

})(jQuery);
