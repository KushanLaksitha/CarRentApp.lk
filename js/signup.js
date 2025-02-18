
document.getElementById('signupForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
    
    // Basic password strength validation
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long!');
    }
});
