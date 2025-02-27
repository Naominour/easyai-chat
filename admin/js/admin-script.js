jQuery(document).ready(function($) {
    // Initialize color picker
    $('.color-picker').wpColorPicker();
    
    // Tab navigation
    $('.easyai-chat-admin-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Hide all tab contents
        $('.tab-content').removeClass('active');
        
        // Show the selected tab content
        $($(this).attr('href')).addClass('active');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
    });
    
    // Update provider information
    $('#llm_provider').on('change', function() {
        var provider = $(this).val();
        $('.provider-info').hide();
        $('.provider-info.' + provider).show();
    }).trigger('change');
    
    // Update temperature value display
    $('input[name="easyai_chat_options[temperature]"]').on('input', function() {
        $('.temperature-value').text($(this).val());
    });

    // Add example question button
    $('.add-example-question').on('click', function() {
        const container = $('.example-questions-container');
        const newItem = $('<div class="example-question-item"></div>');
        
        newItem.html(`
            <input type="text" 
                name="easyai_chat_options[example_questions][]" 
                value="" 
                class="regular-text"
            />
            <button type="button" class="button remove-example-question">Remove</button>
        `);
        
        container.find('.add-example-question').before(newItem);
    });

    // Remove example question button
    $(document).on('click', '.remove-example-question', function() {
        $(this).closest('.example-question-item').remove();
    });
});