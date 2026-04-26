<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin('index.php');

$userId = (int) getCurrentUserId();
$activeSubscription = getActiveSubscription();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_listing') {
    $listingId = (int) ($_POST['listing_id'] ?? 0);

    if ($listingId > 0 && deleteListingForUser($listingId, $userId)) {
        setFlash('success', 'Listing deleted successfully.');
    } else {
        setFlash('error', 'Unable to delete that listing.');
    }

    redirectTo('my-listings.php');
}

$flash = getFlash();
$listings = getListingsForUser($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Listings | <?php echo esc(APP_NAME); ?></title>
  <link rel="icon" type="image/png" href="assets/logo_black.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Inter:wght@300;400;500;600&family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/search.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="app-body my-listings-body">
  <header class="search-nav app-nav">
    <div class="left">
      <a href="search.php" class="brand-link">
        <img src="assets/logo_black.png" alt="Terra Invest logo" class="logo">
        <span>Terra Invest Co.</span>
      </a>
    </div>
    <div class="nav-actions">
      <a href="search.php" class="secondary-nav-link">Search</a>
      <a href="pages/subscription.php" class="listing-btn">
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
          <a href="my-listings.php" class="user-dropdown-link">View My Listings</a>
          <a href="php/logout.php" class="user-dropdown-link user-dropdown-link-danger">Logout</a>
        </div>
      </div>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="flash-message flash-<?php echo esc($flash['type']); ?>">
      <?php echo esc($flash['message']); ?>
    </div>
  <?php endif; ?>

  <main class="page-shell">
    <section class="page-panel">
      <div class="page-heading">
        <div>
          <h1>My Listings</h1>
          <p class="page-subtext"><?php echo esc((string) count($listings)); ?> listing(s) published from your account.</p>
        </div>
        <a href="pages/add-listing.php" class="btn-dark">Add New Listing</a>
      </div>

      <?php if (!$listings): ?>
        <div class="empty-state">You have not added any listings yet.</div>
      <?php else: ?>
        <div class="results-grid account-results-grid">
          <?php foreach ($listings as $listing): ?>
            <article class="listing-card" data-href="pages/view-listing.php?id=<?php echo (int) $listing['id']; ?>" tabindex="0" role="link">
              <?php if ($listing['image'] !== ''): ?>
                <img src="<?php echo esc($listing['image']); ?>" alt="<?php echo esc($listing['title']); ?>">
              <?php else: ?>
                <div class="empty-thumb">No image uploaded</div>
              <?php endif; ?>

              <div class="listing-content">
                <p class="listing-price"><?php echo esc(formatListingPrice((float) $listing['price'])); ?></p>
                <p class="listing-location"><?php echo esc(formatListingLocation((string) $listing['location'])); ?></p>
                <div class="listing-meta">
                  <span class="listing-area"><?php echo esc(formatListingArea((float) $listing['area'])); ?></span>
                  <span class="listing-type"><?php echo esc((string) $listing['property_type']); ?></span>
                </div>
                <div class="listing-actions">
                  <a class="listing-view-btn" href="pages/view-listing.php?id=<?php echo (int) $listing['id']; ?>">View Details</a>
                  <form method="post" class="inline-action-form" onsubmit="return confirm('Delete this listing?');">
                    <input type="hidden" name="action" value="delete_listing">
                    <input type="hidden" name="listing_id" value="<?php echo (int) $listing['id']; ?>">
                    <button type="submit" class="listing-action-icon" aria-label="Delete listing" title="Delete listing">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <script>
    document.querySelectorAll('[data-user-menu]').forEach((menu) => {
      const toggle = menu.querySelector('[data-user-menu-toggle]');
      const dropdown = menu.querySelector('[data-user-dropdown]');

      if (!toggle || !dropdown) {
        return;
      }

      toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    });

    document.addEventListener('click', (event) => {
      document.querySelectorAll('[data-user-menu]').forEach((menu) => {
        if (menu.contains(event.target)) {
          return;
        }

        menu.classList.remove('is-open');
        const toggle = menu.querySelector('[data-user-menu-toggle]');
        if (toggle) {
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    });

    document.querySelectorAll('.listing-card').forEach((card) => {
      const href = card.dataset.href;
      if (!href) {
        return;
      }

      card.addEventListener('click', (event) => {
        if (event.target.closest('a, button, form')) {
          return;
        }

        window.location.href = href;
      });

      card.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          window.location.href = href;
        }
      });
    });
  </script>
</body>
</html>
