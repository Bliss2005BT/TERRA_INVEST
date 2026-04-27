<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$isLoggedIn = isUserLoggedIn();
$exploreUrl = $isLoggedIn ? 'search.php' : 'index.php';
$addPropertyUrl = $isLoggedIn ? 'pages/subscription.php' : 'index.php';

$services = [
    [
        'title' => 'Land Buying',
        'description' => 'Browse verified listings with fast search, filters, and cleaner comparisons.',
        'tags' => ['Verified listings', 'Easy search'],
        'icon' => 'map',
    ],
    [
        'title' => 'Land Selling',
        'description' => 'Add property with images, video, and documents in one guided flow.',
        'tags' => ['Media uploads', 'Seller tools'],
        'icon' => 'home',
    ],
    [
        'title' => 'Document Verification',
        'description' => 'Upload 7/12, Ferfar, and Title Report files to build buyer trust early.',
        'tags' => ['7/12', 'Ferfar', 'Title report'],
        'icon' => 'shield',
    ],
    [
        'title' => 'Property Insights',
        'description' => 'Spot pricing patterns, investment potential, and stronger decisions faster.',
        'tags' => ['Price trends', 'Investment view'],
        'icon' => 'chart',
    ],
    [
        'title' => 'Location Intelligence',
        'description' => 'Use verified video uploads and stronger visual context before you enquire.',
        'tags' => ['Video integrity', 'Visual proof'],
        'icon' => 'pin',
    ],
    [
        'title' => 'Direct Contact',
        'description' => 'Connect with buyers or sellers directly by call or WhatsApp, without middlemen.',
        'tags' => ['Call seller', 'WhatsApp'],
        'icon' => 'phone',
    ],
];

$trustItems = [
    [
        'title' => 'Verified Listings',
        'description' => 'Property details and uploads are structured to support trust before contact.',
        'icon' => 'shield',
        'tone' => 'success',
    ],
    [
        'title' => 'Transparent Information',
        'description' => 'Key land data, pricing, visuals, and documents stay easy to review.',
        'icon' => 'document',
        'tone' => 'default',
    ],
    [
        'title' => 'Easy-to-use Platform',
        'description' => 'A cleaner workflow for both discovery and listing creation across devices.',
        'icon' => 'spark',
        'tone' => 'default',
    ],
    [
        'title' => 'Secure System',
        'description' => 'Uploads, inquiries, and listing workflows are handled in a more controlled setup.',
        'icon' => 'lock',
        'tone' => 'success',
    ],
];

$steps = [
    [
        'title' => 'Create Account',
        'description' => 'Sign in to unlock listing tools and a smoother buying or selling flow.',
        'icon' => 'user',
    ],
    [
        'title' => 'Search or Add Listing',
        'description' => 'Browse land opportunities or start publishing your property details.',
        'icon' => 'search',
    ],
    [
        'title' => 'Upload Documents',
        'description' => 'Attach ownership and title files to strengthen confidence and reduce friction.',
        'icon' => 'upload',
    ],
    [
        'title' => 'Contact Buyer/Seller',
        'description' => 'Move quickly from discovery to direct conversation and the next decision.',
        'icon' => 'message',
    ],
];

