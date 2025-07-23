<?php
/**
 * Plugin Name: WP Task Manager
 * Description: A WordPress plugin for managing tasks with custom post types, admin interface, and shortcode display.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-task-manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WP_TASK_MANAGER_VERSION', '1.0.0');
define('WP_TASK_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_TASK_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_TASK_MANAGER_PLUGIN_DIR . 'admin/task-post-type.php';
require_once WP_TASK_MANAGER_PLUGIN_DIR . 'admin/task-admin-ui.php';
require_once WP_TASK_MANAGER_PLUGIN_DIR . 'public/display-tasks.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_task_manager_activate');
register_deactivation_hook(__FILE__, 'wp_task_manager_deactivate');

/**
 * Plugin activation function
 */
function wp_task_manager_activate() {
    // Trigger our function that registers the custom post type
    require_once WP_TASK_MANAGER_PLUGIN_DIR . 'admin/task-post-type.php';
    wp_task_manager_register_post_type();
    
    // Clear the permalinks after the post type has been registered
    flush_rewrite_rules();
}

/**
 * Plugin deactivation function
 */
function wp_task_manager_deactivate() {
    // Unregister the post type, so the rules are no longer in memory
    unregister_post_type('task');
    // Clear the permalinks to remove our post type's rules from the database
    flush_rewrite_rules();
}

/**
 * Enqueue scripts and styles
 */
function wp_task_manager_enqueue_scripts() {
    // Enqueue frontend styles
    wp_enqueue_style(
        'wp-task-manager-style',
        WP_TASK_MANAGER_PLUGIN_URL . 'css/style.css',
        array(),
        WP_TASK_MANAGER_VERSION
    );

    // Enqueue frontend scripts
    wp_enqueue_script(
        'wp-task-manager-script',
        WP_TASK_MANAGER_PLUGIN_URL . 'js/script.js',
        array('jquery'),
        WP_TASK_MANAGER_VERSION,
        true
    );

    // Localize the script with new data
    wp_localize_script('wp-task-manager-script', 'wpTaskManager', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_task_manager_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'wp_task_manager_enqueue_scripts');
