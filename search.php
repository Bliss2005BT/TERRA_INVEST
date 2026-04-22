<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin('index.php');

$keyword = normalizeText($_GET['keyword'] ?? '');
$location = normalizeText($_GET['location'] ?? '');
$minPrice = normalizeText($_GET['min_price'] ?? '');
$maxPrice = normalizeText($_GET['max_price'] ?? '');
$minArea = normalizeText($_GET['min_area'] ?? '');
$maxArea = normalizeText($_GET['max_area'] ?? '');
$propertyType = normalizeText($_GET['property_type'] ?? 'All');
$allowedPropertyTypes = ['All', 'Residential', 'Commercial', 'Agricultural', 'Industrial', 'Plots'];

if (!in_array($propertyType, $allowedPropertyTypes, true)) {
    $propertyType = 'All';
}

$sql = 'SELECT id, title, price, area, location, property_type, description, images, subscription_type, created_at
        FROM listings
        WHERE ' . getListingActiveSql('created_at', 'subscription_type');
$types = '';
$params = [];

if ($keyword !== '') {
    $sql .= ' AND (title LIKE ? OR location LIKE ? OR description LIKE ?)';
    $types .= 'sss';
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
}

if ($location !== '') {
    $sql .= ' AND location LIKE ?';
    $types .= 's';
    $params[] = '%' . $location . '%';
}

if ($propertyType !== 'All') {
    $sql .= ' AND property_type = ?';
    $types .= 's';
    $params[] = $propertyType;
}

if ($minPrice !== '' && is_numeric($minPrice)) {
    $sql .= ' AND price >= ?';
    $types .= 'd';
    $params[] = (float) $minPrice;
}

if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $sql .= ' AND price <= ?';
    $types .= 'd';
    $params[] = (float) $maxPrice;
}

if ($minArea !== '' && is_numeric($minArea)) {
    $sql .= ' AND area >= ?';
    $types .= 'd';
    $params[] = (float) $minArea;
}

if ($maxArea !== '' && is_numeric($maxArea)) {
    $sql .= ' AND area <= ?';
    $types .= 'd';
    $params[] = (float) $maxArea;
}

$sql .= " ORDER BY FIELD(LOWER(subscription_type), 'featured', 'premium', 'basic') ASC, created_at DESC";

$conn = getDBConnection();
$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$listings = [];

while ($row = $result->fetch_assoc()) {
    $images = json_decode($row['images'] ?? '[]', true);
    if (!is_array($images)) {
        $images = [];
    }

    $row['subscription_type'] = normalizePlanType($row['subscription_type'] ?? 'basic');
    $row['badge_label'] = getPlanBadgeLabel($row['subscription_type']);
    $row['listing_expiry_date'] = getListingExpiryDate($row['created_at'], $row['subscription_type']);
    $row['image'] = $images[0] ?? '';
    $listings[] = $row;
}

$stmt->close();
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Terra Invest Search</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="css/search.css">
</head>
<body>
  <header class="search-nav">
    <div class="left">
      <img src="assets/logo_black.png" alt="Terra Invest logo" class="logo">
      <span>Terra Invest Co.</span>
    </div>

    <div class="nav-actions">
      <a href="pages/subscription.php" class="listing-btn" id="listing-link">Add a Listing</a>
      <div class="user-menu" data-user-menu>
        <button type="button" class="user-menu-toggle" data-user-menu-toggle aria-expanded="false">
          <span class="right">
            <i class="fa-solid fa-user"></i>
            <span id="search-username"><?php echo esc(getCurrentUserName()); ?></span>
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

  <section class="dashboard">
    <video autoplay muted loop playsinline class="bg-video">
      <source src="assets/searchbar video.mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <div class="main-box">
      <h1>Search Lands in Mumbai</h1>
      <p class="desc">
        Discover verified land listings with clarity and confidence.
      </p>

      <form method="get">
        <div class="category-bar" id="property-type-bar">
          <?php foreach ($allowedPropertyTypes as $type): ?>
            <button
              type="submit"
              name="property_type"
              value="<?php echo esc($type); ?>"
              class="<?php echo $propertyType === $type ? 'active' : ''; ?>"
            >
              <?php echo esc($type); ?>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="search-box">
          <input
            type="text"
            id="keyword"
            name="keyword"
            value="<?php echo esc($keyword); ?>"
            placeholder="Search by location, title, or description..."
          >
          <i class="fa fa-search"></i>
        </div>

        <div class="search-filters">
          <input type="text" id="location" name="location" value="<?php echo esc($location); ?>" placeholder="Location">
          <input type="number" id="min-price" name="min_price" value="<?php echo esc($minPrice); ?>" placeholder="Min Price">
          <input type="number" id="max-price" name="max_price" value="<?php echo esc($maxPrice); ?>" placeholder="Max Price">
          <input type="number" id="min-area" name="min_area" value="<?php echo esc($minArea); ?>" placeholder="Min Area">
          <input type="number" id="max-area" name="max_area" value="<?php echo esc($maxArea); ?>" placeholder="Max Area">
          <button class="filter-action" id="apply-search" type="submit">Search</button>
        </div>
      </form>
    </div>
  </section>

  <section class="results-section">
    <div class="results-header">
      <div>
        <h2>Available Listings</h2>
        <p id="results-summary"><?php echo esc((string) count($listings)); ?> listing(s) found</p>
      </div>
    </div>
    <div id="search-results" class="results-grid">
      <?php if (!$listings): ?>
        <div class="empty-results">No listings found.</div>
      <?php else: ?>
        <?php foreach ($listings as $listing): ?>
          <article
            class="listing-card"
            data-href="pages/view-listing.php?id=<?php echo (int) $listing['id']; ?>"
            tabindex="0"
            role="link"
            aria-label="View listing in <?php echo esc(formatListingLocation($listing['location'])); ?>"
          >
            <?php if ($listing['image'] !== ''): ?>
              <img src="<?php echo esc($listing['image']); ?>" alt="<?php echo esc($listing['title']); ?>">
            <?php else: ?>
              <div class="empty-thumb">No image uploaded</div>
            <?php endif; ?>

            <div class="listing-content">
              <p class="listing-price"><?php echo esc(formatListingPrice((float) $listing['price'])); ?></p>
              <p class="listing-location"><?php echo esc(formatListingLocation($listing['location'])); ?></p>
              <div class="listing-meta">
                <span class="listing-area"><?php echo esc(formatListingArea((float) $listing['area'])); ?></span>
                <span class="listing-type"><?php echo esc($listing['property_type']); ?></span>
              </div>
              <?php if (trim((string) $listing['description']) !== ''): ?>
                <p class="listing-text"><?php echo esc(mb_strimwidth(trim((string) $listing['description']), 0, 58, '...')); ?></p>
              <?php endif; ?>
              <a class="listing-view-btn" href="pages/view-listing.php?id=<?php echo (int) $listing['id']; ?>">View Details</a>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
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
        if (event.target.closest('a')) {
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
