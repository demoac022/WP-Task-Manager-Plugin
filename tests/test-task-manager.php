<?php
/**
 * Class TaskManagerTest
 *
 * @package WP_Task_Manager
 */

class TaskManagerTest extends WP_UnitTestCase {
    
    public function setUp() {
        parent::setUp();
        // Your setup code here
    }

    public function tearDown() {
        parent::tearDown();
        // Your cleanup code here
    }

    /**
     * Test task creation
     */
    public function test_create_task() {
        $task_data = array(
            'post_title'    => 'Test Task',
            'post_content'  => 'This is a test task',
            'post_status'   => 'publish',
            'post_type'     => 'task'
        );

        $task_id = wp_insert_post($task_data);
        
        // Test if task was created
        $this->assertNotEquals(0, $task_id);
        $this->assertEquals('Test Task', get_the_title($task_id));

        // Test meta fields
        update_post_meta($task_id, '_task_priority', 'high');
        update_post_meta($task_id, '_task_status', 'pending');
        
        $this->assertEquals('high', get_post_meta($task_id, '_task_priority', true));
        $this->assertEquals('pending', get_post_meta($task_id, '_task_status', true));
    }

    /**
     * Test task status toggle
     */
    public function test_task_status_toggle() {
        // Create a test task
        $task_id = wp_insert_post(array(
            'post_title'    => 'Toggle Test Task',
            'post_type'     => 'task',
            'post_status'   => 'publish'
        ));

        // Set initial status
        update_post_meta($task_id, '_task_status', 'pending');
        
        // Simulate AJAX request
        $_POST['task_id'] = $task_id;
        $_POST['nonce'] = wp_create_nonce('wp_task_manager_toggle_status');
        
        // Call the toggle function
        do_action('wp_ajax_wp_task_manager_toggle_status');
        
        // Check if status was toggled
        $new_status = get_post_meta($task_id, '_task_status', true);
        $this->assertEquals('completed', $new_status);
    }

    /**
     * Test shortcode output
     */
    public function test_task_list_shortcode() {
        // Create test tasks
        $task1_id = wp_insert_post(array(
            'post_title'    => 'Shortcode Test Task 1',
            'post_type'     => 'task',
            'post_status'   => 'publish'
        ));

        $task2_id = wp_insert_post(array(
            'post_title'    => 'Shortcode Test Task 2',
            'post_type'     => 'task',
            'post_status'   => 'publish'
        ));

        // Add meta data
        update_post_meta($task1_id, '_task_priority', 'high');
        update_post_meta($task1_id, '_task_status', 'pending');
        update_post_meta($task2_id, '_task_priority', 'low');
        update_post_meta($task2_id, '_task_status', 'completed');

        // Get shortcode output
        $output = do_shortcode('[task_list]');
        
        // Basic assertions
        $this->assertStringContainsString('Shortcode Test Task 1', $output);
        $this->assertStringContainsString('Shortcode Test Task 2', $output);
        $this->assertStringContainsString('priority-high', $output);
        $this->assertStringContainsString('status-completed', $output);
    }
}
