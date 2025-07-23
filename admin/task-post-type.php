<?php
/**
 * Register the Task custom post type and its meta fields
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Register the custom post type
 */
function wp_task_manager_register_post_type() {
    $labels = array(
        'name'               => _x('Tasks', 'post type general name', 'wp-task-manager'),
        'singular_name'      => _x('Task', 'post type singular name', 'wp-task-manager'),
        'menu_name'          => _x('Tasks', 'admin menu', 'wp-task-manager'),
        'add_new'           => _x('Add New', 'task', 'wp-task-manager'),
        'add_new_item'      => __('Add New Task', 'wp-task-manager'),
        'edit_item'         => __('Edit Task', 'wp-task-manager'),
        'new_item'          => __('New Task', 'wp-task-manager'),
        'view_item'         => __('View Task', 'wp-task-manager'),
        'search_items'      => __('Search Tasks', 'wp-task-manager'),
        'not_found'         => __('No tasks found', 'wp-task-manager'),
        'not_found_in_trash'=> __('No tasks found in Trash', 'wp-task-manager'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'task'),
        'capability_type'   => 'post',
        'has_archive'       => true,
        'hierarchical'      => false,
        'menu_position'     => 5,
        'menu_icon'         => 'dashicons-clipboard',
        'supports'          => array('title', 'editor'),
    );

    register_post_type('task', $args);
}
add_action('init', 'wp_task_manager_register_post_type');

/**
 * Add meta boxes for task fields
 */
function wp_task_manager_add_meta_boxes() {
    add_meta_box(
        'wp_task_manager_meta_box',
        __('Task Details', 'wp-task-manager'),
        'wp_task_manager_meta_box_callback',
        'task',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'wp_task_manager_add_meta_boxes');

/**
 * Meta box callback function
 */
function wp_task_manager_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('wp_task_manager_save_meta_box_data', 'wp_task_manager_meta_box_nonce');

    // Get current values
    $due_date = get_post_meta($post->ID, '_task_due_date', true);
    $priority = get_post_meta($post->ID, '_task_priority', true);
    $status = get_post_meta($post->ID, '_task_status', true);

    // Default values
    if (empty($priority)) $priority = 'medium';
    if (empty($status)) $status = 'pending';
    ?>
    <p>
        <label for="task_due_date"><?php _e('Due Date:', 'wp-task-manager'); ?></label>
        <input type="date" id="task_due_date" name="task_due_date" value="<?php echo esc_attr($due_date); ?>">
    </p>
    <p>
        <label for="task_priority"><?php _e('Priority:', 'wp-task-manager'); ?></label>
        <select id="task_priority" name="task_priority">
            <option value="low" <?php selected($priority, 'low'); ?>><?php _e('Low', 'wp-task-manager'); ?></option>
            <option value="medium" <?php selected($priority, 'medium'); ?>><?php _e('Medium', 'wp-task-manager'); ?></option>
            <option value="high" <?php selected($priority, 'high'); ?>><?php _e('High', 'wp-task-manager'); ?></option>
        </select>
    </p>
    <p>
        <label for="task_status"><?php _e('Status:', 'wp-task-manager'); ?></label>
        <select id="task_status" name="task_status">
            <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'wp-task-manager'); ?></option>
            <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Completed', 'wp-task-manager'); ?></option>
        </select>
    </p>
    <?php
}

/**
 * Save meta box data
 */
function wp_task_manager_save_meta_box_data($post_id) {
    // Check if our nonce is set and verify it
    if (!isset($_POST['wp_task_manager_meta_box_nonce']) ||
        !wp_verify_nonce($_POST['wp_task_manager_meta_box_nonce'], 'wp_task_manager_save_meta_box_data')) {
        return;
    }

    // If this is an autosave, we don't want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Sanitize and save the data
    if (isset($_POST['task_due_date'])) {
        update_post_meta($post_id, '_task_due_date', sanitize_text_field($_POST['task_due_date']));
    }
    
    if (isset($_POST['task_priority'])) {
        update_post_meta($post_id, '_task_priority', sanitize_text_field($_POST['task_priority']));
    }
    
    if (isset($_POST['task_status'])) {
        update_post_meta($post_id, '_task_status', sanitize_text_field($_POST['task_status']));
    }
}
add_action('save_post_task', 'wp_task_manager_save_meta_box_data');
