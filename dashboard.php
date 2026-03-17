<?php
/**
 * Admin Dashboard (Logs) for GJ AI Takeaway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'gj_ai_takeaway_dashboard_menu', 11 );
function gj_ai_takeaway_dashboard_menu() {
    add_submenu_page(
        'gj-ai-takeaway',
        'Interaction Logs',
        'Interaction Logs',
        'manage_options',
        'gj-ai-takeaway-logs',
        'gj_ai_takeaway_logs_page'
    );
}

function gj_ai_takeaway_logs_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gj_ai_logs';

    // Handle clearing logs
    if ( isset( $_POST['gj_ai_clear_logs'] ) && check_admin_referer( 'gj_ai_clear_logs_nonce' ) ) {
        $wpdb->query( "TRUNCATE TABLE $table_name" );
        echo '<div class="updated"><p>All logs cleared successfully.</p></div>';
    }

    // Handle individual row deletion
    if ( isset( $_GET['delete_log'] ) && check_admin_referer( 'gj_ai_delete_log_' . $_GET['delete_log'] ) ) {
        $wpdb->delete( $table_name, array( 'id' => intval( $_GET['delete_log'] ) ) );
        echo '<div class="updated"><p>Log entry deleted.</p></div>';
    }

    // Handle single day deletion (from filter)
    if ( isset( $_POST['gj_ai_delete_date'] ) && check_admin_referer( 'gj_ai_bulk_logs_nonce' ) ) {
        $target_date = sanitize_text_field( $_POST['filter_date'] );
        if ($target_date) {
            $deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE DATE(time) = %s", $target_date ) );
            echo '<div class="updated"><p>' . intval($deleted) . ' logs from ' . esc_html($target_date) . ' deleted.</p></div>';
        }
    }

    // Handle Manual Prune (Based on settings)
    if ( isset( $_POST['gj_ai_prune_logs'] ) && check_admin_referer( 'gj_ai_bulk_logs_nonce' ) ) {
        gj_ai_perform_log_cleanup();
        echo '<div class="updated"><p>Old logs pruned based on your retention settings.</p></div>';
    }

    // Filtering logic
    $where = " WHERE 1=1 ";
    $filter_date = isset( $_GET['filter_date'] ) ? sanitize_text_field( $_GET['filter_date'] ) : '';
    
    if ( $filter_date ) {
        $where .= $wpdb->prepare( " AND DATE(time) = %s ", $filter_date );
    }

    $logs = $wpdb->get_results( "SELECT * FROM $table_name $where ORDER BY time DESC LIMIT 500" );

    ?>
    <div class="wrap">
        <h1>GJ AI Takeaway - Interaction Logs</h1>
        
        <div class="tablenav top" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
            <div class="alignleft actions">
                <form method="get" action="" style="display: flex; align-items: center; gap: 10px;">
                    <input type="hidden" name="page" value="gj-ai-takeaway-logs">
                    <strong>Filter by Date:</strong>
                    <input type="date" name="filter_date" id="filter_date" value="<?php echo esc_attr($filter_date); ?>">
                    <input type="submit" class="button button-primary" value="Apply Filter">
                    <?php if($filter_date): ?>
                        <a href="admin.php?page=gj-ai-takeaway-logs" class="button">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="alignright actions" style="display: flex; gap: 10px;">
                <?php if($filter_date): ?>
                    <form method="post" action="" onsubmit="return confirm('Delete ALL logs for <?php echo esc_attr($filter_date); ?>?');">
                        <?php wp_nonce_field( 'gj_ai_bulk_logs_nonce' ); ?>
                        <input type="hidden" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">
                        <input type="submit" name="gj_ai_delete_date" class="button" style="color:#d63638; border-color:#d63638;" value="Delete All for This Date">
                    </form>
                <?php endif; ?>

                <form method="post" action="" onsubmit="return confirm('Prune logs older than configured retention days?');">
                    <?php wp_nonce_field( 'gj_ai_bulk_logs_nonce' ); ?>
                    <input type="submit" name="gj_ai_prune_logs" class="button" value="Run Cleanup (Prune)">
                </form>

                <form method="post" action="" onsubmit="return confirm('Are you sure you want to clear ALL logs? This cannot be undone.');">
                    <?php wp_nonce_field( 'gj_ai_clear_logs_nonce' ); ?>
                    <input type="submit" name="gj_ai_clear_logs" class="button button-link-delete" value="Clear Everything" style="color:#d63638;">
                </form>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="140">Time</th>
                    <th width="120">Session ID</th>
                    <th width="100">IP Address</th>
                    <th width="100">User</th>
                    <th width="60">Post</th>
                    <th>Query</th>
                    <th>AI Response</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $logs ) : ?>
                    <?php foreach ( $logs as $log ) : 
                        $user = $log->user_id ? get_userdata( $log->user_id ) : null;
                        $user_display = $user ? $user->display_name : 'Guest';
                        $delete_url = wp_nonce_url( admin_url( 'admin.php?page=gj-ai-takeaway-logs&delete_log=' . $log->id ), 'gj_ai_delete_log_' . $log->id );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $log->time ); ?></td>
                            <td><code style="font-size: 10px;"><?php echo esc_html( $log->chat_session_id ); ?></code></td>
                            <td><?php echo esc_html( $log->ip_address ); ?></td>
                            <td><?php echo esc_html( $user_display ); ?></td>
                            <td><a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank">#<?php echo esc_html( $log->post_id ); ?></a></td>
                            <td><div style="max-height: 80px; overflow-y: auto; font-size: 12px;"><?php echo esc_html( $log->query ); ?></div></td>
                            <td><div style="max-height: 80px; overflow-y: auto; font-size: 12px;"><?php echo esc_html( $log->response ); ?></div></td>
                            <td>
                                <a href="<?php echo $delete_url; ?>" class="button button-small" onclick="return confirm('Delete this log?');" style="color:#d63638;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8">No logs found matching your criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
