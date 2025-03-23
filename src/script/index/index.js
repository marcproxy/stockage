document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    // Toggle sidebar on mobile
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992 && 
            sidebar.classList.contains('show') && 
            !sidebar.contains(e.target) && 
            e.target !== menuToggle) {
            sidebar.classList.remove('show');
        }
    });
    
    // Search functionality
    const searchInput = document.getElementById('searchProduit');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.location.href = './src/templates/inventory/inventory.php?search=' + encodeURIComponent(this.value);
            }
        });
    }
});