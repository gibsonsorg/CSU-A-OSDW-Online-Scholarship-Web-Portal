// Function to show Register Form
function showRegister() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const forgotForm = document.getElementById('forgotForm');

    if(loginForm) loginForm.classList.add('hidden');
    if(forgotForm) forgotForm.classList.add('hidden');
    if(registerForm) registerForm.classList.remove('hidden');
}

// Function to show Login Form
function showLogin() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const forgotForm = document.getElementById('forgotForm');

    if(registerForm) registerForm.classList.add('hidden');
    if(forgotForm) forgotForm.classList.add('hidden');
    if(loginForm) loginForm.classList.remove('hidden');
}

// Function to show Forgot Password Form
function showForgotPassword() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const forgotForm = document.getElementById('forgotForm');

    if(loginForm) loginForm.classList.add('hidden');
    if(registerForm) registerForm.classList.add('hidden');
    if(forgotForm) forgotForm.classList.remove('hidden');
}

// Check URL on page load
document.addEventListener("DOMContentLoaded", function() {
    const path = window.location.pathname;
    
    if (path.includes('register')) {
        showRegister();
    } else {
        // Default to login if the path is /login or anything else
        showLogin();
    }
});

// Password Toggle Helper
function togglePassword(id) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}