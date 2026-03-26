document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const navCenter = document.getElementById('navCenter');
    const menuClose = document.getElementById('menuClose');
    const menuBackdrop = document.getElementById('menuBackdrop');
    const menuItems = document.querySelectorAll('.menu-item');

    // Open menu when hamburger is clicked
    hamburgerBtn.addEventListener('click', function() {
        navCenter.classList.add('active');
        menuBackdrop.classList.add('active');
        hamburgerBtn.classList.add('active');
    });

    // Close menu when close button is clicked
    menuClose.addEventListener('click', function() {
        closeMenu();
    });

    // Close menu when backdrop is clicked
    menuBackdrop.addEventListener('click', function() {
        closeMenu();
    });

    // Close menu when a menu item is clicked
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Remove active class from all items
            menuItems.forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
            
            // Close menu after selection
            closeMenu();
        });
    });

    // Function to close menu
    function closeMenu() {
        navCenter.classList.remove('active');
        menuBackdrop.classList.remove('active');
        hamburgerBtn.classList.remove('active');
    }

    // Close menu when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (navCenter.classList.contains('active')) {
                const clickedInsideNav = navCenter.contains(event.target);
                const clickedBtn = hamburgerBtn.contains(event.target);

                if (!clickedInsideNav && !clickedBtn) {
                    closeMenu();
                }
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
});