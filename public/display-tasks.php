<?php
/**
 * Handle the frontend display of tasks
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Register shortcode for displaying tasks
 */
function wp_task_manager_shortcode($atts) {
    // Normalize attribute keys to lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    
    // Override default attributes with user attributes
    $atts = shortcode_atts(array(
        'status' => '',      // pending, completed, or empty for all
        'priority' => '',    // low, medium, high, or empty for all
        'limit' => -1,       // -1 for all tasks
        'orderby' => 'date', // date, priority, status, due_date
        'order' => 'DESC'    // ASC or DESC
    ), $atts);

    // Start building query args
    $args = array(
        'post_type' => 'task',
        'posts_per_page' => $atts['limit'],
        'orderby' => 'date',
        'order' => $atts['order']
    );

    // Meta query for filtering
    $meta_query = array();

    // Add status filter if specified
    if (!empty($atts['status'])) {
        $meta_query[] = array(
            'key' => '_task_status',
            'value' => sanitize_text_field($atts['status'])
        );
    }

    // Add priority filter if specified
    if (!empty($atts['priority'])) {
        $meta_query[] = array(
            'key' => '_task_priority',
            'value' => sanitize_text_field($atts['priority'])
        );
    }

    // Add meta query if we have conditions
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    // Handle custom ordering
    if ($atts['orderby'] === 'priority' || $atts['orderby'] === 'status' || $atts['orderby'] === 'due_date') {
        $args['meta_key'] = '_task_' . $atts['orderby'];
        $args['orderby'] = 'meta_value';
    }

    // Get tasks
    $tasks = new WP_Query($args);

    // Start output buffering
    ob_start();

    if ($tasks->have_posts()) {
        ?>
        <div class="wp-task-manager-list">
            <div class="task-filters">
                <select class="task-status-filter">
                    <option value=""><?php _e('All Statuses', 'wp-task-manager'); ?></option>
                    <option value="pending"><?php _e('Pending', 'wp-task-manager'); ?></option>
                    <option value="completed"><?php _e('Completed', 'wp-task-manager'); ?></option>
                </select>
                <select class="task-priority-filter">
                    <option value=""><?php _e('All Priorities', 'wp-task-manager'); ?></option>
                    <option value="low"><?php _e('Low', 'wp-task-manager'); ?></option>
                    <option value="medium"><?php _e('Medium', 'wp-task-manager'); ?></option>
                    <option value="high"><?php _e('High', 'wp-task-manager'); ?></option>
                </select>
            </div>
            
            <div class="tasks-container">
                <?php while ($tasks->have_posts()) : $tasks->the_post(); 
                    $task_id = get_the_ID();
                    $priority = get_post_meta($task_id, '_task_priority', true);
                    $status = get_post_meta($task_id, '_task_status', true);
                    $due_date = get_post_meta($task_id, '_task_due_date', true);
                ?>
                <div class="task-item priority-<?php echo esc_attr($priority); ?> status-<?php echo esc_attr($status); ?>">
                    <h3 class="task-title"><?php the_title(); ?></h3>
                    <div class="task-meta">
                        <span class="task-priority"><?php echo esc_html(ucfirst($priority)); ?></span>
                        <span class="task-status"><?php echo esc_html(ucfirst($status)); ?></span>
                        <?php if ($due_date) : ?>
                            <span class="task-due-date">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($due_date))); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="task-description">
                        <?php the_excerpt(); ?>
                    </div>
                    <?php if (is_user_logged_in()) : ?>
                        <button class="task-toggle-status" data-task-id="<?php echo esc_attr($task_id); ?>" data-nonce="<?php echo wp_create_nonce('wp_task_manager_toggle_status'); ?>">
                            <?php echo $status === 'completed' ? __('Mark as Pending', 'wp-task-manager') : __('Mark as Completed', 'wp-task-manager'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
    } else {
        echo '<p class="no-tasks">' . __('No tasks found.', 'wp-task-manager') . '</p>';
    }

    // Restore original post data
    wp_reset_postdata();

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('task_list', 'wp_task_manager_shortcode');

/**
 * Handle AJAX status toggle
 */
function wp_task_manager_toggle_status() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_task_manager_toggle_status')) {
        wp_send_json_error('Invalid nonce');
    }

    // Check if task ID is set
    if (!isset($_POST['task_id'])) {
        wp_send_json_error('No task ID provided');
    }

    $task_id = intval($_POST['task_id']);

    // Check if user can edit this task
    if (!current_user_can('edit_post', $task_id)) {
        wp_send_json_error('Permission denied');
    }

    // Get current status
    $current_status = get_post_meta($task_id, '_task_status', true);
    
    // Toggle status
    $new_status = ($current_status === 'completed') ? 'pending' : 'completed';
    
    // Update status
    $updated = update_post_meta($task_id, '_task_status', $new_status);

    if ($updated) {
        wp_send_json_success(array(
            'status' => $new_status,
            'message' => sprintf(
                __('Task marked as %s', 'wp-task-manager'),
                $new_status
            )
        ));
    } else {
        wp_send_json_error('Failed to update task status');
    }
}
add_action('wp_ajax_wp_task_manager_toggle_status', 'wp_task_manager_toggle_status');
