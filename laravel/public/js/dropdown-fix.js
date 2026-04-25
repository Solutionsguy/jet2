/**
 * Single-Click Dropdown Fix
 * This script handles all .custom-toggle buttons to ensure they open on the FIRST click.
 * It bypasses Bootstrap's automatic engine to prevent double-click conflicts.
 */

$(document).ready(function() {
    console.log('🚀 Single-Click Menu Fix initialized');

    // 1. Disable any lingering Bootstrap listeners on these specific buttons
    $('.custom-toggle').attr('data-bs-toggle', 'disabled');

    // 2. Clear all open menus when clicking anywhere else
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });

    // 3. The "Master" Toggle Handler
    $(document).on('click', '.custom-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $parent = $(this).closest('.btn-group');
        const $menu = $parent.find('.dropdown-menu');
        const isCurrentlyOpen = $menu.hasClass('show');

        // Close all other menus first
        $('.dropdown-menu').removeClass('show');

        // Toggle this specific menu
        if (!isCurrentlyOpen) {
            $menu.addClass('show');
            console.log('✅ Menu Opened');
        } else {
            console.log('❌ Menu Closed');
        }
    });
});
