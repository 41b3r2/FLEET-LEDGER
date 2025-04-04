document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const forgotLink = document.getElementById('forgotLink');
    const loginForm = document.getElementById('loginForm');
    
    loginForm.addEventListener('submit', function(e) {
e.preventDefault();

const loginBtn = document.querySelector('.login-btn');
loginBtn.innerHTML = 'Logging in...';

setTimeout(() => {
    if (usernameInput.value && passwordInput.value) {
loginBtn.innerHTML = 'Success!';
loginBtn.style.backgroundColor = '#4CAF50';
loginBtn.style.color = 'white';
    } else {
loginBtn.innerHTML = 'Try Again';
loginBtn.style.backgroundColor = '#f44336';
loginBtn.style.color = 'white';
    }
    
    setTimeout(() => {
loginBtn.innerHTML = 'Login';
loginBtn.style.backgroundColor = 'white';
loginBtn.style.color = '#dc0000';
    }, 2000);
}, 1000);
    });
    
    const passwordIcon = document.querySelector('.password-icon');
    passwordIcon.addEventListener('click', function() {
if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    passwordIcon.textContent = '👁️';
} else {
    passwordInput.type = 'password';
    passwordIcon.textContent = '🔒';
}
    });
    
    forgotLink.addEventListener('click', function(e) {
e.preventDefault();
alert('Password reset functionality will be implemented here.');
    });
    
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
input.addEventListener('focus', function() {
    this.parentElement.style.transform = 'scale(1.02)';
});

input.addEventListener('blur', function() {
    this.parentElement.style.transform = 'scale(1)';
});
    });
});