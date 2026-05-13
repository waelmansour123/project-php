// SkillHub Platform Javascript
document.addEventListener('DOMContentLoaded', () => {
    
    // Mobile Navigation Toggle
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            
            // Toggle hamburger icon animation
            const spans = navToggle.querySelectorAll('span');
            if (navLinks.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(6px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }

    // Auto-Dismiss Alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Confirmation dialog for hazardous actions
    const deleteButtons = document.querySelectorAll('.btn-confirm-delete, .btn-danger');
    deleteButtons.forEach(button => {
        // Exclude simple forms that don't need cancellation confirmation
        if (button.tagName === 'A' || button.tagName === 'BUTTON') {
            button.addEventListener('click', (e) => {
                const message = button.getAttribute('data-confirm') || 'Are you absolutely sure you want to perform this action?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        }
    });

    // Register password match validation
    const registerForm = document.querySelector('.register-form');
    if (registerForm) {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        registerForm.addEventListener('submit', (e) => {
            if (passwordInput && confirmPasswordInput) {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    alert('Passwords do not match. Please enter matching passwords.');
                }
            }
        });
    }
});
