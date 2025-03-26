document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
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
    
    // Filtres
    const filterForm = document.getElementById('filtersForm');
    const filterSelects = filterForm.querySelectorAll('select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Sélection en masse
    const selectAll = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const applyBulkButton = document.getElementById('applyBulkAction');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            updateBulkActionButton();
        });
    }
    
    if (productCheckboxes.length > 0) {
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkActionButton();
                
                // Vérifier si toutes les cases sont cochées
                const allChecked = Array.from(productCheckboxes).every(c => c.checked);
                if (selectAll) {
                    selectAll.checked = allChecked;
                }
            });
        });
    }
    
    function updateBulkActionButton() {
        const anyChecked = Array.from(productCheckboxes).some(c => c.checked);
        if (applyBulkButton) {
            applyBulkButton.disabled = !anyChecked;
        }
    }
    
    // Validation du formulaire d'actions groupées
    const bulkForm = document.getElementById('bulkForm');
    const bulkActionSelect = document.getElementById('bulk_action');
    
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            const selectedAction = bulkActionSelect.value;
            const selectedProducts = Array.from(productCheckboxes).filter(c => c.checked);
            
            if (!selectedAction) {
                e.preventDefault();
                alert('Veuillez sélectionner une action à effectuer.');
                return false;
            }
            
            if (selectedProducts.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un produit.');
                return false;
            }
            
            if (selectedAction === 'delete') {
                if (!confirm('Êtes-vous sûr de vouloir supprimer les produits sélectionnés ? Cette action est irréversible.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});