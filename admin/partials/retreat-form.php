<?php
/**
 * Retreat form template
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = $edit_mode ? __( 'Edit Retreat', 'dfx-parish-retreat-letters' ) : __( 'Add New Retreat', 'dfx-parish-retreat-letters' );
$submit_text = $edit_mode ? __( 'Update Retreat', 'dfx-parish-retreat-letters' ) : __( 'Add Retreat', 'dfx-parish-retreat-letters' );
?>

<div class="wrap">
    <h1><?php echo esc_html( $page_title ); ?></h1>
    
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>" class="button">
        <?php esc_html_e( '← Back to Retreats', 'dfx-parish-retreat-letters' ); ?>
    </a>

    <hr class="wp-header-end">

    <form method="post" action="" class="dfx-prl-retreat-form">
        <?php wp_nonce_field( 'dfx_prl_retreat_form', 'dfx_prl_nonce' ); ?>
        
        <?php if ( $edit_mode ) : ?>
            <input type="hidden" name="retreat_id" value="<?php echo esc_attr( $retreat->id ); ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="retreat-name">
                            <?php esc_html_e( 'Retreat Name', 'dfx-parish-retreat-letters' ); ?>
                            <span class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="retreat-name" 
                            name="name" 
                            value="<?php echo esc_attr( $retreat->name ); ?>" 
                            class="regular-text" 
                            required
                            placeholder="<?php esc_attr_e( 'Enter retreat name', 'dfx-parish-retreat-letters' ); ?>"
                        >
                        <p class="description">
                            <?php esc_html_e( 'Enter the name or title of the retreat.', 'dfx-parish-retreat-letters' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="retreat-location">
                            <?php esc_html_e( 'Location', 'dfx-parish-retreat-letters' ); ?>
                            <span class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="retreat-location" 
                            name="location" 
                            value="<?php echo esc_attr( $retreat->location ); ?>" 
                            class="regular-text" 
                            required
                            placeholder="<?php esc_attr_e( 'Enter retreat location', 'dfx-parish-retreat-letters' ); ?>"
                        >
                        <p class="description">
                            <?php esc_html_e( 'Enter the location where the retreat will take place.', 'dfx-parish-retreat-letters' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="retreat-start-date">
                            <?php esc_html_e( 'Start Date', 'dfx-parish-retreat-letters' ); ?>
                            <span class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="date" 
                            id="retreat-start-date" 
                            name="start_date" 
                            value="<?php echo esc_attr( $retreat->start_date ); ?>" 
                            required
                        >
                        <p class="description">
                            <?php esc_html_e( 'Select the date when the retreat begins.', 'dfx-parish-retreat-letters' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="retreat-end-date">
                            <?php esc_html_e( 'End Date', 'dfx-parish-retreat-letters' ); ?>
                            <span class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="date" 
                            id="retreat-end-date" 
                            name="end_date" 
                            value="<?php echo esc_attr( $retreat->end_date ); ?>" 
                            required
                        >
                        <p class="description">
                            <?php esc_html_e( 'Select the date when the retreat ends.', 'dfx-parish-retreat-letters' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="dfx-prl-form-actions">
            <p class="submit">
                <input 
                    type="submit" 
                    name="dfx_prl_submit_retreat" 
                    id="submit" 
                    class="button button-primary" 
                    value="<?php echo esc_attr( $submit_text ); ?>"
                >
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>" class="button">
                    <?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
                </a>
            </p>
        </div>
    </form>

    <?php if ( $edit_mode && $retreat->id ) : ?>
        <div class="dfx-prl-retreat-info">
            <h3><?php esc_html_e( 'Retreat Information', 'dfx-parish-retreat-letters' ); ?></h3>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e( 'Created:', 'dfx-parish-retreat-letters' ); ?></strong></td>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $retreat->created_at ) ) ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Last Updated:', 'dfx-parish-retreat-letters' ); ?></strong></td>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $retreat->updated_at ) ) ); ?></td>
                    </tr>
                    <?php if ( ! empty( $retreat->start_date ) && ! empty( $retreat->end_date ) ) : ?>
                        <tr>
                            <td><strong><?php esc_html_e( 'Duration:', 'dfx-parish-retreat-letters' ); ?></strong></td>
                            <td>
                                <?php 
                                printf(
                                    /* translators: %d: number of days */
                                    esc_html( _n( '%d day', '%d days', $retreat->get_duration_days(), 'dfx-parish-retreat-letters' ) ),
                                    $retreat->get_duration_days()
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.required {
    color: #d63638;
}

.dfx-prl-form-actions {
    margin-top: 20px;
}

.dfx-prl-retreat-info {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.dfx-prl-retreat-info table {
    margin-top: 10px;
}

.dfx-prl-retreat-info table td {
    padding: 8px 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Date validation
    $('#retreat-start-date, #retreat-end-date').on('change', function() {
        var startDate = $('#retreat-start-date').val();
        var endDate = $('#retreat-end-date').val();
        
        if (startDate && endDate && startDate > endDate) {
            alert('<?php echo esc_js( __( 'Start date cannot be after end date.', 'dfx-parish-retreat-letters' ) ); ?>');
            $(this).focus();
        }
    });
});
</script>