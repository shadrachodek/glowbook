<?php
/**
 * Help & Documentation page template.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

$plugin_url = SODEK_GB_PLUGIN_URL;
?>

<div class="wrap sodek-gb-help-wrap">
    <h1><?php esc_html_e( 'GlowBook Help', 'glowbook' ); ?></h1>
    <p class="sodek-gb-help-intro"><?php esc_html_e( 'Simple guides to help you manage your appointments. Click any topic below to learn more.', 'glowbook' ); ?></p>

    <style>
        .sodek-gb-help-wrap {
            max-width: 900px;
        }
        .sodek-gb-help-intro {
            font-size: 15px;
            color: #50575e;
            margin-bottom: 24px;
        }
        .sodek-gb-help-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            border-bottom: 2px solid #2271b1;
            margin-bottom: 0;
        }
        .sodek-gb-help-tab {
            padding: 14px 20px;
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-bottom: none;
            margin-bottom: -2px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #50575e;
            transition: all 0.15s;
            border-radius: 6px 6px 0 0;
            margin-right: -1px;
        }
        .sodek-gb-help-tab:hover {
            background: #fff;
            color: #1d2327;
        }
        .sodek-gb-help-tab.active {
            background: #fff;
            color: #2271b1;
            border-color: #2271b1;
            border-bottom-color: #fff;
            font-weight: 600;
        }
        .sodek-gb-help-content {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-top: none;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .sodek-gb-help-section {
            display: none;
        }
        .sodek-gb-help-section.active {
            display: block;
        }
        .sodek-gb-help-section h2 {
            font-size: 20px;
            color: #1d2327;
            margin: 0 0 8px 0;
        }
        .sodek-gb-help-section .section-intro {
            font-size: 14px;
            color: #646970;
            margin: 0 0 24px 0;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        .sodek-gb-help-section h3 {
            font-size: 16px;
            color: #1d2327;
            margin: 28px 0 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sodek-gb-help-section h3:first-of-type {
            margin-top: 0;
        }
        .sodek-gb-help-section h3 .dashicons {
            color: #2271b1;
        }
        .sodek-gb-help-section p {
            font-size: 14px;
            line-height: 1.7;
            color: #3c434a;
            margin: 0 0 14px 0;
        }
        .sodek-gb-help-section ul, .sodek-gb-help-section ol {
            margin: 0 0 20px 0;
            padding-left: 24px;
            font-size: 14px;
            line-height: 1.9;
            color: #3c434a;
        }
        .sodek-gb-help-section li {
            margin-bottom: 10px;
        }
        .sodek-gb-help-section li strong {
            color: #1d2327;
        }
        .sodek-gb-step-guide {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 16px 0 24px 0;
        }
        .sodek-gb-step-guide ol {
            margin: 0;
            padding-left: 20px;
        }
        .sodek-gb-step-guide li {
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        .sodek-gb-step-guide li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .sodek-gb-help-tip {
            background: linear-gradient(135deg, #e7f5ff 0%, #dbeafe 100%);
            border-left: 4px solid #2271b1;
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            font-size: 14px;
        }
        .sodek-gb-help-tip strong {
            color: #2271b1;
            display: block;
            margin-bottom: 4px;
        }
        .sodek-gb-help-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #d97706;
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            font-size: 14px;
        }
        .sodek-gb-help-warning strong {
            color: #92400e;
        }
        .sodek-gb-quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin: 20px 0;
        }
        .sodek-gb-quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: #fff;
            border: 2px solid #e5e5e5;
            border-radius: 10px;
            text-decoration: none;
            color: #1d2327;
            transition: all 0.2s;
        }
        .sodek-gb-quick-link:hover {
            border-color: #2271b1;
            background: #f0f6fc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .sodek-gb-quick-link .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
            color: #2271b1;
        }
        .sodek-gb-quick-link span {
            font-weight: 600;
            font-size: 14px;
        }
        .sodek-gb-version-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            font-size: 13px;
            color: #757575;
        }
        .sodek-gb-status-example {
            display: inline-flex;
            align-items: center;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .sodek-gb-simple-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 14px;
        }
        .sodek-gb-simple-table th,
        .sodek-gb-simple-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }
        .sodek-gb-simple-table th {
            background: #f6f7f7;
            font-weight: 600;
            color: #1d2327;
        }
        .sodek-gb-simple-table tr:hover td {
            background: #f9f9f9;
        }
        .sodek-gb-visual-guide {
            background: #fff;
            border: 2px dashed #c3c4c7;
            border-radius: 8px;
            padding: 40px;
            margin: 20px 0;
            text-align: center;
            color: #646970;
        }
        .sodek-gb-visual-guide .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #c3c4c7;
            margin-bottom: 12px;
        }
        @media (max-width: 782px) {
            .sodek-gb-help-tabs {
                flex-direction: column;
            }
            .sodek-gb-help-tab {
                border-radius: 0;
                margin-right: 0;
                border-bottom: 1px solid #c3c4c7;
            }
            .sodek-gb-help-tab:first-child {
                border-radius: 6px 6px 0 0;
            }
            .sodek-gb-help-content {
                padding: 20px;
            }
        }
    </style>

    <div class="sodek-gb-help-tabs">
        <div class="sodek-gb-help-tab active" data-tab="overview"><?php esc_html_e( 'Overview', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="bookings"><?php esc_html_e( 'Bookings', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="payments"><?php esc_html_e( 'Payments', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="calendar"><?php esc_html_e( 'Calendar', 'glowbook' ); ?></div>
        <div class="sodek-gb-help-tab" data-tab="faq"><?php esc_html_e( 'Common Questions', 'glowbook' ); ?></div>
    </div>

    <div class="sodek-gb-help-content">

        <!-- Overview -->
        <div class="sodek-gb-help-section active" id="overview">
            <h2><?php esc_html_e( 'Welcome to GlowBook', 'glowbook' ); ?></h2>
            <p class="section-intro"><?php esc_html_e( 'GlowBook helps you manage your appointments. Here\'s where to find everything.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Quick Links', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Click any button below to go directly to that page:', 'glowbook' ); ?></p>

            <div class="sodek-gb-quick-links">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sodek-gb-dashboard' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-dashboard"></span>
                    <span><?php esc_html_e( 'Dashboard', 'glowbook' ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sodek_gb_booking' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span><?php esc_html_e( 'New Booking', 'glowbook' ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sodek-gb-calendar' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php esc_html_e( 'Calendar', 'glowbook' ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sodek-gb-settings' ) ); ?>" class="sodek-gb-quick-link">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span><?php esc_html_e( 'Settings', 'glowbook' ); ?></span>
                </a>
            </div>

            <h3><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Your Dashboard', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'The Dashboard is your home page. When you log in, you\'ll see:', 'glowbook' ); ?></p>
            <ul>
                <li><strong><?php esc_html_e( 'Today\'s Appointments', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'How many clients are booked for today', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'This Week', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'Total appointments this week', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Needs Confirmation', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'New bookings waiting for you to approve', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Recent Deposits', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'Money collected this month', 'glowbook' ); ?></li>
            </ul>

            <div class="sodek-gb-help-tip">
                <strong><?php esc_html_e( 'Tip: Start Here Each Day', 'glowbook' ); ?></strong>
                <?php esc_html_e( 'Check your Dashboard first thing each morning to see your schedule and handle any new booking requests.', 'glowbook' ); ?>
            </div>
        </div>

        <!-- Bookings -->
        <div class="sodek-gb-help-section" id="bookings">
            <h2><?php esc_html_e( 'Managing Bookings', 'glowbook' ); ?></h2>
            <p class="section-intro"><?php esc_html_e( 'Learn how to create, edit, and manage your appointments.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'How to Create a New Booking', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'When a client calls or messages you, follow these steps:', 'glowbook' ); ?></p>

            <div class="sodek-gb-step-guide">
                <ol>
                    <li><?php esc_html_e( 'Click "New Booking" from the menu on the left (or use the quick link above)', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Pick the service they want from the dropdown list', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Choose the date and time', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Type in the client\'s name, phone number, and email', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click the blue "Publish" button to save', 'glowbook' ); ?></li>
                </ol>
            </div>

            <h3><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Understanding Booking Status', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Each booking has a colored label showing its status:', 'glowbook' ); ?></p>

            <table class="sodek-gb-simple-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'glowbook' ); ?></th>
                        <th><?php esc_html_e( 'What It Means', 'glowbook' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="sodek-gb-status-example status-pending"><?php esc_html_e( 'Pending', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'A new booking request. The client is waiting for you to confirm.', 'glowbook' ); ?></td>
                    </tr>
                    <tr>
                        <td><span class="sodek-gb-status-example status-confirmed"><?php esc_html_e( 'Confirmed', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'You\'ve approved this appointment. The client knows it\'s happening.', 'glowbook' ); ?></td>
                    </tr>
                    <tr>
                        <td><span class="sodek-gb-status-example status-completed"><?php esc_html_e( 'Completed', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'The appointment is finished. The service was done.', 'glowbook' ); ?></td>
                    </tr>
                    <tr>
                        <td><span class="sodek-gb-status-example status-cancelled"><?php esc_html_e( 'Cancelled', 'glowbook' ); ?></span></td>
                        <td><?php esc_html_e( 'The appointment was cancelled by you or the client.', 'glowbook' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <h3><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'How to Confirm a Booking', 'glowbook' ); ?></h3>
            <div class="sodek-gb-step-guide">
                <ol>
                    <li><?php esc_html_e( 'Find the booking in your list (it will show "Pending")', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click on it to open the details', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Look for the Status dropdown on the right side', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Change it from "Pending" to "Confirmed"', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click "Update" to save', 'glowbook' ); ?></li>
                </ol>
            </div>

            <h3><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'How to Reschedule an Appointment', 'glowbook' ); ?></h3>
            <div class="sodek-gb-step-guide">
                <ol>
                    <li><?php esc_html_e( 'Click on the booking you need to change', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click the "Reschedule" button', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Pick the new date and time', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click "Confirm Reschedule"', 'glowbook' ); ?></li>
                </ol>
            </div>

            <div class="sodek-gb-help-tip">
                <strong><?php esc_html_e( 'Tip: Check Client Notes', 'glowbook' ); ?></strong>
                <?php esc_html_e( 'Always look at the "Customer Notes" section before each appointment. Clients often leave important info there, like hair references or special requests.', 'glowbook' ); ?>
            </div>
        </div>

        <!-- Payments -->
        <div class="sodek-gb-help-section" id="payments">
            <h2><?php esc_html_e( 'Recording Payments', 'glowbook' ); ?></h2>
            <p class="section-intro"><?php esc_html_e( 'Keep track of deposits and payments from your clients.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'How to Record a Payment', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'When a client pays you (cash, Cash App, Zelle, etc.), record it like this:', 'glowbook' ); ?></p>

            <div class="sodek-gb-step-guide">
                <ol>
                    <li><?php esc_html_e( 'Open the booking', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Find the "Payment Details" box on the right side', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Type in how much they paid', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Or click the "Full $XX" button to fill in the remaining balance', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Choose how they paid (Cash, Cash App, Zelle, etc.)', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click "Record Payment"', 'glowbook' ); ?></li>
                </ol>
            </div>

            <h3><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Understanding the Payment Box', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'The Payment Details box shows you:', 'glowbook' ); ?></p>
            <ul>
                <li><strong><?php esc_html_e( 'Total', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'The full price of the service', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Paid', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'How much the client has paid so far', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Due', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'How much they still owe', 'glowbook' ); ?></li>
            </ul>

            <h3><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Viewing All Payments', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'To see all your payments in one place, go to GlowBook > Transactions in the menu. You\'ll see:', 'glowbook' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Every payment you\'ve recorded', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'Which booking it was for', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'How the client paid', 'glowbook' ); ?></li>
                <li><?php esc_html_e( 'The date and amount', 'glowbook' ); ?></li>
            </ul>

            <div class="sodek-gb-help-warning">
                <strong><?php esc_html_e( 'Important: Online Payments', 'glowbook' ); ?></strong>
                <?php esc_html_e( 'When clients pay online (through the booking form), those payments are recorded automatically. You don\'t need to add them manually.', 'glowbook' ); ?>
            </div>
        </div>

        <!-- Calendar -->
        <div class="sodek-gb-help-section" id="calendar">
            <h2><?php esc_html_e( 'Using the Calendar', 'glowbook' ); ?></h2>
            <p class="section-intro"><?php esc_html_e( 'See your schedule at a glance and block off time when you\'re unavailable.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Viewing Your Schedule', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'The Calendar shows all your bookings. You can:', 'glowbook' ); ?></p>
            <ul>
                <li><strong><?php esc_html_e( 'Switch views', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'Choose Month, Week, or Day view at the top', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'Click any booking', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'Opens it so you can see details or make changes', 'glowbook' ); ?></li>
                <li><strong><?php esc_html_e( 'See colors', 'glowbook' ); ?></strong> &mdash; <?php esc_html_e( 'Different colors show different statuses (pending, confirmed, etc.)', 'glowbook' ); ?></li>
            </ul>

            <h3><span class="dashicons dashicons-calendar"></span> <?php esc_html_e( 'How to Block Off Days', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Going on vacation? Need a day off? Block it so clients can\'t book:', 'glowbook' ); ?></p>

            <div class="sodek-gb-step-guide">
                <ol>
                    <li><?php esc_html_e( 'Go to GlowBook > Availability in the menu', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Find "Blocked Dates"', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click "Add Blocked Date"', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Pick the date (or date range)', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Add a note like "Vacation" if you want', 'glowbook' ); ?></li>
                    <li><?php esc_html_e( 'Click Save', 'glowbook' ); ?></li>
                </ol>
            </div>

            <div class="sodek-gb-help-tip">
                <strong><?php esc_html_e( 'Tip: Set Your Working Hours', 'glowbook' ); ?></strong>
                <?php esc_html_e( 'In the Availability page, you can also set what hours you work each day. This stops clients from booking at 3am or during your lunch break!', 'glowbook' ); ?>
            </div>
        </div>

        <!-- FAQ -->
        <div class="sodek-gb-help-section" id="faq">
            <h2><?php esc_html_e( 'Common Questions', 'glowbook' ); ?></h2>
            <p class="section-intro"><?php esc_html_e( 'Quick answers to things people ask most often.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'A client wants to cancel. What do I do?', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Open the booking, change the Status to "Cancelled", and click Update. If they paid a deposit, you\'ll need to handle the refund based on your cancellation policy.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'How do I mark an appointment as done?', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'After you finish with a client, open their booking, change the Status to "Completed", and click Update. Don\'t forget to record any final payment they made!', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Can I add notes about a client?', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Yes! On any booking, look for the "Admin Notes" section. These notes are private - only you and other staff can see them, not the client.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'What if the price changed during the appointment?', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'You can edit the booking and change the price. Just update the amount in the Booking Details section, then save. The Payment Details will update to show the new balance.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'How do I change my business hours?', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Go to GlowBook > Availability in the menu. You can set your working hours for each day of the week.', 'glowbook' ); ?></p>

            <h3><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Where do I see how much money I\'ve made?', 'glowbook' ); ?></h3>
            <p><?php esc_html_e( 'Go to GlowBook > Reports in the menu. You\'ll see charts and totals for your bookings and payments.', 'glowbook' ); ?></p>

            <div class="sodek-gb-help-tip">
                <strong><?php esc_html_e( 'Need More Help?', 'glowbook' ); ?></strong>
                <?php esc_html_e( 'Contact your website administrator or visit the support page for assistance.', 'glowbook' ); ?>
            </div>

            <div class="sodek-gb-version-info">
                <?php
                printf(
                    /* translators: %s: plugin version */
                    esc_html__( 'GlowBook Version %s', 'glowbook' ),
                    esc_html( SODEK_GB_VERSION )
                );
                ?>
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
