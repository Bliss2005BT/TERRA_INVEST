function toggleMenu() {
    const navLinks = document.querySelector('.nav-links');
    const hamburger = document.querySelector('.hamburger');
    const body = document.body;

    const isOpen = navLinks.classList.contains('mobile-open');

    if (isOpen) {
        // Closing menu
        navLinks.classList.remove('mobile-open');
        hamburger.classList.remove('active');
        body.style.overflow = '';
    } else {
        // Opening menu
        navLinks.classList.add('mobile-open');
        hamburger.classList.add('active');
        body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

// Close mobile menu when a nav link is clicked
document.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', () => {
        const navLinks = document.querySelector('.nav-links');
        const hamburger = document.querySelector('.hamburger');
        const body = document.body;

        navLinks.classList.remove('mobile-open');
        hamburger.classList.remove('active');
        body.style.overflow = '';
    });
});

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    const navLinks = document.querySelector('.nav-links');
    const hamburger = document.querySelector('.hamburger');
    const body = document.body;

    if (navLinks.classList.contains('mobile-open') &&
        !navLinks.contains(e.target) &&
        !hamburger.contains(e.target)) {
        navLinks.classList.remove('mobile-open');
        hamburger.classList.remove('active');
        body.style.overflow = '';
    }
});

// Auth Modal Functions
function login() {
    openAuthModal('login');
}

function register() {
    openAuthModal('register');
}

function openAuthModal(tab) {
    const modal = document.getElementById('auth-modal');
    const modalTitle = document.getElementById('modal-title');

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    switchTab(tab);

    if (tab === 'login') {
        modalTitle.textContent = 'Welcome Back';
    } else {
        modalTitle.textContent = 'Join Terra Invest';
    }
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tab + '-tab').classList.add('active');

    // Add active class to selected tab button
    document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');

    // Update modal title
    const modalTitle = document.getElementById('modal-title');
    if (tab === 'login') {
        modalTitle.textContent = 'Welcome Back';
    } else {
        modalTitle.textContent = 'Join Terra Invest';
    }
}

// Close modal when clicking outside
document.getElementById('auth-modal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('auth-modal')) {
        closeAuthModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('auth-modal').classList.contains('active')) {
        closeAuthModal();
    }
});

// Handle success/error messages from PHP
document.addEventListener('DOMContentLoaded', function() {
    // Load session data from PHP
    loadSessionData();

    // Check for success message
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');

    if (success) {
        showMessage(success, 'success');
    }
    if (error) {
        showMessage(error, 'error');
    }
});

function loadSessionData() {
    fetch('php/session_data.php')
        .then(response => response.json())
        .then(data => {
            window.userSession = data;
            updateNavbar();
        })
        .catch(error => {
            console.error('Error loading session data:', error);
            // Fallback: assume not logged in
            window.userSession = { isLoggedIn: false, userName: null };
            updateNavbar();
        });
}

function updateNavbar() {
    const authButtons = document.getElementById('auth-buttons');

    if (window.userSession && window.userSession.isLoggedIn) {
        // User is logged in
        authButtons.innerHTML = `
            <span class="user-greeting">Welcome, ${window.userSession.userName}</span>
            <a href="php/logout.php" class="btn-logout">Logout</a>
        `;
    } else {
        // User is not logged in
        authButtons.innerHTML = `
            <a href="#" class="btn-login" onclick="login()">Login</a>
            <a href="#" class="btn-start" onclick="register()">Start investing</a>
        `;
    }
}

function showMessage(message, type) {
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        max-width: 300px;
        ${type === 'success' ? 'background-color: #4CAF50;' : 'background-color: #f44336;'}
    `;

    document.body.appendChild(messageDiv);

    // Remove message after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}