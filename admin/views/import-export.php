<?php
/**
 * Import/export admin view.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sodek-gb-admin-wrap sodek-gb-import-export-page">
    <div class="sodek-gb-admin-hero">
        <div>
            <span class="sodek-gb-admin-kicker"><?php esc_html_e( 'Environment Transfer', 'glowbook' ); ?></span>
            <h1><?php esc_html_e( 'GlowBook Import / Export', 'glowbook' ); ?></h1>
            <p><?php esc_html_e( 'Move your catalog, customer data, staffing, availability, and bookings between environments with relationship-aware JSON packages.', 'glowbook' ); ?></p>
        </div>
        <div class="sodek-gb-admin-hero-note">
            <strong><?php esc_html_e( 'Merge-safe imports', 'glowbook' ); ?></strong>
            <span><?php esc_html_e( 'Existing records are updated when they can be matched. Missing records are created. Existing records are never deleted automatically.', 'glowbook' ); ?></span>
        </div>
    </div>

    <div class="sodek-gb-import-export-grid">
        <section class="sodek-gb-admin-surface">
            <div class="sodek-gb-admin-surface-header">
                <div>
                    <h2><?php esc_html_e( 'Export Data', 'glowbook' ); ?></h2>
                    <p><?php esc_html_e( 'Download a portable JSON file for one dataset or a full environment backup.', 'glowbook' ); ?></p>
                </div>
                <span class="sodek-gb-admin-pill"><?php esc_html_e( 'Relationship metadata included', 'glowbook' ); ?></span>
            </div>

            <div class="sodek-gb-admin-dataset-list">
                <?php foreach ( $datasets as $key => $dataset ) : ?>
                    <div class="sodek-gb-admin-dataset-card">
                        <div>
                            <strong><?php echo esc_html( $dataset['label'] ); ?></strong>
                            <p><?php echo esc_html( $dataset['description'] ); ?></p>
                        </div>
                        <a
                            class="button button-secondary"
                            href="<?php echo esc_url(
                                wp_nonce_url(
                                    add_query_arg(
                                        array(
                                            'page'                 => 'sodek-gb-import-export',
                                            'dataset'              => $key,
                                            'sodek_gb_export_json' => '1',
                                        ),
                                        admin_url( 'admin.php' )
                                    ),
                                    'sodek_gb_export_json'
                                )
                            ); ?>"
                        >
                            <?php esc_html_e( 'Export', 'glowbook' ); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="sodek-gb-admin-surface">
            <div class="sodek-gb-admin-surface-header">
                <div>
                    <h2><?php esc_html_e( 'Import Data', 'glowbook' ); ?></h2>
                    <p><?php esc_html_e( 'Upload a GlowBook package to update an existing environment or restore a test copy.', 'glowbook' ); ?></p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="sodek-gb-import-form">
                <?php wp_nonce_field( 'sodek_gb_import_json' ); ?>
                <input type="hidden" name="page" value="sodek-gb-import-export">
                <input type="hidden" name="sodek_gb_import_json" value="1">

                <div class="sodek-gb-import-field">
                    <label for="sodek-gb-import-dataset"><?php esc_html_e( 'Dataset', 'glowbook' ); ?></label>
                    <select id="sodek-gb-import-dataset" name="dataset" required>
                        <option value=""><?php esc_html_e( 'Choose a dataset', 'glowbook' ); ?></option>
                        <?php foreach ( $datasets as $key => $dataset ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $dataset['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p><?php esc_html_e( 'For complete migrations, use Full Backup so categories, services, add-ons, staff, customers, and bookings can reconnect in one pass.', 'glowbook' ); ?></p>
                </div>

                <div class="sodek-gb-import-field">
                    <label for="sodek-gb-import-file"><?php esc_html_e( 'JSON File', 'glowbook' ); ?></label>
                    <input id="sodek-gb-import-file" type="file" name="import_file" accept=".json,application/json" required>
                    <p><?php esc_html_e( 'Only GlowBook JSON export files are supported.', 'glowbook' ); ?></p>
                </div>

                <div class="sodek-gb-import-callout">
                    <strong><?php esc_html_e( 'What is protected during import?', 'glowbook' ); ?></strong>
                    <span><?php esc_html_e( 'Saved cards are intentionally excluded. Import source keys, legacy IDs, slugs, and emails are used together to reconnect records safely where possible.', 'glowbook' ); ?></span>
                </div>

                <p class="submit" style="margin-bottom:0;">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Run Import', 'glowbook' ); ?></button>
                </p>
            </form>
        </section>
    </div>

    <section class="sodek-gb-admin-surface sodek-gb-import-export-notes">
        <div class="sodek-gb-admin-surface-header">
            <div>
                <h2><?php esc_html_e( 'What Gets Moved', 'glowbook' ); ?></h2>
                <p><?php esc_html_e( 'Use this as the final checklist when you are validating a migration or staging refresh.', 'glowbook' ); ?></p>
            </div>
        </div>

        <div class="sodek-gb-import-export-checklist">
            <div>
                <strong><?php esc_html_e( 'Services and categories', 'glowbook' ); ?></strong>
                <p><?php esc_html_e( 'Pricing, durations, images, category assignments, and frontend display settings are exported together.', 'glowbook' ); ?></p>
            </div>
            <div>
                <strong><?php esc_html_e( 'Add-ons and service links', 'glowbook' ); ?></strong>
                <p><?php esc_html_e( 'Add-ons keep their service assignments so imported bookings and payment totals stay accurate.', 'glowbook' ); ?></p>
            </div>
            <div>
                <strong><?php esc_html_e( 'Customers and staff', 'glowbook' ); ?></strong>
                <p><?php esc_html_e( 'Portal preferences, profile meta, preferred staff links, and staff service capabilities move with the package.', 'glowbook' ); ?></p>
            </div>
            <div>
                <strong><?php esc_html_e( 'Bookings and payment state', 'glowbook' ); ?></strong>
                <p><?php esc_html_e( 'Bookings include customer, staff, service, add-on, and payment references, plus balance state and import identity keys.', 'glowbook' ); ?></p>
            </div>
        </div>
    </section>
</div>

<style>
.sodek-gb-import-export-page .sodek-gb-admin-hero,
.sodek-gb-import-export-page .sodek-gb-admin-surface {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 22px;
    box-shadow: 0 18px 36px rgba(16, 24, 40, 0.05);
}
.sodek-gb-admin-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 0.42fr);
    gap: 20px;
    padding: 28px 30px;
    margin: 18px 0;
    background: linear-gradient(135deg, #fffaf5 0%, #f7efe6 100%);
    border-color: #eadfce;
}
.sodek-gb-admin-kicker {
    display: inline-flex;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #8a5a21;
}
.sodek-gb-admin-hero h1 {
    margin: 0 0 8px;
    font-size: 34px;
    line-height: 1.08;
}
.sodek-gb-admin-hero p {
    margin: 0;
    color: #667085;
    line-height: 1.7;
}
.sodek-gb-admin-hero-note {
    display: grid;
    gap: 8px;
    align-self: end;
    padding: 18px 20px;
    background: rgba(255,255,255,0.84);
    border: 1px solid rgba(182, 120, 49, 0.16);
    border-radius: 18px;
}
.sodek-gb-import-export-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}
.sodek-gb-admin-surface {
    padding: 22px;
}
.sodek-gb-admin-surface-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 18px;
}
.sodek-gb-admin-surface-header h2 {
    margin: 0;
}
.sodek-gb-admin-surface-header p {
    margin: 6px 0 0;
    color: #667085;
}
.sodek-gb-admin-pill {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 12px;
    border-radius: 999px;
    background: #f6f2eb;
    border: 1px solid #eadfce;
    color: #8a5a21;
    font-weight: 600;
}
.sodek-gb-admin-dataset-list {
    display: grid;
    gap: 12px;
}
.sodek-gb-admin-dataset-card {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 18px;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #fff;
}
.sodek-gb-admin-dataset-card strong {
    display: block;
    margin-bottom: 6px;
    font-size: 15px;
}
.sodek-gb-admin-dataset-card p {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}
.sodek-gb-import-form {
    display: grid;
    gap: 18px;
}
.sodek-gb-import-field {
    display: grid;
    gap: 8px;
}
.sodek-gb-import-field label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #8a5a21;
}
.sodek-gb-import-field p {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}
.sodek-gb-import-field select,
.sodek-gb-import-field input[type="file"] {
    max-width: 100%;
}
.sodek-gb-import-callout {
    display: grid;
    gap: 8px;
    padding: 16px 18px;
    border-radius: 16px;
    background: #faf7f2;
    border: 1px solid #eadfce;
}
.sodek-gb-import-callout span {
    color: #667085;
    line-height: 1.6;
}
.sodek-gb-import-export-notes {
    margin-top: 18px;
}
.sodek-gb-import-export-checklist {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.sodek-gb-import-export-checklist > div {
    padding: 16px 18px;
    border-radius: 16px;
    background: #fcfbf9;
    border: 1px solid #ece6df;
}
.sodek-gb-import-export-checklist strong {
    display: block;
    margin-bottom: 8px;
}
.sodek-gb-import-export-checklist p {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}
@media screen and (max-width: 1100px) {
    .sodek-gb-admin-hero,
    .sodek-gb-import-export-grid,
    .sodek-gb-import-export-checklist {
        grid-template-columns: 1fr;
    }
}
</style>
