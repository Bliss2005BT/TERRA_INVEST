<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$activeSubscription = getActiveSubscription();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? esc($pageTitle) . ' | ' . APP_NAME : APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/logo_black.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/search.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" defer></script>
</head>
<body class="app-body">
    <header class="search-nav app-nav">
        <div class="left">
            <a href="../search.php" class="brand-link">
                <img src="../assets/logo_black.png" alt="Terra Invest logo" class="logo">
                <span>Terra Invest Co.</span>
            </a>
        </div>
        <div class="nav-actions">
            <a href="../search.php" class="secondary-nav-link">Search</a>
            <a href="../pages/subscription.php" class="listing-btn">
                <?php echo $activeSubscription ? 'Add a Listing' : 'Choose Plan'; ?>
            </a>
            <div class="user-menu" data-user-menu>
                <button type="button" class="user-menu-toggle" data-user-menu-toggle aria-expanded="false">
                    <span class="right">
                        <i class="fa-solid fa-user"></i>
                        <span><?php echo esc(getCurrentUserName()); ?></span>
                    </span>
                    <i class="fa-solid fa-chevron-down user-menu-chevron" aria-hidden="true"></i>
                </button>
                <div class="user-dropdown" data-user-dropdown>
                    <a href="../my-listings.php" class="user-dropdown-link">View My Listings</a>
                    <a href="../php/logout.php" class="user-dropdown-link user-dropdown-link-danger">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="flash-message flash-<?php echo esc($flash['type']); ?>">
            <?php echo esc($flash['message']); ?>
        </div>
    <?php endif; ?>
