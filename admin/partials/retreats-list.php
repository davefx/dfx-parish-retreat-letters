<?php
/**
 * Retreats list template
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-add-retreat' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add New', 'dfx-parish-retreat-letters' ); ?>
    </a>

    <hr class="wp-header-end">

    <!-- Search and Filter Form -->
    <div class="dfx-prl-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="dfx-prl-retreats">
            
            <p class="search-box">
                <label class="screen-reader-text" for="retreat-search-input"><?php esc_html_e( 'Search Retreats:', 'dfx-parish-retreat-letters' ); ?></label>
                <input type="search" id="retreat-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search retreats...', 'dfx-parish-retreat-letters' ); ?>">
                
                <label for="start-date-from"><?php esc_html_e( 'Start Date From:', 'dfx-parish-retreat-letters' ); ?></label>
                <input type="date" id="start-date-from" name="start_date_from" value="<?php echo esc_attr( $start_date_from ); ?>">
                
                <label for="start-date-to"><?php esc_html_e( 'Start Date To:', 'dfx-parish-retreat-letters' ); ?></label>
                <input type="date" id="start-date-to" name="start_date_to" value="<?php echo esc_attr( $start_date_to ); ?>">
                
                <input type="submit" name="" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Retreats', 'dfx-parish-retreat-letters' ); ?>">
                
                <?php if ( ! empty( $search ) || ! empty( $start_date_from ) || ! empty( $start_date_to ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>" class="button">
                        <?php esc_html_e( 'Clear Filters', 'dfx-parish-retreat-letters' ); ?>
                    </a>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <?php if ( empty( $retreats ) ) : ?>
        <div class="no-retreats">
            <?php if ( ! empty( $search ) || ! empty( $start_date_from ) || ! empty( $start_date_to ) ) : ?>
                <p><?php esc_html_e( 'No retreats found matching your search criteria.', 'dfx-parish-retreat-letters' ); ?></p>
            <?php else : ?>
                <p><?php esc_html_e( 'No retreats found.', 'dfx-parish-retreat-letters' ); ?> 
                   <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-add-retreat' ) ); ?>">
                       <?php esc_html_e( 'Create your first retreat', 'dfx-parish-retreat-letters' ); ?>
                   </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <!-- Results Summary -->
        <div class="dfx-prl-results-info">
            <p>
                <?php
                printf(
                    /* translators: 1: number of retreats, 2: total number of retreats */
                    esc_html__( 'Showing %1$d of %2$d retreats', 'dfx-parish-retreat-letters' ),
                    count( $retreats ),
                    $total_retreats
                );
                ?>
            </p>
        </div>

        <!-- Retreats Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-name">
                        <?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?>
                    </th>
                    <th scope="col" class="manage-column column-location">
                        <?php esc_html_e( 'Location', 'dfx-parish-retreat-letters' ); ?>
                    </th>
                    <th scope="col" class="manage-column column-start-date">
                        <?php esc_html_e( 'Start Date', 'dfx-parish-retreat-letters' ); ?>
                    </th>
                    <th scope="col" class="manage-column column-end-date">
                        <?php esc_html_e( 'End Date', 'dfx-parish-retreat-letters' ); ?>
                    </th>
                    <th scope="col" class="manage-column column-duration">
                        <?php esc_html_e( 'Duration', 'dfx-parish-retreat-letters' ); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $retreats as $retreat ) : ?>
                    <tr id="retreat-<?php echo esc_attr( $retreat->id ); ?>">
                        <td class="column-name">
                            <strong><?php echo esc_html( $retreat->name ); ?></strong>
                        </td>
                        <td class="column-location">
                            <?php echo esc_html( $retreat->location ); ?>
                        </td>
                        <td class="column-start-date">
                            <?php echo esc_html( $retreat->get_formatted_start_date() ); ?>
                        </td>
                        <td class="column-end-date">
                            <?php echo esc_html( $retreat->get_formatted_end_date() ); ?>
                        </td>
                        <td class="column-duration">
                            <?php 
                            printf(
                                /* translators: %d: number of days */
                                esc_html( _n( '%d day', '%d days', $retreat->get_duration_days(), 'dfx-parish-retreat-letters' ) ),
                                $retreat->get_duration_days()
                            );
                            ?>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-add-retreat&edit=' . $retreat->id ) ); ?>">
                                        <?php esc_html_e( 'Edit', 'dfx-parish-retreat-letters' ); ?>
                                    </a>
                                </span>
                                <span class="trash">
                                    | <a href="#" class="dfx-prl-delete-retreat" data-retreat-id="<?php echo esc_attr( $retreat->id ); ?>">
                                        <?php esc_html_e( 'Delete', 'dfx-parish-retreat-letters' ); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => __( '&laquo;', 'dfx-parish-retreat-letters' ),
                        'next_text' => __( '&raquo;', 'dfx-parish-retreat-letters' ),
                        'total'     => $total_pages,
                        'current'   => $paged,
                    );
                    
                    echo paginate_links( $pagination_args );
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>