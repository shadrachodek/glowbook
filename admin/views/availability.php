<?php
/**
 * Availability management view.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sodek-gb-admin-wrap">
    <h1><?php esc_html_e( 'GlowBook Availability', 'glowbook' ); ?></h1>

    <?php settings_errors( 'sodek_gb_messages' ); ?>

    <div class="sodek-gb-availability-columns">
        <div class="sodek-gb-availability-column">
            <h2><?php esc_html_e( 'Weekly Schedule', 'glowbook' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Set your regular business hours for each day of the week.', 'glowbook' ); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field( 'sodek_gb_save_schedule', 'sodek_gb_schedule_nonce' ); ?>

                <table class="wp-list-table widefat fixed sodek-gb-schedule-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;"><?php esc_html_e( 'Day', 'glowbook' ); ?></th>
                            <th style="width: 80px;"><?php esc_html_e( 'Open', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Start Time', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'End Time', 'glowbook' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $days as $day_num => $day_name ) :
                            $day_schedule = isset( $schedule[ $day_num ] ) ? $schedule[ $day_num ] : array(
                                'start_time'   => '09:00:00',
                                'end_time'     => '18:00:00',
                                'is_available' => false,
                            );
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $day_name ); ?></strong></td>
                            <td>
                                <input type="checkbox"
                                    name="schedule[<?php echo esc_attr( $day_num ); ?>][is_available]"
                                    value="1"
                                    <?php checked( $day_schedule['is_available'], true ); ?>
                                    class="sodek-gb-day-toggle">
                            </td>
                            <td>
                                <input type="time"
                                    name="schedule[<?php echo esc_attr( $day_num ); ?>][start_time]"
                                    value="<?php echo esc_attr( substr( $day_schedule['start_time'], 0, 5 ) ); ?>"
                                    class="sodek-gb-time-input">
                            </td>
                            <td>
                                <input type="time"
                                    name="schedule[<?php echo esc_attr( $day_num ); ?>][end_time]"
                                    value="<?php echo esc_attr( substr( $day_schedule['end_time'], 0, 5 ) ); ?>"
                                    class="sodek-gb-time-input">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="sodek_gb_save_schedule" class="button button-primary" value="<?php esc_attr_e( 'Save Schedule', 'glowbook' ); ?>">
                </p>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Daily Booking Limits', 'glowbook' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Control the total number of bookings the business accepts each day. Service-specific limits still apply inside these daily limits.', 'glowbook' ); ?></p>

            <form method="post" action="" class="sodek-gb-daily-limits-form">
                <?php wp_nonce_field( 'sodek_gb_save_daily_limits', 'sodek_gb_daily_limits_nonce' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_daily_limit"><?php esc_html_e( 'Default Daily Limit', 'glowbook' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="default_daily_limit" name="default_daily_limit" min="0" step="1" value="<?php echo esc_attr( $default_daily_limit ); ?>" class="small-text">
                            <p class="description"><?php esc_html_e( 'Default is 3. Use 0 for unlimited daily bookings.', 'glowbook' ); ?></p>
                        </td>
                    </tr>
                </table>

                <table class="wp-list-table widefat fixed sodek-gb-schedule-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;"><?php esc_html_e( 'Day', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Daily Limit', 'glowbook' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $days as $day_num => $day_name ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $day_name ); ?></strong></td>
                                <td>
                                    <input type="number"
                                        name="daily_limits[<?php echo esc_attr( $day_num ); ?>]"
                                        min="0"
                                        step="1"
                                        value="<?php echo isset( $weekday_daily_limits[ $day_num ] ) ? esc_attr( $weekday_daily_limits[ $day_num ] ) : ''; ?>"
                                        class="small-text"
                                        placeholder="<?php echo esc_attr( $default_daily_limit ); ?>">
                                    <p class="description"><?php esc_html_e( 'Leave blank to inherit the default. Use 0 for unlimited on this weekday.', 'glowbook' ); ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3><?php esc_html_e( 'Specific Date Overrides', 'glowbook' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Use this for extensions, holidays, or one-off days that need a different limit.', 'glowbook' ); ?></p>

                <table class="wp-list-table widefat fixed striped sodek-gb-date-limit-overrides">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Limit', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Note', 'glowbook' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $override_index = 0;
                        foreach ( $date_limit_overrides as $date => $override ) :
                            ?>
                            <tr>
                                <td data-colname="<?php esc_attr_e( 'Date', 'glowbook' ); ?>"><input type="date" name="date_limit_overrides[<?php echo esc_attr( $override_index ); ?>][date]" value="<?php echo esc_attr( $date ); ?>"></td>
                                <td data-colname="<?php esc_attr_e( 'Limit', 'glowbook' ); ?>"><input type="number" name="date_limit_overrides[<?php echo esc_attr( $override_index ); ?>][limit]" min="0" step="1" value="<?php echo esc_attr( $override['limit'] ?? $default_daily_limit ); ?>" class="small-text"></td>
                                <td data-colname="<?php esc_attr_e( 'Note', 'glowbook' ); ?>"><input type="text" name="date_limit_overrides[<?php echo esc_attr( $override_index ); ?>][note]" value="<?php echo esc_attr( $override['note'] ?? '' ); ?>" class="regular-text"></td>
                            </tr>
                            <?php
                            $override_index++;
                        endforeach;
                        ?>
                        <tr>
                            <td data-colname="<?php esc_attr_e( 'Date', 'glowbook' ); ?>"><input type="date" name="date_limit_overrides[<?php echo esc_attr( $override_index ); ?>][date]"></td>
                            <td data-colname="<?php esc_attr_e( 'Limit', 'glowbook' ); ?>"><input type="number" name="date_limit_overrides[<?php echo esc_attr( $override_index ); ?>][limit]" min="0" step="1" value="" class="small-text" placeholder="<?php echo esc_attr( $default_daily_limit ); ?>"></td>
                            <td data-colname="<?php esc_attr_e( 'Note', 'glowbook' ); ?>"><input type="text" name="date_limit_overrides[<?php echo esc_attr( $override_index ); ?>][note]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Holiday extension', 'glowbook' ); ?>"></td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="sodek_gb_save_daily_limits" class="button button-primary" value="<?php esc_attr_e( 'Save Daily Limits', 'glowbook' ); ?>">
                </p>
            </form>
        </div>

        <div class="sodek-gb-availability-column">
            <h2><?php esc_html_e( 'Block Dates', 'glowbook' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Block specific dates when you are unavailable (holidays, vacations, etc.).', 'glowbook' ); ?></p>

            <form method="post" action="" class="sodek-gb-block-date-form">
                <?php wp_nonce_field( 'sodek_gb_block_date', 'sodek_gb_block_nonce' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="block_date"><?php esc_html_e( 'Date', 'glowbook' ); ?></label></th>
                        <td>
                            <input type="date" id="block_date" name="block_date" required
                                min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="block_reason"><?php esc_html_e( 'Reason', 'glowbook' ); ?></label></th>
                        <td>
                            <input type="text" id="block_reason" name="block_reason" class="regular-text"
                                placeholder="<?php esc_attr_e( 'e.g., Holiday, Vacation', 'glowbook' ); ?>">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="sodek_gb_block_date" class="button button-secondary" value="<?php esc_attr_e( 'Block Date', 'glowbook' ); ?>">
                </p>
            </form>

            <h3><?php esc_html_e( 'Blocked Dates', 'glowbook' ); ?></h3>

            <?php if ( empty( $blocked_dates ) ) : ?>
                <p><?php esc_html_e( 'No dates blocked for this month.', 'glowbook' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Reason', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'glowbook' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $blocked_dates as $date => $reason ) : ?>
                        <tr>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></td>
                            <td><?php echo esc_html( $reason ); ?></td>
                            <td>
                                <button type="button" class="button button-small sodek-gb-remove-block" data-date="<?php echo esc_attr( $date ); ?>">
                                    <?php esc_html_e( 'Remove', 'glowbook' ); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
