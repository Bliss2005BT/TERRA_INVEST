<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isUserLoggedIn()) {
    redirectTo('search.php');
}

$flash = getFlash();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terra Invest & Co.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Inter:wght@300;400;500;600&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
    <link rel="icon" type="image/png" href="assets/logo_black.png">
</head>
<body>
    <header class="header-wrapper">
        <nav class="floating-navbar">
            <div class="nav-logo">
                <img src="assets/logo_black.png" alt="Terra Invest Co.">
                <span class="logo-text">Terra Invest Co.</span>
            </div>

            <div class="nav-links">
                <div class="close-btn" onclick="toggleMenu()">
                    <span>&times;</span>
                </div>
                <a href="#about" class="nav-item">About</a>
                <a href="#pricing" class="nav-item">Pricing</a>
                <a href="#services" class="nav-item">Services</a>
                <a href="contact.php" class="nav-item">Contact Us</a>
            </div>

            <div class="nav-actions">
                <div id="auth-buttons">
                    <a href="#" class="btn-login" onclick="login(); return false;">Login</a>
                    <a href="#" class="btn-start" onclick="register(); return false;">Start investing</a>
                </div>
                <div class="hamburger" onclick="toggleMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>
    </header>

    <section class="modern-hero-frame">
        <div class="visual-card" style="background-image: url('assets/farm-5836815_1920.jpg');">
            <div class="content-overlay">
                <div class="text-stack">
                    <h1 class="main-display-title">OWN YOUR HORIZON.</h1>
                    <p class="description-text">
                        "Securing the landscapes of tomorrow. Your journey into <br>premier land ownership starts here."
                    </p>

                    <div class="cta-wrapper">
                        <button class="btn-modern-white">Discover Acreage</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="aim-wireframe">
        <div class="aim-wrapper">
            <div class="aim-left">
                <div class="video-box">
                    <video autoplay muted loop playsinline>
                        <source src="assets/hero-video.mp4" type="video/mp4">
                    </video>
                </div>
            </div>

            <div class="aim-right">
                <div class="title-box">
                    <h1>Transforming the Way Land is Bought and Sold</h1>
                </div>

                <div class="text-box">
                    <p>
                        Our aim is to simplify land buying and selling by creating a transparent,
                        reliable, and technology-driven platform. Terra Invest focuses on verified
                        land listings, clear visual insights, and direct buyer-seller communication
                        to eliminate uncertainty, reduce brokerage dependency, and build trust in
                        land transactions.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="services-wrapper">
        <div class="services-content">
            <div class="services-grid">
                <div class="service">
                    <h3>Verified Land Listings</h3>
                    <p>Authentic land properties with verified ownership and legal documentation.</p>
                </div>

                <div class="service">
                    <h3>Buyer-Seller Allocation</h3>
                    <p>Directly connecting buyers with trusted sellers based on needs and budget.</p>
                </div>

                <div class="service">
                    <h3>Property Verification</h3>
                    <p>Detailed checks on land records, ownership history, and compliance.</p>
                </div>

                <div class="service">
                    <h3>Location-Based Land Deals</h3>
                    <p>Curated land opportunities in prime and developing regions.</p>
                </div>

                <div class="service">
                    <h3>Secure Customer Information</h3>
                    <p>Strict protection of personal and investment data.</p>
                </div>
            </div>

            <div class="our-services-content">
                <h2>Our Services</h2>
                <p>
                    Discover a transparent way to invest in land. We provide end-to-end support
                    from legal verification to direct seller communication, ensuring your
                    investment is secure and profitable.
                </p>
                <button>Learn More</button>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Quick links</h3>
                <a href="#home">home</a>
                <a href="#packages">packages</a>
                <a href="#query">query</a>
            </div>

            <div class="footer-section">
                <h3>Extra links</h3>
                <a href="#contact">ask questions</a>
                <a href="#">terms of use</a>
                <a href="#">privacy policy</a>
            </div>

            <div class="footer-section">
                <h3>Contact Info</h3>
                <p>+91 84464 41988</p>
                <p>tuscanojuan17@gmail.com</p>
                <p>Virar, Mumbai, Maharashtra - 401301</p>
            </div>

            <div class="footer-section">
                <h3>Follow us</h3>
                <a href="#">Instagram</a>
                <a href="#">LinkedIn</a>
                <a href="#">GitHub</a>
            </div>
        </div>

        <div class="footer-bottom">
            <p>Created by Bliss Tuscano & Juan Tuscano | All rights reserved</p>
        </div>
    </footer>

    <div id="auth-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Welcome Back</h2>
                <button class="modal-close" onclick="closeAuthModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('login')">Login</button>
                    <button class="tab-btn" onclick="switchTab('register')">Register</button>
                </div>

                <div id="login-tab" class="tab-content active">
                    <form action="php/login.php" method="POST" class="auth-form" id="login-form" data-auth-form="login" novalidate>
                        <div class="auth-status" data-form-status aria-live="polite" hidden></div>
                        <div class="form-group">
                            <input id="login-email" type="email" name="email" placeholder="Email ID" autocomplete="email" aria-label="Email ID">
                            <p class="field-error" data-error-for="email" aria-live="polite"></p>
                        </div>
                        <div class="form-group">
                            <div class="password-field">
                                <input id="login-password" type="password" name="password" placeholder="Password" autocomplete="current-password" aria-label="Password">
                                <button type="button" class="password-toggle" data-toggle-password aria-label="Show password">Show</button>
                            </div>
                            <p class="field-error" data-error-for="password" aria-live="polite"></p>
                        </div>
                        <div class="auth-form-meta">
                            <a href="mailto:tuscanojuan17@gmail.com?subject=Password%20reset%20request" class="forgot-password-link">Forgot Password?</a>
                        </div>
                        <button type="submit" name="login" class="auth-submit">
                            <span class="auth-submit-label">Login</span>
                            <span class="auth-submit-spinner" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>

                <div id="register-tab" class="tab-content">
                    <form action="php/register.php" method="POST" class="auth-form" id="register-form" data-auth-form="register" novalidate>
                        <div class="auth-status" data-form-status aria-live="polite" hidden></div>
                        <div class="form-group">
                            <input id="register-name" type="text" name="name" placeholder="Full Name" autocomplete="name" aria-label="Full Name">
                            <p class="field-error" data-error-for="name" aria-live="polite"></p>
                        </div>
                        <div class="form-group">
                            <input id="register-email" type="email" name="email" placeholder="Email ID" autocomplete="email" aria-label="Email ID">
                            <p class="field-error" data-error-for="email" aria-live="polite"></p>
                        </div>
                        <div class="form-group">
                            <div class="password-field">
                                <input id="register-password" type="password" name="password" placeholder="Password" autocomplete="new-password" aria-label="Password">
                                <button type="button" class="password-toggle" data-toggle-password aria-label="Show password">Show</button>
                            </div>
                            <p class="field-error" data-error-for="password" aria-live="polite"></p>
                        </div>
                        <div class="form-group">
                            <div class="password-field">
                                <input id="register-confirm-password" type="password" name="confirm_password" placeholder="Confirm Password" autocomplete="new-password" aria-label="Confirm Password">
                                <button type="button" class="password-toggle" data-toggle-password aria-label="Show password">Show</button>
                            </div>
                            <p class="field-error" data-error-for="confirm_password" aria-live="polite"></p>
                        </div>
                        <button type="submit" name="register" class="auth-submit">
                            <span class="auth-submit-label">Create Account</span>
                            <span class="auth-submit-spinner" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.authPageFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
</body>
</html>
