// Form validation
document.getElementById('registrationForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms').checked;
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long');
        return;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match');
        return;
    }
    
    if (!terms) {
        e.preventDefault();
        alert('You must agree to the Terms and Conditions');
        return;
    }
});