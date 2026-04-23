<?php
/**
 * Staff management page.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

$all_staff = Sodek_GB_Staff::get_all_staff();
$active_staff = array_filter( $all_staff, function( $s ) {
    return ! empty( $s['is_active'] );
} );
?>
<div class="wrap sodek-gb-admin-wrap">
    <h1>
        <?php esc_html_e( 'Staff Members', 'glowbook' ); ?>
        <a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Add New User', 'glowbook' ); ?>
        </a>
    </h1>

    <p class="description">
        <?php esc_html_e( 'Manage staff members who can be assigned to bookings. To add a user as staff, edit their profile and enable "Is Staff Member" in the Booking Staff Settings section.', 'glowbook' ); ?>
    </p>

    <?php if ( empty( $all_staff ) ) : ?>
        <div class="notice notice-info">
            <p>
                <?php
                printf(
                    /* translators: %s: Users page URL */
                    esc_html__( 'No staff members configured yet. Go to %s and edit a user to mark them as booking staff.', 'glowbook' ),
                    '<a href="' . esc_url( admin_url( 'users.php' ) ) . '">' . esc_html__( 'Users', 'glowbook' ) . '</a>'
                );
                ?>
            </p>
        </div>
    <?php else : ?>

        <div class="sodek-gb-staff-stats">
            <div class="sodek-gb-stat-box">
                <span class="sodek-gb-stat-number"><?php echo count( $all_staff ); ?></span>
                <span class="sodek-gb-stat-label"><?php esc_html_e( 'Total Staff', 'glowbook' ); ?></span>
            </div>
            <div class="sodek-gb-stat-box">
                <span class="sodek-gb-stat-number"><?php echo count( $active_staff ); ?></span>
                <span class="sodek-gb-stat-label"><?php esc_html_e( 'Active', 'glowbook' ); ?></span>
            </div>
        </div>

        <div class="sodek-gb-staff-grid">
            <?php foreach ( $all_staff as $staff ) : ?>
                <div class="sodek-gb-staff-card <?php echo empty( $staff['is_active'] ) ? 'sodek-gb-staff-inactive' : ''; ?>">
                    <div class="sodek-gb-staff-avatar">
                        <img src="<?php echo esc_url( $staff['avatar'] ); ?>" alt="<?php echo esc_attr( $staff['name'] ); ?>">
                        <span class="sodek-gb-staff-color" style="background-color: <?php echo esc_attr( $staff['color'] ); ?>;"></span>
                    </div>
                    <div class="sodek-gb-staff-info">
                        <h3><?php echo esc_html( $staff['name'] ); ?></h3>
                        <p class="sodek-gb-staff-email"><?php echo esc_html( $staff['email'] ); ?></p>
                        <?php if ( $staff['phone'] ) : ?>
                            <p class="sodek-gb-staff-phone"><?php echo esc_html( $staff['phone'] ); ?></p>
                        <?php endif; ?>
                        <p class="sodek-gb-staff-status">
                            <?php if ( $staff['is_active'] ) : ?>
                                <span class="sodek-gb-status sodek-gb-status-success"><?php esc_html_e( 'Active', 'glowbook' ); ?></span>
                            <?php else : ?>
                                <span class="sodek-gb-status sodek-gb-status-warning"><?php esc_html_e( 'Inactive', 'glowbook' ); ?></span>
                            <?php endif; ?>
                        </p>

                        <?php if ( ! empty( $staff['services'] ) ) : ?>
                            <p class="sodek-gb-staff-services">
                                <strong><?php esc_html_e( 'Services:', 'glowbook' ); ?></strong><br>
                                <?php
                                $service_names = array();
                                foreach ( $staff['services'] as $service_id ) {
                                    $service = Sodek_GB_Service::get_service( $service_id );
                                    if ( $service ) {
                                        $service_names[] = $service['title'];
                                    }
                                }
                                echo esc_html( implode( ', ', $service_names ) );
                                ?>
                            </p>
                        <?php else : ?>
                            <p class="sodek-gb-staff-services">
                                <em><?php esc_html_e( 'All services', 'glowbook' ); ?></em>
                            </p>
                        <?php endif; ?>

                        <div class="sodek-gb-staff-stats-mini">
                            <span title="<?php esc_attr_e( 'Bookings today', 'glowbook' ); ?>">
                                <?php esc_html_e( 'Today:', 'glowbook' ); ?>
                                <?php echo esc_html( Sodek_GB_Staff::get_bookings_count( $staff['id'], 'today' ) ); ?>
                            </span>
                            <span title="<?php esc_attr_e( 'Bookings this week', 'glowbook' ); ?>">
                                <?php esc_html_e( 'Week:', 'glowbook' ); ?>
                                <?php echo esc_html( Sodek_GB_Staff::get_bookings_count( $staff['id'], 'week' ) ); ?>
                            </span>
                            <span title="<?php esc_attr_e( 'Bookings this month', 'glowbook' ); ?>">
                                <?php esc_html_e( 'Month:', 'glowbook' ); ?>
                                <?php echo esc_html( Sodek_GB_Staff::get_bookings_count( $staff['id'], 'month' ) ); ?>
                            </span>
                        </div>
                    </div>
                    <div class="sodek-gb-staff-actions">
                        <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $staff['id'] ) ); ?>" class="button">
                            <?php esc_html_e( 'Edit', 'glowbook' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=sodek-gb-calendar&staff=' . $staff['id'] ) ); ?>" class="button">
                            <?php esc_html_e( 'View Calendar', 'glowbook' ); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<style>
.sodek-gb-staff-stats {
    display: flex;
    gap: 15px;
    margin: 20px 0;
}

.sodek-gb-staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.sodek-gb-staff-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.sodek-gb-staff-card.sodek-gb-staff-inactive {
    opacity: 0.7;
    background: #f9f9f9;
}

.sodek-gb-staff-avatar {
    position: relative;
    flex-shrink: 0;
}

.sodek-gb-staff-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.sodek-gb-staff-color {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.sodek-gb-staff-info {
    flex: 1;
    min-width: 200px;
}

.sodek-gb-staff-info h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.sodek-gb-staff-info p {
    margin: 3px 0;
    color: #646970;
    font-size: 13px;
}

.sodek-gb-staff-services {
    margin-top: 10px !important;
}

.sodek-gb-staff-stats-mini {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    font-size: 12px;
    color: #646970;
}

.sodek-gb-staff-actions {
    width: 100%;
    display: flex;
    gap: 10px;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f1;
}

@media screen and (max-width: 782px) {
    .sodek-gb-staff-grid {
        grid-template-columns: 1fr;
    }
}
</style>
