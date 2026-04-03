// Manual dropdown toggle fix
$(document).ready(function() {
    console.log('Dropdown fix script loaded');
    
    // Remove existing Bootstrap dropdown initialization
    $('[data-bs-toggle="dropdown"]').off('click');
    
    // Manual dropdown toggle
    $('[data-bs-toggle="dropdown"]').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Menu icon clicked');
        
        var $dropdown = $(this).next('.dropdown-menu');
        var isVisible = $dropdown.hasClass('show');
        
        // Hide all other dropdowns
        $('.dropdown-menu').removeClass('show');
        
        // Toggle this dropdown
        if (!isVisible) {
            $dropdown.addClass('show');
            console.log('Dropdown opened');
        } else {
            $dropdown.removeClass('show');
            console.log('Dropdown closed');
        }
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
    
    console.log('Dropdown handlers attached');
});
