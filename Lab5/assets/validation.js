
// Client-side form validation

// Registration form validation
if (document.getElementById('registerForm')) {
    const form = document.getElementById('registerForm');
    
    form.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        
        let errors = [];
        
        // Username validation
        if (username.length < 3 || username.length > 50) {
            errors.push('Username must be between 3 and 50 characters');
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            errors.push('Username can only contain letters, numbers, and underscores');
        }
        
        // Email validation
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            errors.push('Please enter a valid email address');
        }
        
        // Password validation
        if (password.length < 8) {
            errors.push('Password must be at least 8 characters long');
        }
        
        if (!/(?=.*[a-z])/.test(password)) {
            errors.push('Password must contain at least one lowercase letter');
        }
        
        if (!/(?=.*[A-Z])/.test(password)) {
            errors.push('Password must contain at least one uppercase letter');
        }
        
        if (!/(?=.*\d)/.test(password)) {
            errors.push('Password must contain at least one number');
        }
        
        if (!/(?=.*[@$!%*?&])/.test(password)) {
            errors.push('Password must contain at least one special character (@$!%*?&)');
        }
        
        // Confirm password validation
        if (password !== confirmPassword) {
            errors.push('Passwords do not match');
        }
        
        // Display errors or submit
        if (errors.length > 0) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
        }
    });
    
    // Real-time password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthDiv = document.createElement('div');
    strengthDiv.style.marginTop = '5px';
    strengthDiv.style.fontSize = '12px';
    passwordInput.parentNode.appendChild(strengthDiv);
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let strengthText = '';
        let strengthColor = '';
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[@$!%*?&]/.test(password)) strength++;
        
        if (strength === 0) {
            strengthText = '';
            strengthColor = '';
        } else if (strength <= 2) {
            strengthText = 'Weak password';
            strengthColor = '#dc3545';
        } else if (strength <= 4) {
            strengthText = 'Medium password';
            strengthColor = '#ffc107';
        } else {
            strengthText = 'Strong password';
            strengthColor = '#28a745';
        }
        
        strengthDiv.textContent = strengthText;
        strengthDiv.style.color = strengthColor;
        strengthDiv.style.fontWeight = 'bold';
    });
    
    // Confirm password matching indicator
    const confirmPasswordInput = document.getElementById('confirm_password');
    const matchDiv = document.createElement('div');
    matchDiv.style.marginTop = '5px';
    matchDiv.style.fontSize = '12px';
    confirmPasswordInput.parentNode.appendChild(matchDiv);
    
    confirmPasswordInput.addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (confirmPassword === '') {
            matchDiv.textContent = '';
        } else if (password === confirmPassword) {
            matchDiv.textContent = 'Passwords match ✓';
            matchDiv.style.color = '#28a745';
            matchDiv.style.fontWeight = 'bold';
        } else {
            matchDiv.textContent = 'Passwords do not match ✗';
            matchDiv.style.color = '#dc3545';
            matchDiv.style.fontWeight = 'bold';
        }
    });
}

// Login form validation
if (document.getElementById('loginForm')) {
    const form = document.getElementById('loginForm');
    
    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (username === '' || password === '') {
            e.preventDefault();
            alert('Please enter both username and password');
        }
    });
}

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Confirmation for rejection actions
document.addEventListener('DOMContentLoaded', function() {
    const rejectButtons = document.querySelectorAll('button[value="rejected"]');
    
    rejectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to reject this enrollment request?')) {
                e.preventDefault();
            }
        });
    });
});