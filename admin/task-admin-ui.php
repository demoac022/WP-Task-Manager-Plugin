<?php
/**
 * Handle admin UI customizations for the Task Manager
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Add custom columns to the task list
 */
function wp_task_manager_set_custom_columns($columns) {
    $new_columns = array();
    
    // Insert title and description first
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = __('Task', 'wp-task-manager');
    $new_columns['description'] = __('Description', 'wp-task-manager');
    
    // Add our custom columns
    $new_columns['due_date'] = __('Due Date', 'wp-task-manager');
    $new_columns['priority'] = __('Priority', 'wp-task-manager');
    $new_columns['status'] = __('Status', 'wp-task-manager');
    
    // Add date at the end
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}
add_filter('manage_task_posts_columns', 'wp_task_manager_set_custom_columns');

/**
 * Add content to custom columns
 */
function wp_task_manager_custom_column_content($column, $post_id) {
    switch ($column) {
        case 'description':
            $content = get_the_excerpt($post_id);
            echo wp_trim_words($content, 20);
            break;
            
        case 'due_date':
            $due_date = get_post_meta($post_id, '_task_due_date', true);
            echo $due_date ? date_i18n(get_option('date_format'), strtotime($due_date)) : '—';
            break;
            
        case 'priority':
            $priority = get_post_meta($post_id, '_task_priority', true);
            $priority_class = 'priority-' . $priority;
            echo '<span class="' . esc_attr($priority_class) . '">' . esc_html(ucfirst($priority)) . '</span>';
            break;
            
        case 'status':
            $status = get_post_meta($post_id, '_task_status', true);
            $status_class = 'status-' . $status;
            echo '<span class="' . esc_attr($status_class) . '">' . esc_html(ucfirst($status)) . '</span>';
            break;
    }
}
add_action('manage_task_posts_custom_column', 'wp_task_manager_custom_column_content', 10, 2);

/**
 * Make columns sortable
 */
function wp_task_manager_sortable_columns($columns) {
    $columns['due_date'] = 'due_date';
    $columns['priority'] = 'priority';
    $columns['status'] = 'status';
    return $columns;
}
add_filter('manage_edit-task_sortable_columns', 'wp_task_manager_sortable_columns');

/**
 * Add custom sorting
 */
function wp_task_manager_sort_columns($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'task') {
        return;
    }

    $orderby = $query->get('orderby');

    switch ($orderby) {
        case 'due_date':
            $query->set('meta_key', '_task_due_date');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'priority':
            $query->set('meta_key', '_task_priority');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'status':
            $query->set('meta_key', '_task_status');
            $query->set('orderby', 'meta_value');
            break;
    }
}
add_action('pre_get_posts', 'wp_task_manager_sort_columns');

/**
 * Add custom filter dropdowns
 */
function wp_task_manager_add_admin_filters() {
    global $typenow;
    
    if ($typenow !== 'task') {
        return;
    }

    // Priority filter
    $priority = isset($_GET['task_priority']) ? $_GET['task_priority'] : '';
    ?>
    <select name="task_priority">
        <option value=""><?php _e('All Priorities', 'wp-task-manager'); ?></option>
        <option value="low" <?php selected($priority, 'low'); ?>><?php _e('Low', 'wp-task-manager'); ?></option>
        <option value="medium" <?php selected($priority, 'medium'); ?>><?php _e('Medium', 'wp-task-manager'); ?></option>
        <option value="high" <?php selected($priority, 'high'); ?>><?php _e('High', 'wp-task-manager'); ?></option>
    </select>

    <?php
    // Status filter
    $status = isset($_GET['task_status']) ? $_GET['task_status'] : '';
    ?>
    <select name="task_status">
        <option value=""><?php _e('All Statuses', 'wp-task-manager'); ?></option>
        <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'wp-task-manager'); ?></option>
        <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Completed', 'wp-task-manager'); ?></option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'wp_task_manager_add_admin_filters');

/**
 * Apply custom filters to query
 */
function wp_task_manager_filter_tasks($query) {
    global $pagenow;
    
    if (!is_admin() || !$query->is_main_query() || $pagenow !== 'edit.php' || $query->get('post_type') !== 'task') {
        return;
    }

    $meta_query = array();

    // Priority filter
    if (!empty($_GET['task_priority'])) {
        $meta_query[] = array(
            'key' => '_task_priority',
            'value' => sanitize_text_field($_GET['task_priority']),
            'compare' => '='
        );
    }

    // Status filter
    if (!empty($_GET['task_status'])) {
        $meta_query[] = array(
            'key' => '_task_status',
            'value' => sanitize_text_field($_GET['task_status']),
            'compare' => '='
        );
    }

    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'wp_task_manager_filter_tasks');
