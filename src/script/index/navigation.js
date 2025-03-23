// navigation.js - Gestion du défilement fluide et de la navigation fixe

document.addEventListener('DOMContentLoaded', function() {
    // Référence aux éléments DOM
    const header = document.querySelector('header');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    
    // Hauteur de décalage pour le défilement (hauteur de l'en-tête + marge)
    const offsetHeight = header.offsetHeight + 20;
    
    // Ajuster la hauteur de la barre latérale
    function adjustSidebarHeight() {
        sidebar.style.height = `calc(100vh - ${offsetHeight}px)`;
    }
    
    // Gérer le défilement fluide vers les sections
    function setupSmoothScroll() {
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Récupérer l'ID de la cible
                const targetId = this.getAttribute('href');
                
                // Si c'est un lien vers une autre page, suivre le lien
                if (!targetId.startsWith('#')) {
                    window.location.href = targetId;
                    return;
                }
                
                // Trouver l'élément cible
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    // Calculer la position de défilement
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - offsetHeight;
                    
                    // Faire défiler la page
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Mettre à jour l'URL avec le fragment
                    history.pushState(null, null, targetId);
                    
                    // Mettre à jour la navigation active
                    updateActiveLink(targetId);
                    
                    // Fermer la barre latérale sur mobile
                    if (window.innerWidth <= 992) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        });
    }
    
    // Mettre à jour le lien actif dans la barre latérale
    function updateActiveLink(targetId) {
        // Supprimer la classe active de tous les liens
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
        });
        
        // Ajouter la classe active au lien correspondant
        const activeLink = document.querySelector(`.sidebar-menu a[href="${targetId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
    
    // Observer l'intersection des sections pour mettre à jour la navigation active
    function setupIntersectionObserver() {
        const sections = document.querySelectorAll('.card-header[id]');
        
        const observerOptions = {
            rootMargin: `-${offsetHeight}px 0px -70% 0px`
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const targetId = '#' + entry.target.id;
                    updateActiveLink(targetId);
                    
                    // Mettre à jour l'URL sans déclencher un défilement
                    if (history.replaceState) {
                        history.replaceState(null, null, targetId);
                    }
                }
            });
        }, observerOptions);
        
        sections.forEach(section => {
            observer.observe(section);
        });
    }
    
    // Gérer le bouton de menu mobile
    function setupMobileMenu() {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Fermer le menu lorsqu'on clique en dehors
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992 && 
                sidebar.classList.contains('show') && 
                !sidebar.contains(e.target) && 
                e.target !== menuToggle) {
                sidebar.classList.remove('show');
            }
        });
    }
    
    // Initialiser la navigation
    function init() {
        adjustSidebarHeight();
        setupSmoothScroll();
        setupIntersectionObserver();
        setupMobileMenu();
        
        // Gérer le redimensionnement de la fenêtre
        window.addEventListener('resize', function() {
            adjustSidebarHeight();
            
            // Fermer la barre latérale sur les petits écrans
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
            }
        });
        
        // Si l'URL contient un fragment, faire défiler vers cette section
        if (window.location.hash) {
            const targetElement = document.querySelector(window.location.hash);
            if (targetElement) {
                // Petit délai pour s'assurer que la page est entièrement chargée
                setTimeout(() => {
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - offsetHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'auto'
                    });
                    updateActiveLink(window.location.hash);
                }, 100);
            }
        }
    }
    
    // Démarrer l'initialisation
    init();
});