// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });

    return isValid;
}

// Add error class styling
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .error {
            border-color: red !important;
        }
        .success-message {
            color: green;
            margin: 1rem 0;
        }
        .error-message {
            color: red;
            margin: 1rem 0;
        }
    `;
    document.head.appendChild(style);
}); 