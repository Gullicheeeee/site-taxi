/**
 * BACK-OFFICE TAXI JULIEN - JavaScript
 */

// Auto-dismiss des alertes
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
});

// Confirmation de suppression
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
        if (!confirm(el.dataset.confirm || 'Êtes-vous sûr ?')) {
            e.preventDefault();
        }
    });
});

// Toggle mobile menu
const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
const sidebar = document.querySelector('.sidebar');

if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
}

// Fermer sidebar quand on clique en dehors (mobile)
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && !mobileMenuToggle?.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

// Auto-resize textarea
document.querySelectorAll('textarea[data-autoresize]').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});

// Copy to clipboard helper
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Afficher une notification
        const toast = document.createElement('div');
        toast.className = 'alert alert-success';
        toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999;';
        toast.textContent = 'Copié !';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}

// Slug generator
function generateSlug(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Auto-generate slug from title
const titleInput = document.querySelector('input[name="title"]');
const slugInput = document.querySelector('input[name="slug"]');

if (titleInput && slugInput && !slugInput.value) {
    titleInput.addEventListener('input', () => {
        slugInput.value = generateSlug(titleInput.value);
    });
}

console.log('Back-office Taxi Julien chargé');
