<?php
/**
 * Help & Documentation page template.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap sodek-gb-help-wrap">
    <h1><?php esc_html_e( 'GlowBook Help & Documentation', 'glowbook' ); ?></h1>
    <p class="sodek-gb-help-intro"><?php esc_html_e( 'Welcome to the GlowBook documentation. Find guides and tips to help you manage your booking system effectively.', 'glowbook' ); ?></p>

    <style>
        .sodek-gb-help-wrap {
            max-width: 1200px;
        }
        .sodek-gb-help-intro {
            font-size: 14px;
            color: #50575e;
            margin-bottom: 24px;
        }
        .sodek-gb-help-tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid #c3c4c7;
            margin-bottom: 0;
        }
        .sodek-gb-help-tab {
            padding: 12px 20px;
            background: #f0f0f1;
            border: 1px solid #c3c4c7;
            border-bottom: none;
            margin-bottom: -1px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #50575e;
            transition: all 0.15s;
            border-radius: 4px 4px 0 0;
            margin-right: -1px;
        }
        .sodek-gb-help-tab:hover {
            background: #fff;
            color: #1d2327;
        }
        .sodek-gb-help-tab.active {
            background: #fff;
            color: #1d2327;
            border-bottom-color: #fff;
        }
        .sodek-gb-help-content {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-top: none;
            padding: 24px;
            border-radius: 0 0 4px 4px;
        }
        .sodek-gb-help-section {
            display: none;
        }
        .sodek-gb-help-section.active {
            display: block;
        }
        .sodek-gb-help-section h2 {
            font-size: 18px;
            color: #1d2327;
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e5e5;
        }
        .sodek-gb-help-section h3 {
            font-size: 15px;
            color: #1d2327;
            margin: 24px 0 12px 0;
        }
        .sodek-gb-help-section h3:first-of-type {
            margin-top: 0;
        }
        .sodek-gb-help-section p {
            font-size: 13px;
            line-height: 1.6;
            color: #3c434a;
            margin: 0 0 12px 0;
        }
        .sodek-gb-help-section ul, .sodek-gb-help-section ol {
            margin: 0 0 16px 20px;
            font-size: 13px;
            line-height: 1.8;
            color: #3c434a;
        }
        .sodek-gb-help-section li {
            margin-bottom: 6px;
        }
        .sodek-gb-help-card {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 16px;
        }
        .sodek-gb-help-card h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #1d2327;
        }
        .sodek-gb-help-card p {
            margin: 0;
        }
        .sodek-gb-help-tip {
            background: #e7f5ff;
            border-left: 4px solid #2271b1;
            padding: 12px 16px;
            margin: 16px 0;
            border-radius: 0 4px 4px 0;
        }
        .sodek-gb-help-tip strong {
            color: #2271b1;
        }
        .sodek-gb-help-warning {
            background: #fcf9e8;
            border-left: 4px solid #dba617;
            padding: 12px 16px;
            margin: 16px 0;
            border-radius: 0 4px 4px 0;
        }
        .sodek-gb-help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin: 16px 0;
        }
        .sodek-gb-quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            text-decoration: none;
            color: #1d2327;
            transition: all 0.15s;
        }
        .sodek-gb-quick-link:hover {
            border-color: #2271b1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .sodek-gb-quick-link .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: #2271b1;
        }
        .sodek-gb-quick-link span {
            font-weight: 500;
        }
        .sodek-gb-version-info {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #757575;
        }
        .sodek-gb-status-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .sodek-gb-status-table th,
        .sodek-gb-status-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
            font-size: 13px;
        }
        .sodek-gb-status-table th {
            background: #f6f7f7;
            font-weight: 600;
        }
        .sodek-gb-status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .sodek-gb-status-pending { background: #fff3cd; color: #856404; }
        .sodek-gb-status-confirmed { background: #d4edda; color: #155724; }
        .sodek-gb-status-completed { background: #cce5ff; color: #004085; }
        .sodek-gb-status-cancelled { background: #f8d7da; color: #721c24; }
    </style>

    <div class="sodek-gb-help-tabs">
        <div class="sodek-gb-help-tab active" data-tab="getting-started"><?php esc_html_e( 'Getting Started', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="bookings"><?php esc_html_e( 'Managing Bookings', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="services"><?php esc_html_e( 'Services & Add-ons', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="payments"><?php esc_html_e( 'Payments', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="calendar"><?php esc_html_e( 'Calendar & Availability', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="shortcuts"><?php esc_html_e( 'Quick Reference', 'glowbook' ); ?></div>
    </div>

    <div class="sodek-gb-help-content">

        <!-- Getting Started -->
        <div class="sodek-gb-help-section active" id="getting-started">
            <h2><?php esc_html_e( 'Getting Started with GlowBook', 'glowbook' ); ?></h2>

            <h3><?php esc_html_e( 'Quick Links', 'glowbook' ); ?></h3>
            <div class="sodek-gb-help-grid">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sodek-gb-dashboard' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-dashboard"></span>
                    <span><?php esc_html_e( 'Dashboard', 'glowbook' ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sodek_gb_booking' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span><?php esc_html_e( 'Add New Booking', 'glowbook' ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sodek-gb-calendar' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php esc_html_e( 'View Calendar', 'glowbook' ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sodek-gb-settings' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span><?php esc_html_e( 'Settings', 'glowbook' ); ?></span>
                </a>
            </div>

            <h3><?php esc_html_e( 'Dashboard Overview', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'The Dashboard is your command center. It shows:', 'glowbook' ); ?></p>
            <ul>
                <li><strong><?php esc_html_e( 'Appointments Today', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Number of bookings scheduled for today', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'This Week', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Total bookings for the current week', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Awaiting Confirmation', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Bookings that need your approval', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Deposits This Month', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Total deposits collected', 'glowbook' ); ?></li>
            </ul>

            <div class="sodek-gb-help-tip">
                <strong><?php esc_html_e( 'Tip:', 'glowbook' ); ?></strong> <?php esc_html_e( 'Check your dashboard at the start of each day to see your schedule and any pending bookings that need confirmation.', 'glowbook' ); ?>
            </div>
        </div>

        <!-- Managing Bookings -->
        <div class="sodek-gb-help-section" id="bookings">
            <h2><?php esc_html_e( 'Managing Bookings', 'glowbook' ); ?></h2>

            <h3><?php esc_html_e( 'Creating a New Booking', 'glowbook' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Click "Add New Booking" from the dashboard or GlowBook menu', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Select the service from the dropdown', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Choose the date and time', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Enter customer information (name, email, phone)', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Set the booking status', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Click "Publish" to save', 'glowbook' ); ?></li>
            </ol>

            <h3><?php esc_html_e( 'Booking Statuses', 'glowbook' ); ?></h3>
            <table class="sodek-gb-status-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'glowbook' ); ?></th>
                        <th><?php esc_html_e( 'Meaning', 'glowbook' ); ?></th>
                        <th><?php esc_html_e( 'When to Use', 'glowbook' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="sodek-gb-status-badge sodek-gb-status-pending"><?php esc_html_e( 'Pending', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'Awaiting confirmation', 'glowbook' ); ?></td>
                        <td><?php esc_html_e( 'New online bookings waiting for your approval', 'glowbook' ); ?></td>
                    </tr>
                    <tr>
                        <td><span class="sodek-gb-status-badge sodek-gb-status-confirmed"><?php esc_html_e( 'Confirmed', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'Appointment is confirmed', 'glowbook' ); ?></td>
                        <td><?php esc_html_e( 'After you approve a booking or receive deposit', 'glowbook' ); ?></td>
                    </tr>
                    <tr>
                        <td><span class="sodek-gb-status-badge sodek-gb-status-completed"><?php esc_html_e( 'Completed', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'Service has been delivered', 'glowbook' ); ?></td>
                        <td><?php esc_html_e( 'After the appointment is finished', 'glowbook' ); ?></td>
                    </tr>
                    <tr>
                        <td><span class="sodek-gb-status-badge sodek-gb-status-cancelled"><?php esc_html_e( 'Cancelled', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'Booking was cancelled', 'glowbook' ); ?></td>
                        <td><?php esc_html_e( 'When customer or you cancel the appointment', 'glowbook' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <h3><?php esc_html_e( 'Recording Payments', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'On the booking edit screen, use the Payment Details box to:', 'glowbook' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Enter the payment amount', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Click "Full $X" to auto-fill the remaining balance', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Select the payment method', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Click "Record Payment" to save', 'glowbook' ); ?></li>
            </ol>
        </div>

        <!-- Services & Add-ons -->
        <div class="sodek-gb-help-section" id="services">
            <h2><?php esc_html_e( 'Services & Add-ons', 'glowbook' ); ?></h2>

            <h3><?php esc_html_e( 'Creating Services', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Services are what customers book. To create a service:', 'glowbook' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to GlowBook → Services → Add New', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Enter the service name and description', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Set the price and duration', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Configure deposit requirements if needed', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Assign to a category', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Publish the service', 'glowbook' ); ?></li>
            </ol>

            <h3><?php esc_html_e( 'Service Add-ons', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Add-ons are extras customers can select with their booking (e.g., hair products, extensions).', 'glowbook' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Go to GlowBook → Add-ons → Add New', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Enter the add-on name and price', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Link it to specific services or make it global', 'glowbook' ); ?></li>
            </ol>

            <div class="sodek-gb-help-tip">
                <strong><?php esc_html_e( 'Tip:', 'glowbook' ); ?></strong> <?php esc_html_e( 'Use categories to organize services (e.g., "Braids", "Locs", "Treatments") so customers can easily find what they need.', 'glowbook' ); ?>
            </div>
        </div>

        <!-- Payments -->
        <div class="sodek-gb-help-section" id="payments">
            <h2><?php esc_html_e( 'Payments & Transactions', 'glowbook' ); ?></h2>

            <h3><?php esc_html_e( 'Payment Methods', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'GlowBook supports multiple payment methods:', 'glowbook' ); ?></p>
            <ul>
                <li><strong><?php esc_html_e( 'Online Payments', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Square integration for card payments', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Cash', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Record cash payments at appointment', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Card Reader', 'glowbook' ); ?></strong> - <?php esc_html_e( 'In-person card payments', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Bank Transfer', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Direct bank transfers', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Cash App / Zelle', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Mobile payment apps', 'glowbook' ); ?></li>
            </ul>

            <h3><?php esc_html_e( 'Viewing Transactions', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Go to GlowBook → Transactions to see all payment records including:', 'glowbook' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Transaction reference and booking ID', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Amount and payment method', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Customer information', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Payment status', 'glowbook' ); ?></li>
            </ul>

            <h3><?php esc_html_e( 'Deposit Settings', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Configure deposits in Settings → Payment Settings:', 'glowbook' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Set deposit as fixed amount or percentage', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Override deposit per service if needed', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Enable/disable deposit requirement', 'glowbook' ); ?></li>
            </ul>
        </div>

        <!-- Calendar & Availability -->
        <div class="sodek-gb-help-section" id="calendar">
            <h2><?php esc_html_e( 'Calendar & Availability', 'glowbook' ); ?></h2>

            <h3><?php esc_html_e( 'Using the Calendar', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'The calendar view shows all your bookings:', 'glowbook' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Switch between Month, Week, Day, and List views', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Click on a booking to view/edit details', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Filter by service or status', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Colors indicate booking status (pending, confirmed, etc.)', 'glowbook' ); ?></li>
            </ul>

            <h3><?php esc_html_e( 'Setting Availability', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Go to GlowBook → Availability to configure:', 'glowbook' ); ?></p>
            <ul>
                <li><strong><?php esc_html_e( 'Business Hours', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Set your working hours for each day', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Blocked Dates', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Mark specific dates as unavailable (holidays, vacations)', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Buffer Time', 'glowbook' ); ?></strong> - <?php esc_html_e( 'Add break time between appointments', 'glowbook' ); ?></li>
            </ul>

            <div class="sodek-gb-help-warning">
                <strong><?php esc_html_e( 'Important:', 'glowbook' ); ?></strong> <?php esc_html_e( 'Remember to block dates when you\'re unavailable to prevent customers from booking during those times.', 'glowbook' ); ?>
            </div>
        </div>

        <!-- Quick Reference -->
        <div class="sodek-gb-help-section" id="shortcuts">
            <h2><?php esc_html_e( 'Quick Reference', 'glowbook' ); ?></h2>

            <h3><?php esc_html_e( 'Common Tasks', 'glowbook' ); ?></h3>
            <div class="sodek-gb-help-grid">
                <div class="sodek-gb-help-card">
                    <h4><?php esc_html_e( 'Confirm a Pending Booking', 'glowbook' ); ?></h4>
                    <p><?php esc_html_e( 'Edit booking → Change status to "Confirmed" → Update', 'glowbook' ); ?></p>
                </div>
                <div class="sodek-gb-help-card">
                    <h4><?php esc_html_e( 'Record a Payment', 'glowbook' ); ?></h4>
                    <p><?php esc_html_e( 'Edit booking → Payment Details → Enter amount → Record Payment', 'glowbook' ); ?></p>
                </div>
                <div class="sodek-gb-help-card">
                    <h4><?php esc_html_e( 'Reschedule Appointment', 'glowbook' ); ?></h4>
                    <p><?php esc_html_e( 'Edit booking → Change date/time → Update', 'glowbook' ); ?></p>
                </div>
                <div class="sodek-gb-help-card">
                    <h4><?php esc_html_e( 'Block a Date', 'glowbook' ); ?></h4>
                    <p><?php esc_html_e( 'Availability → Add Blocked Date → Select date → Save', 'glowbook' ); ?></p>
                </div>
            </div>

            <h3><?php esc_html_e( 'Before Each Appointment', 'glowbook' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Check the booking details and customer notes', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Verify deposit has been paid', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Review any add-ons selected', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Confirm appointment time with customer if needed', 'glowbook' ); ?></li>
            </ol>

            <h3><?php esc_html_e( 'After Each Appointment', 'glowbook' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Record any remaining payment', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Change status to "Completed"', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Add any notes about the appointment', 'glowbook' ); ?></li>
            </ol>

            <div class="sodek-gb-version-info">
                <?php
                printf(
                    /* translators: %s: plugin version */
                    esc_html__( 'GlowBook Version %s', 'glowbook' ),
                    esc_html( SODEK_GB_VERSION )
                );
                ?>
                &nbsp;&bull;&nbsp;
                <a href="https://shadrachodek.com" target="_blank"><?php esc_html_e( 'Support & Contact', 'glowbook' ); ?></a>
            </div>
        </div>

    </div>

    <script>
    jQuery(function($) {
        $('.sodek-gb-help-tab').on('click', function() {
            var tabId = $(this).data('tab');

            // Update tab states
            $('.sodek-gb-help-tab').removeClass('active');
            $(this).addClass('active');

            // Update section visibility
            $('.sodek-gb-help-section').removeClass('active');
            $('#' + tabId).addClass('active');
        });
    });
    </script>
</div>
