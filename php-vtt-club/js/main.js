/**
 * =====================================================
 * JavaScript principal - Xendrazkoak VTT
 * =====================================================
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // =====================================================
    // Menu mobile
    // =====================================================
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileMenuToggle && mainNav) {
        mobileMenuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('open');
        });
        
        // Fermer le menu si on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!mainNav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                mainNav.classList.remove('open');
            }
        });
    }
    
    // =====================================================
    // Confirmation de suppression
    // =====================================================
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Êtes-vous sûr de vouloir effectuer cette action ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // =====================================================
    // Prévisualisation des images uploadées
    // =====================================================
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            const preview = document.getElementById(this.dataset.preview);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // =====================================================
    // Auto-hide des alertes après 5 secondes
    // =====================================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
    
    // =====================================================
    // Validation du formulaire d'inscription (mineur)
    // =====================================================
    const signupForm = document.querySelector('form[data-validate-age]');
    if (signupForm) {
        const ageInput = signupForm.querySelector('input[name="age"]');
        const responsableGroup = document.getElementById('responsable-group');
        
        if (ageInput && responsableGroup) {
            function checkAge() {
                const age = parseInt(ageInput.value) || 0;
                if (age > 0 && age < 18) {
                    responsableGroup.style.display = 'block';
                    responsableGroup.querySelector('input').required = true;
                } else {
                    responsableGroup.style.display = 'none';
                    responsableGroup.querySelector('input').required = false;
                }
            }
            
            ageInput.addEventListener('input', checkAge);
            checkAge(); // Vérifier au chargement
        }
    }
    
    // =====================================================
    // Compteur de caractères pour les textarea
    // =====================================================
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(function(textarea) {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('div');
        counter.className = 'form-text text-right';
        counter.innerHTML = '<span class="current">0</span> / ' + maxLength + ' caractères';
        textarea.parentNode.appendChild(counter);
        
        const currentSpan = counter.querySelector('.current');
        
        function updateCounter() {
            currentSpan.textContent = textarea.value.length;
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
    
    // =====================================================
    // Filtre en temps réel sur les tableaux
    // =====================================================
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(function(input) {
        const tableId = input.dataset.tableSearch;
        const table = document.getElementById(tableId);
        
        if (table) {
            input.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    });
});
