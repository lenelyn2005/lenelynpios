// Sidebar JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    
    // Toggle sidebar on desktop
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleMobileSidebar();
            } else {
                toggleDesktopSidebar();
            }
        });
    }
    
    // Mobile menu button
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            toggleMobileSidebar();
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeMobileSidebar();
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileSidebar();
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth <= 768) {
            closeMobileSidebar();
        }
    });
    
    function toggleDesktopSidebar() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('sidebar-collapsed');
        
        // Save preference
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }
    
    function toggleMobileSidebar() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    }
    
    function closeMobileSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    }
    
    // Restore sidebar state on page load
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
    if (sidebarCollapsed === 'true' && window.innerWidth > 768) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
    }
    
    // Add active class to current page
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.closest('.nav-item').classList.add('active');
        }
    });
    
    // Add smooth scrolling to sidebar
    const sidebarNav = document.querySelector('.sidebar-nav');
    if (sidebarNav) {
        sidebarNav.style.scrollBehavior = 'smooth';
    }
    
    // Add loading states to navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't add loading for external links or same page
            if (this.target === '_blank' || this.href === window.location.href) {
                return;
            }
            
            // Add loading indicator
            const icon = this.querySelector('i');
            if (icon) {
                const originalClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                // Reset after 3 seconds (fallback)
                setTimeout(() => {
                    icon.className = originalClass;
                }, 3000);
            }
        });
    });
    
    // Add tooltips for collapsed sidebar
    if (window.innerWidth > 768) {
        addSidebarTooltips();
    }
    
    function addSidebarTooltips() {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            const link = item.querySelector('.nav-link');
            const text = link.querySelector('.nav-text').textContent;
            
            // Create tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'sidebar-tooltip';
            tooltip.textContent = text;
            tooltip.style.cssText = `
                position: absolute;
                left: 100%;
                top: 50%;
                transform: translateY(-50%);
                background: #2c3e50;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                z-index: 1001;
                margin-left: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            
            item.style.position = 'relative';
            item.appendChild(tooltip);
            
            // Show tooltip on hover
            item.addEventListener('mouseenter', function() {
                if (sidebar.classList.contains('collapsed')) {
                    tooltip.style.opacity = '1';
                    tooltip.style.visibility = 'visible';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
            });
        });
    }
    
    // Add search functionality to sidebar (if search input exists)
    const sidebarSearch = document.querySelector('.sidebar-search');
    if (sidebarSearch) {
        const searchInput = sidebarSearch.querySelector('input');
        const navItems = document.querySelectorAll('.nav-item');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            navItems.forEach(item => {
                const text = item.querySelector('.nav-text').textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Add keyboard navigation
    let currentNavIndex = -1;
    const navItems = document.querySelectorAll('.nav-item:not([style*="display: none"])');
    
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 'ArrowDown') {
            e.preventDefault();
            currentNavIndex = Math.min(currentNavIndex + 1, navItems.length - 1);
            focusNavItem(currentNavIndex);
        } else if (e.altKey && e.key === 'ArrowUp') {
            e.preventDefault();
            currentNavIndex = Math.max(currentNavIndex - 1, 0);
            focusNavItem(currentNavIndex);
        } else if (e.altKey && e.key === 'Enter') {
            e.preventDefault();
            if (currentNavIndex >= 0 && navItems[currentNavIndex]) {
                navItems[currentNavIndex].querySelector('.nav-link').click();
            }
        }
    });
    
    function focusNavItem(index) {
        navItems.forEach((item, i) => {
            if (i === index) {
                item.classList.add('keyboard-focus');
                item.querySelector('.nav-link').focus();
            } else {
                item.classList.remove('keyboard-focus');
            }
        });
    }
    
    // Add keyboard focus styles
    const style = document.createElement('style');
    style.textContent = `
        .nav-item.keyboard-focus .nav-link {
            background: rgba(52, 152, 219, 0.3) !important;
            outline: 2px solid #3498db;
            outline-offset: -2px;
        }
    `;
    document.head.appendChild(style);
});

// Utility functions for sidebar
window.SidebarUtils = {
    // Programmatically toggle sidebar
    toggle: function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.click();
        }
    },
    
    // Close sidebar
    close: function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        } else {
            sidebar.classList.remove('collapsed');
            document.querySelector('.main-content').classList.remove('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    },
    
    // Open sidebar
    open: function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.classList.add('sidebar-open');
        } else {
            sidebar.classList.remove('collapsed');
            document.querySelector('.main-content').classList.remove('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    },
    
    // Set active page
    setActive: function(pageName) {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.classList.remove('active');
            const link = item.querySelector('.nav-link');
            if (link && link.getAttribute('href').includes(pageName)) {
                item.classList.add('active');
            }
        });
    }
};
