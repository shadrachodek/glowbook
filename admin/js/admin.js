/**
 * Braiderholic Booking Admin Scripts
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(function() {
        initScheduleToggle();
        initBlockDateRemoval();
        initBookingActions();
        initSquareCredentialToggle();
    });

    /**
     * Toggle time inputs based on availability checkbox
     */
    function initScheduleToggle() {
        $('.sodek-gb-day-toggle').on('change', function() {
            var $row = $(this).closest('tr');
            var $inputs = $row.find('.sodek-gb-time-input');

            if ($(this).is(':checked')) {
                $inputs.prop('disabled', false).css('opacity', 1);
            } else {
                $inputs.prop('disabled', true).css('opacity', 0.5);
            }
        }).trigger('change');
    }

    /**
     * Handle blocked date removal
     */
    function initBlockDateRemoval() {
        $('.sodek-gb-remove-block').on('click', function() {
            if (!confirm(sodekGbAdmin.strings.confirmDelete)) {
                return;
            }

            var $button = $(this);
            var date = $button.data('date');
            var $row = $button.closest('tr');

            $button.prop('disabled', true).text(sodekGbAdmin.strings.saving);

            // Find the override ID (we need to query the API)
            wp.apiFetch({
                path: '/sodek-gb/v1/availability/blocked?year=' + date.substr(0, 4) + '&month=' + date.substr(5, 2),
                method: 'GET'
            }).then(function(blocked) {
                // For now, just reload the page
                location.reload();
            }).catch(function(error) {
                alert(sodekGbAdmin.strings.error);
                $button.prop('disabled', false).text('Remove');
            });
        });
    }

    /**
     * Initialize booking quick actions
     */
    function initBookingActions() {
        // Quick status change
        $('.sodek-gb-quick-status').on('change', function() {
            var $select = $(this);
            var bookingId = $select.data('booking-id');
            var newStatus = $select.val();

            wp.apiFetch({
                path: '/sodek-gb/v1/bookings/' + bookingId,
                method: 'POST',
                data: { status: newStatus }
            }).then(function(booking) {
                $select.closest('tr').find('.sodek-gb-status')
                    .removeClass()
                    .addClass('sodek-gb-status sodek-gb-status-' + newStatus)
                    .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
            }).catch(function(error) {
                alert(sodekGbAdmin.strings.error);
            });
        });

        // Send manual reminder
        $('.sodek-gb-send-reminder').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var bookingId = $button.data('booking-id');

            if (!confirm('Send a reminder email to this customer?')) {
                return;
            }

            $button.prop('disabled', true).text(sodekGbAdmin.strings.saving);

            wp.apiFetch({
                path: '/sodek-gb/v1/bookings/' + bookingId + '/reminder',
                method: 'POST'
            }).then(function(response) {
                $button.text('Sent!');
                setTimeout(function() {
                    $button.prop('disabled', false).text('Send Reminder');
                }, 2000);
            }).catch(function(error) {
                alert(sodekGbAdmin.strings.error);
                $button.prop('disabled', false).text('Send Reminder');
            });
        });
    }

    /**
     * Datepicker initialization for availability calendar
     */
    function initAvailabilityCalendar() {
        if ($('#sodek-gb-availability-calendar').length === 0) {
            return;
        }

        // This could be enhanced with a full calendar library
        // For now, using native date inputs
    }

    /**
     * Toggle Square credential fields based on source selection
     */
    function initSquareCredentialToggle() {
        var $credentialSource = $('#sodek_gb_square_credential_source');
        var $environment = $('#sodek_gb_square_environment');

        if ($credentialSource.length === 0) {
            return;
        }

        function updateCredentialVisibility() {
            var source = $credentialSource.val();
            var env = $environment.val();
            var showManual = source === 'manual';

            // Show/hide all manual config rows
            $('.sodek-gb-square-manual-config').toggle(showManual);

            // If showing manual, also handle environment toggle
            if (showManual) {
                $('.sodek-gb-square-sandbox').toggle(env === 'sandbox');
                $('.sodek-gb-square-production').toggle(env === 'production');
            }
        }

        $credentialSource.on('change', updateCredentialVisibility);
        $environment.on('change', updateCredentialVisibility);

        // Initial state is set by PHP, but ensure it's correct
        // updateCredentialVisibility();
    }

    /**
     * Square connection test
     */
    $(document).on('click', '.sodek-gb-test-square-connection', function() {
        var $button = $(this);
        var $status = $button.siblings('.sodek-gb-connection-status');

        $button.prop('disabled', true);
        $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sodek_gb_test_square_connection',
                nonce: sodekGbAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<span style="color:#46b450;">' + response.data.message + '</span>');
                } else {
                    $status.html('<span style="color:#dc3232;">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $status.html('<span style="color:#dc3232;">Connection failed. Please try again.</span>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

})(jQuery);