function renderServiceIcon(string $icon): string
{
    $icons = [
        'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4.5 3.5 6.7a1 1 0 0 0-.63.93v11.24a.6.6 0 0 0 .83.56L9 17.2l6 2.3 5.5-2.2a1 1 0 0 0 .63-.93V5.13a.6.6 0 0 0-.83-.56L15 6.8 9 4.5Zm0 1.82 5 1.92v9.44l-5-1.92V6.32Zm-1 9.44-4.13 1.58V7.99L8 6.4v9.36Zm11.13.25L16 17.6V8.24l4.13-1.58v9.35Z" fill="currentColor"/></svg>',
        'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.8 3.5 10.7l1.27 1.57L6 11.32V20h5.25v-5.5h1.5V20H18v-8.68l1.23.95 1.27-1.57L12 3.8Z" fill="currentColor"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8 5 5.5v5.14c0 4.45 2.84 8.61 7 10.06 4.16-1.45 7-5.61 7-10.06V5.5l-7-2.7Zm0 2.13 5 1.93v3.78c0 3.45-2.12 6.83-5 8.18-2.88-1.35-5-4.73-5-8.18V6.86l5-1.93Zm-1.02 9.39-2.2-2.2-1.41 1.41 3.61 3.61 5.66-5.66-1.41-1.41-4.25 4.25Z" fill="currentColor"/></svg>',
        'chart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.25h16v1.5H2.75V4H4v15.25Zm4.25-2.5H6.5v-5h1.75v5Zm4.63 0h-1.75V8.5h1.75v8.25Zm4.62 0h-1.75V5.25h1.75v11.5Z" fill="currentColor"/></svg>',
        'pin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75a6.75 6.75 0 0 0-6.75 6.75c0 4.89 6.04 11.14 6.3 11.4a.63.63 0 0 0 .9 0c.26-.26 6.3-6.51 6.3-11.4A6.75 6.75 0 0 0 12 2.75Zm0 9.5a2.75 2.75 0 1 1 0-5.5 2.75 2.75 0 0 1 0 5.5Z" fill="currentColor"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.55 23c-8.02 0-14.55-6.53-14.55-14.55 0-.8.64-1.45 1.45-1.45h3.12c.69 0 1.28.48 1.42 1.16l.5 2.44c.11.53-.06 1.08-.46 1.45l-1.67 1.54a11.64 11.64 0 0 0 5.05 5.05l1.54-1.67c.37-.4.92-.57 1.45-.46l2.44.5c.68.14 1.16.73 1.16 1.42v3.12c0 .81-.65 1.45-1.45 1.45h-1.4Z" fill="currentColor"/></svg>',
        'document' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5h7.75L19 8.75V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Zm7 1.94V9h3.56L13 5.44ZM8 12h8v1.5H8V12Zm0 3.5h8V17H8v-1.5Z" fill="currentColor"/></svg>',
        'spark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 1.9 5.1L19 9l-5.1 1.9L12 16l-1.9-5.1L5 9l5.1-1.9L12 2Zm6.5 11 1.02 2.73L22.25 16l-2.73 1.02L18.5 19.75l-1.02-2.73L14.75 16l2.73-1.27L18.5 13Zm-13 1 1.02 2.73L9.25 18l-2.73 1.02L5.5 21.75l-1.02-2.73L1.75 18l2.73-1.27L5.5 14Z" fill="currentColor"/></svg>',
        'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.75 10V7.75a4.25 4.25 0 1 1 8.5 0V10H18a1 1 0 0 1 1 1v8.25a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V11a1 1 0 0 1 1-1h1.75Zm1.5 0h5.5V7.75a2.75 2.75 0 1 0-5.5 0V10Zm2.75 3a1.5 1.5 0 0 1 .75 2.8v1.95h-1.5V15.8A1.5 1.5 0 0 1 12 13Z" fill="currentColor"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12.25a4.13 4.13 0 1 1 0-8.25 4.13 4.13 0 0 1 0 8.25Zm0 1.75c4.13 0 7.5 2.62 7.5 5.84 0 .36-.29.66-.66.66H5.16a.66.66 0 0 1-.66-.66C4.5 16.62 7.87 14 12 14Z" fill="currentColor"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 4a6.5 6.5 0 0 1 5.17 10.44l4.44 4.45-1.06 1.06-4.45-4.44A6.5 6.5 0 1 1 10.5 4Zm0 1.5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z" fill="currentColor"/></svg>',
        'upload' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11.25 16.75h1.5V9.62l2.78 2.78 1.06-1.06L12 6.75l-4.59 4.59 1.06 1.06 2.78-2.78v7.13ZM5 19.25h14v1.5H5v-1.5Z" fill="currentColor"/></svg>',
        'message' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4.75h14A1.25 1.25 0 0 1 20.25 6v9A1.25 1.25 0 0 1 19 16.25H9.22l-4.01 3.27a.75.75 0 0 1-1.21-.58V6A1.25 1.25 0 0 1 5 4.75Zm1.5 3v1.5h11v-1.5h-11Zm0 3.5v1.5h7.25v-1.5H6.5Z" fill="currentColor"/></svg>',
    ];

    return $icons[$icon] ?? $icons['spark'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | Terra Invest &amp; Co.</title>
    <meta name="description" content="Explore Terra Invest services for land buying, selling, verification, insights, and direct seller contact.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/web icon.png">
    <link rel="stylesheet" href="css/services.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <div class="page-shell">
        <header class="header-wrapper">
            <nav class="floating-navbar" aria-label="Primary">
                <div class="nav-logo">
                    <img src="assets/logo_black.png" alt="Terra Invest Co.">
                    <span class="logo-text">Terra Invest Co.</span>
                </div>

                <div class="nav-links" id="nav-links">
                    <button type="button" class="close-btn" onclick="toggleMenu()" aria-label="Close menu">
                        <span>&times;</span>
                    </button>
                    <a href="index.php#footer" class="nav-item">About</a>
                    <a href="index.php#services-grid" class="nav-item">Features</a>
                    <a href="services.php" class="nav-item" aria-current="page">Services</a>
                    <a href="contact.php" class="nav-item">Contact Us</a>
                </div>

                <div class="nav-actions">
                <div id="auth-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="search.php" class="btn-login">Explore</a>
                        <a href="pages/subscription.php" class="btn-start">Add Property</a>
                    <?php else: ?>
                        <a href="#" class="btn-login" onclick="openAuthModal('login'); return false;">Login</a>
                        <a href="#" class="btn-start" onclick="openAuthModal('register'); return false;">Start investing</a>
                    <?php endif; ?>
                </div>
                    <button type="button" class="hamburger" onclick="toggleMenu()" aria-label="Open menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </nav>
        </header>

        <main>
            <section class="hero-section">
                <div class="hero-content">
                    <div class="eyebrow">Land Platform Services</div>
                    <h1>Our Services</h1>
                    <p>Buy, sell, and invest in land with confidence.</p>

                    <div class="hero-actions">
                        <?php if ($isLoggedIn): ?>
                            <a href="search.php" class="btn-primary">Explore Listings</a>
                            <a href="pages/subscription.php" class="btn-secondary accent">Add Property</a>
                        <?php else: ?>
                            <a href="#" class="btn-primary" onclick="openAuthModal('login', 'search.php'); return false;">Explore Listings</a>
                            <a href="#" class="btn-secondary accent" onclick="openAuthModal('login', 'pages/subscription.php'); return false;">Add Property</a>
                        <?php endif; ?>
                    </div>

                    <div class="hero-stats" aria-label="Platform highlights">
                        <div class="stat-card">
                            <strong>100+</strong>
                            <span>Active listings</span>
                        </div>
                        <div class="stat-card">
                            <strong>3-step</strong>
                            <span>Seller setup</span>
                        </div>
                        <div class="stat-card">
                            <strong>Verified</strong>
                            <span>Document-first flow</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section-block" id="services-grid">
                <div class="section-heading">
                    <div>
                        <h2>Services Built for Faster Decisions</h2>
                    </div>
                    <p>Everything is shaped to reduce uncertainty, show stronger proof, and move serious buyers and sellers closer to action.</p>
                </div>

                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <article class="service-card">
                            <div class="icon-chip">
                                <?php echo renderServiceIcon($service['icon']); ?>
                            </div>
                            <h3><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="service-tags">
                                <?php foreach ($service['tags'] as $tag): ?>
                                    <span><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="section-block">
                <div class="why-layout">
                    <div class="why-panel">
                        <div class="section-heading">
                            <div>
                                <h2>Why Choose Us</h2>
                            </div>
                            <p>A cleaner product flow for land discovery, verification, and direct seller communication.</p>
                        </div>

                        <div class="trust-grid">
                            <?php foreach ($trustItems as $item): ?>
                                <article class="trust-card">
                                    <div class="icon-chip <?php echo $item['tone'] === 'success' ? 'success' : ''; ?>">
                                        <?php echo renderServiceIcon($item['icon']); ?>
                                    </div>
                                    <h3><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <aside class="highlight-panel">
                        <h3>Built for trust before the first call.</h3>
                        <p>Show better proof, reduce back-and-forth, and help serious buyers act sooner.</p>

                        <div class="mini-metrics">
                            <div>
                                <strong>Verified uploads</strong>
                                <span>Structured image, video, and document support</span>
                            </div>
                            <div>
                                <strong>Direct contact flow</strong>
                                <span>No unnecessary layers between enquiry and action</span>
                            </div>
                            <div>
                                <strong>Mobile-ready</strong>
                                <span>Browse and manage listings cleanly across screen sizes</span>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="section-block">
                <div class="section-heading">
                    <div>
                        <h2>How It Works</h2>
                    </div>
                    <p>A straightforward path from account setup to active land conversations.</p>
                </div>

                <div class="steps-grid">
                    <?php foreach ($steps as $index => $step): ?>
                        <article class="step-card">
                            <span class="step-number"><?php echo $index + 1; ?></span>
                            <div class="icon-chip">
                                <?php echo renderServiceIcon($step['icon']); ?>
                            </div>
                            <h3><?php echo htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars($step['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="section-block">
                <div class="cta-panel">
                    <div>
                        <h2>Ready to get started?</h2>
                        <p>Explore active land opportunities or publish your property with stronger proof.</p>
                    </div>

                    <div class="cta-actions">
                        <?php if ($isLoggedIn): ?>
                            <a href="search.php" class="btn-primary">Explore Listings</a>
                            <a href="pages/subscription.php" class="btn-secondary">Add Listing</a>
                        <?php else: ?>
                            <a href="#" class="btn-primary" onclick="openAuthModal('login', 'search.php'); return false;">Explore Listings</a>
                            <a href="#" class="btn-secondary" onclick="openAuthModal('login', 'pages/subscription.php'); return false;">Add Listing</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>

        <footer class="page-footer">
            <p>Terra Invest &amp; Co. helps buyers and sellers move with better clarity.</p>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="services.php">Services</a>
                <a href="contact.php">Contact</a>
            </div>
        </footer>
    </div>

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
        window.authPageFlash = <?php echo json_encode(getFlash(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

        function toggleMenu() {
            const navLinks = document.getElementById('nav-links');
            if (!navLinks) {
                return;
            }
            navLinks.classList.toggle('mobile-open');
            document.body.classList.toggle('menu-open');
        }

        // Override updateNavbar from script.js to preserve services page navbar
        window.updateNavbar = function() {};

        // Enhanced openAuthModal that supports redirect URL
        const originalOpenAuthModal = window.openAuthModal;
        window.openAuthModal = function(tab, redirectUrl) {
            const modal = document.getElementById('auth-modal');
            if (!modal) {
                return;
            }
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            switchTab(tab);

            // Store redirect URL on both forms so handleAuthSubmit can pick it up
            if (redirectUrl) {
                document.querySelectorAll('.auth-form').forEach(function(form) {
                    form.dataset.redirect = redirectUrl;
                });
            }
        };
    </script>
</body>
</html>
