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
    
    // Dépendances des listes déroulantes
    const armoireSelect = document.getElementById('id_armoire');
    const etagereSelect = document.getElementById('id_etagere');
    const sectionSelect = document.getElementById('id_section');
    
    // Lorsqu'une armoire est sélectionnée, charger les étagères correspondantes
    armoireSelect.addEventListener('change', function() {
        const id_armoire = this.value;
        
        // Réinitialiser les sélections d'étagère et de section
        etagereSelect.innerHTML = '<option value="">Chargement...</option>';
        etagereSelect.disabled = true;
        sectionSelect.innerHTML = '<option value="">Sélectionner d\'abord un étage</option>';
        sectionSelect.disabled = true;
        
        if (id_armoire) {
            // Requête AJAX pour récupérer les étagères
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../../src/api/get-etageres.php?id_armoire=${id_armoire}`, true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = xhr.responseText;
                        // Vérifier si la réponse contient du HTML ou une erreur PHP
                        if (response.includes('<!DOCTYPE html>') || response.includes('<br />')) {
                            console.error('Erreur: La réponse contient du HTML au lieu de JSON');
                            etagereSelect.innerHTML = '<option value="">Erreur de chargement (réponse non-JSON)</option>';
                            etagereSelect.disabled = false;
                            return;
                        }
                        
                        const etageres = JSON.parse(response);
                        
                        // Mettre à jour le select des étagères
                        etagereSelect.innerHTML = '<option value="">Sélectionner un étage</option>';
                        
                        etageres.forEach(function(etagere) {
                            const option = document.createElement('option');
                            option.value = etagere.id_etagere;
                            option.textContent = `Étage ${etagere.numero_etagere}`;
                            etagereSelect.appendChild(option);
                        });
                        
                        etagereSelect.disabled = false;
                    } catch (e) {
                        console.error('Erreur de parsing JSON:', e);
                        etagereSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    }
                } else {
                    etagereSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                }
            };
            
            xhr.onerror = function() {
                etagereSelect.innerHTML = '<option value="">Erreur de connexion</option>';
            };
            
            xhr.send();
        } else {
            etagereSelect.innerHTML = '<option value="">Sélectionner d\'abord une armoire</option>';
        }
    });
    
    // Lorsqu'une étagère est sélectionnée, charger les sections correspondantes
    etagereSelect.addEventListener('change', function() {
        const id_etagere = this.value;
        
        // Réinitialiser la sélection de section
        sectionSelect.innerHTML = '<option value="">Chargement...</option>';
        sectionSelect.disabled = true;
        
        if (id_etagere) {
            // Requête AJAX pour récupérer les sections
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../../src/api/get-sections.php?id_etagere=${id_etagere}`, true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = xhr.responseText;
                        // Vérifier si la réponse contient du HTML ou une erreur PHP
                        if (response.includes('<!DOCTYPE html>') || response.includes('<br />')) {
                            console.error('Erreur: La réponse contient du HTML au lieu de JSON');
                            sectionSelect.innerHTML = '<option value="">Erreur de chargement (réponse non-JSON)</option>';
                            sectionSelect.disabled = false;
                            return;
                        }
                        
                        const sections = JSON.parse(response);
                        
                        // Mettre à jour le select des sections
                        sectionSelect.innerHTML = '<option value="">Sélectionner une section</option>';
                        
                        sections.forEach(function(section) {
                            const option = document.createElement('option');
                            option.value = section.id_section;
                            option.textContent = `Section ${section.numero_section}`;
                            sectionSelect.appendChild(option);
                        });
                        
                        sectionSelect.disabled = false;
                    } catch (e) {
                        console.error('Erreur de parsing JSON:', e);
                        sectionSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    }
                } else {
                    sectionSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                }
            };
            
            xhr.onerror = function() {
                sectionSelect.innerHTML = '<option value="">Erreur de connexion</option>';
            };
            
            xhr.send();
        } else {
            sectionSelect.innerHTML = '<option value="">Sélectionner d\'abord un étage</option>';
        }
    });
});