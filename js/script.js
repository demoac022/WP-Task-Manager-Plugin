jQuery(document).ready(function($) {
    // Task status toggle
    $('.task-toggle-status').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const taskId = button.data('task-id');
        const nonce = button.data('nonce');
        
        $.ajax({
            url: wpTaskManager.ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_task_manager_toggle_status',
                task_id: taskId,
                nonce: nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    const taskItem = button.closest('.task-item');
                    
                    // Update status class
                    taskItem.removeClass('status-pending status-completed')
                           .addClass('status-' + response.data.status);
                    
                    // Update status text
                    taskItem.find('.task-status').text(
                        response.data.status.charAt(0).toUpperCase() + 
                        response.data.status.slice(1)
                    );
                    
                    // Update button text
                    button.text(
                        response.data.status === 'completed' 
                            ? 'Mark as Pending' 
                            : 'Mark as Completed'
                    );
                    
                    // Show success message
                    const message = $('<div class="task-update-message success">')
                        .text(response.data.message)
                        .insertAfter(button)
                        .fadeOut(3000, function() {
                            $(this).remove();
                        });
                } else {
                    // Show error message
                    const message = $('<div class="task-update-message error">')
                        .text('Error updating task status')
                        .insertAfter(button)
                        .fadeOut(3000, function() {
                            $(this).remove();
                        });
                }
            },
            error: function() {
                // Show error message
                const message = $('<div class="task-update-message error">')
                    .text('Error updating task status')
                    .insertAfter(button)
                    .fadeOut(3000, function() {
                        $(this).remove();
                    });
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Task filters
    $('.task-status-filter, .task-priority-filter').on('change', function() {
        const statusFilter = $('.task-status-filter').val();
        const priorityFilter = $('.task-priority-filter').val();
        
        $('.task-item').each(function() {
            const task = $(this);
            const matchesStatus = !statusFilter || task.hasClass('status-' + statusFilter);
            const matchesPriority = !priorityFilter || task.hasClass('priority-' + priorityFilter);
            
            if (matchesStatus && matchesPriority) {
                task.show();
            } else {
                task.hide();
            }
        });

        // Show/hide "no tasks" message
        const visibleTasks = $('.task-item:visible').length;
        let noTasksMessage = $('.no-filtered-tasks');
        
        if (visibleTasks === 0) {
            if (noTasksMessage.length === 0) {
                noTasksMessage = $('<p class="no-filtered-tasks no-tasks">')
                    .text('No tasks match the selected filters')
                    .insertAfter('.task-filters');
            }
            noTasksMessage.show();
        } else {
            noTasksMessage.hide();
        }
    });
});
