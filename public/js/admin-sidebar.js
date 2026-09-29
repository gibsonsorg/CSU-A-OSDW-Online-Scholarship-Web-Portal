(() => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const adminMain = document.getElementById('adminMain');

    if (!sidebarToggle || !adminSidebar || !adminMain) return;

    const applyCollapsedState = isCollapsed => {
        adminSidebar.classList.toggle('collapsed', isCollapsed);
        adminMain.classList.toggle('sidebar-collapsed', isCollapsed);
        sidebarToggle.classList.toggle('active', isCollapsed);
    };

    sidebarToggle.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();

        const isCollapsed = !adminSidebar.classList.contains('collapsed');
        applyCollapsedState(isCollapsed);
        localStorage.setItem('adminSidebarCollapsed', String(isCollapsed));
    });

    applyCollapsedState(localStorage.getItem('adminSidebarCollapsed') === 'true');
})();
