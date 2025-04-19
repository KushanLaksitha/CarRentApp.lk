document.addEventListener('DOMContentLoaded', function() {
    // Get references to important elements
    const sidebarToggler = document.querySelector('.navbar-toggler');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.col-md-9.col-lg-10');
    const body = document.querySelector('body');
    
    // Track sidebar state
    let sidebarOpen = false;
    
    // Check if we're on mobile view
    function isMobileView() {
        return window.innerWidth < 992; // Bootstrap's lg breakpoint
    }
    
    // Function to open sidebar
    function openSidebar() {
        sidebar.classList.add('sidebar-mobile-active');
        
        // Add overlay to make background darker
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        body.appendChild(overlay);
        
        // Close sidebar when clicking outside
        overlay.addEventListener('click', closeSidebar);
        
        sidebarOpen = true;
    }
    
    // Function to close sidebar
    function closeSidebar() {
        sidebar.classList.remove('sidebar-mobile-active');
        
        // Remove overlay
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.remove();
        }
        
        sidebarOpen = false;
    }
    
    // Toggle sidebar when clicking navbar-toggler
    sidebarToggler.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isMobileView()) {
            if (sidebarOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }
    });
    
    // Close sidebar on window resize (if going from mobile to desktop)
    window.addEventListener('resize', function() {
        if (!isMobileView() && sidebarOpen) {
            closeSidebar();
        }
    });
    
    // Initialize correct state on page load
    if (isMobileView()) {
        sidebar.classList.add('sidebar-mobile');
        mainContent.classList.add('full-width');
    } else {
        sidebar.classList.remove('sidebar-mobile');
        mainContent.classList.remove('full-width');
    }
    
    // Update classes on window resize
    window.addEventListener('resize', function() {
        if (isMobileView()) {
            sidebar.classList.add('sidebar-mobile');
            mainContent.classList.add('full-width');
        } else {
            sidebar.classList.remove('sidebar-mobile');
            sidebar.classList.remove('sidebar-mobile-active');
            mainContent.classList.remove('full-width');
            
            // Remove overlay if it exists
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    });
});