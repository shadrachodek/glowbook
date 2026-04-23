<?php
/**
 * Admin Calendar View.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sodek-gb-calendar-wrap">
    <h1><?php esc_html_e( 'GlowBook Calendar', 'glowbook' ); ?></h1>

    <div class="sodek-gb-calendar-toolbar">
        <div class="sodek-gb-calendar-filters">
            <label for="sodek-gb-service-filter">
                <?php esc_html_e( 'Filter by Service:', 'glowbook' ); ?>
            </label>
            <select id="sodek-gb-service-filter">
                <option value=""><?php esc_html_e( 'All Services', 'glowbook' ); ?></option>
            </select>

            <label for="sodek-gb-status-filter">
                <?php esc_html_e( 'Filter by Status:', 'glowbook' ); ?>
            </label>
            <select id="sodek-gb-status-filter">
                <option value=""><?php esc_html_e( 'All Statuses', 'glowbook' ); ?></option>
                <option value="pending"><?php esc_html_e( 'Pending', 'glowbook' ); ?></option>
                <option value="confirmed"><?php esc_html_e( 'Confirmed', 'glowbook' ); ?></option>
                <option value="completed"><?php esc_html_e( 'Completed', 'glowbook' ); ?></option>
                <option value="cancelled"><?php esc_html_e( 'Cancelled', 'glowbook' ); ?></option>
                <option value="no-show"><?php esc_html_e( 'No-Show', 'glowbook' ); ?></option>
            </select>
        </div>

        <div class="sodek-gb-calendar-legend">
            <span class="sodek-gb-legend-item sodek-gb-legend-pending">
                <span class="sodek-gb-legend-dot"></span> <?php esc_html_e( 'Pending', 'glowbook' ); ?>
            </span>
            <span class="sodek-gb-legend-item sodek-gb-legend-confirmed">
                <span class="sodek-gb-legend-dot"></span> <?php esc_html_e( 'Confirmed', 'glowbook' ); ?>
            </span>
            <span class="sodek-gb-legend-item sodek-gb-legend-completed">
                <span class="sodek-gb-legend-dot"></span> <?php esc_html_e( 'Completed', 'glowbook' ); ?>
            </span>
            <span class="sodek-gb-legend-item sodek-gb-legend-cancelled">
                <span class="sodek-gb-legend-dot"></span> <?php esc_html_e( 'Cancelled', 'glowbook' ); ?>
            </span>
        </div>
    </div>

    <div id="sodek-gb-calendar"></div>

    <script>
    // Debug: Check if dependencies loaded
    window.addEventListener('load', function() {
        var debugInfo = [];
        debugInfo.push('FullCalendar: ' + (typeof FullCalendar !== 'undefined' ? 'LOADED' : 'NOT LOADED'));
        debugInfo.push('wp.apiFetch: ' + (typeof wp !== 'undefined' && typeof wp.apiFetch !== 'undefined' ? 'LOADED' : 'NOT LOADED'));
        debugInfo.push('sodekGbCalendar: ' + (typeof sodekGbCalendar !== 'undefined' ? 'LOADED' : 'NOT LOADED'));
        debugInfo.push('Calendar element: ' + (document.getElementById('sodek-gb-calendar') ? 'FOUND' : 'NOT FOUND'));
        console.log('GlowBook Debug:', debugInfo.join(', '));

        // Show debug info on page if there's an issue
        if (typeof FullCalendar === 'undefined') {
            var calEl = document.getElementById('sodek-gb-calendar');
            if (calEl) {
                calEl.innerHTML = '<div style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">' +
                    '<strong>Calendar Loading Issue:</strong><br>' + debugInfo.join('<br>') + '</div>';
            }
        }
    });
    </script>

    <!-- Booking Details Modal -->
    <div id="sodek-gb-booking-modal" class="sodek-gb-modal" style="display: none;">
        <div class="sodek-gb-modal-content">
            <div class="sodek-gb-modal-header">
                <h2><?php esc_html_e( 'Booking Details', 'glowbook' ); ?></h2>
                <button type="button" class="sodek-gb-modal-close">&times;</button>
            </div>
            <div class="sodek-gb-modal-body">
                <div class="sodek-gb-booking-details">
                    <div class="sodek-gb-detail-row">
                        <label><?php esc_html_e( 'Service:', 'glowbook' ); ?></label>
                        <span id="sodek-gb-modal-service"></span>
                    </div>
                    <div class="sodek-gb-detail-row">
                        <label><?php esc_html_e( 'Customer:', 'glowbook' ); ?></label>
                        <span id="sodek-gb-modal-customer"></span>
                    </div>
                    <div class="sodek-gb-detail-row">
                        <label><?php esc_html_e( 'Date & Time:', 'glowbook' ); ?></label>
                        <span id="sodek-gb-modal-datetime"></span>
                    </div>
                    <div class="sodek-gb-detail-row">
                        <label><?php esc_html_e( 'Duration:', 'glowbook' ); ?></label>
                        <span id="sodek-gb-modal-duration"></span>
                    </div>
                    <div class="sodek-gb-detail-row">
                        <label><?php esc_html_e( 'Status:', 'glowbook' ); ?></label>
                        <span id="sodek-gb-modal-status"></span>
                    </div>
                    <div class="sodek-gb-detail-row">
                        <label><?php esc_html_e( 'Deposit:', 'glowbook' ); ?></label>
                        <span id="sodek-gb-modal-deposit"></span>
                    </div>
                    <div class="sodek-gb-detail-row sodek-gb-detail-notes" id="sodek-gb-modal-notes-row" style="display: none;">
                        <label><?php esc_html_e( 'Notes:', 'glowbook' ); ?></label>
                        <span id="sodek-gb-modal-notes"></span>
                    </div>
                </div>
            </div>
            <div class="sodek-gb-modal-footer">
                <a href="#" id="sodek-gb-modal-edit" class="button button-primary">
                    <?php esc_html_e( 'Edit Booking', 'glowbook' ); ?>
                </a>
                <button type="button" id="sodek-gb-modal-confirm" class="button" style="display: none;">
                    <?php esc_html_e( 'Confirm', 'glowbook' ); ?>
                </button>
                <button type="button" id="sodek-gb-modal-complete" class="button" style="display: none;">
                    <?php esc_html_e( 'Mark Complete', 'glowbook' ); ?>
                </button>
                <button type="button" id="sodek-gb-modal-cancel" class="button" style="display: none;">
                    <?php esc_html_e( 'Cancel', 'glowbook' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>
