
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordField = document.getElementById('password-field');
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    
    // Toggle icon
    this.classList.toggle('bi-eye');
    this.classList.toggle('bi-eye-slash');
});

// Add some simple form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password-field').value.trim();
    
    if (username === '' || password === '') {
        e.preventDefault();
        alert('Please fill in all fields');
    }
});
