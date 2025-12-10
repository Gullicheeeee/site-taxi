// ADMIN.JS - Back Office Taxi Julien

document.addEventListener('DOMContentLoaded', function() {

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Character counter for textareas
    const textareas = document.querySelectorAll('[data-maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.dataset.maxlength;
        const counter = document.createElement('div');
        counter.className = 'form-help';
        counter.textContent = `${textarea.value.length} / ${maxLength} caractères`;
        textarea.parentNode.appendChild(counter);

        textarea.addEventListener('input', function() {
            counter.textContent = `${this.value.length} / ${maxLength} caractères`;
            if (this.value.length > maxLength) {
                counter.style.color = 'var(--danger)';
            } else {
                counter.style.color = 'var(--gray-500)';
            }
        });
    });

    // Image preview
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(input.dataset.preview);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Slug generator from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            if (!slugInput.dataset.manuallyEdited) {
                slugInput.value = generateSlug(this.value);
            }
        });

        slugInput.addEventListener('input', function() {
            this.dataset.manuallyEdited = 'true';
        });
    }

    // Rich text editor (simple implementation)
    const editorToolbars = document.querySelectorAll('.editor-toolbar');
    editorToolbars.forEach(toolbar => {
        const buttons = toolbar.querySelectorAll('.editor-btn');
        const textarea = toolbar.nextElementSibling;

        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const command = this.dataset.command;
                applyTextFormat(textarea, command);
            });
        });
    });

    // Table row click to edit
    const editableRows = document.querySelectorAll('[data-edit-url]');
    editableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('a')) {
                window.location.href = this.dataset.editUrl;
            }
        });
    });

});

// Helper Functions

function generateSlug(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function applyTextFormat(textarea, command) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    let formattedText = selectedText;

    switch (command) {
        case 'bold':
            formattedText = `**${selectedText}**`;
            break;
        case 'italic':
            formattedText = `*${selectedText}*`;
            break;
        case 'h2':
            formattedText = `## ${selectedText}`;
            break;
        case 'h3':
            formattedText = `### ${selectedText}`;
            break;
        case 'link':
            const url = prompt('URL du lien:');
            if (url) {
                formattedText = `[${selectedText}](${url})`;
            }
            break;
        case 'list':
            formattedText = `- ${selectedText}`;
            break;
    }

    textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start, start + formattedText.length);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copié dans le presse-papier !');
    });
}

// AJAX helper
async function ajaxRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('AJAX Error:', error);
        throw error;
    }
}
