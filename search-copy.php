<?php
declare(strict_types=1);
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
  <script src="js/search.js" defer></script>
</head>
<body>
  <header class="search-nav">
    <div class="left">
      <img src="assets/logo_black.png" alt="Terra Invest logo" class="logo">
      <span>Terra Invest Co.</span>
    </div>

    <div class="nav-actions">
      <a href="pages/subscription.php" class="listing-btn" id="listing-link-copy">Add a Listing</a>
      <div class="right">
        <i class="fa-solid fa-user"></i>
        <span id="search-username">Username</span>
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

      <div class="category-bar" id="property-type-bar">
        <button class="active" data-type="All">All</button>
        <button data-type="Residential">Residential</button>
        <button data-type="Commercial">Commercial</button>
        <button data-type="Agricultural">Agricultural</button>
        <button data-type="Industrial">Industrial</button>
        <button data-type="Plots">Plots</button>
      </div>

      <div class="search-box">
        <input type="text" id="keyword" placeholder="Search by location, title, or description...">
        <i class="fa fa-search"></i>
      </div>

      <div class="search-filters">
        <input type="text" id="location" placeholder="Location">
        <input type="number" id="min-price" placeholder="Min Price">
        <input type="number" id="max-price" placeholder="Max Price">
        <input type="number" id="min-area" placeholder="Min Area">
        <input type="number" id="max-area" placeholder="Max Area">
        <button class="filter-action" id="apply-search">Search</button>
      </div>
    </div>
  </section>

  <section class="results-section">
    <div class="results-header">
      <div>
        <h2>Available Listings</h2>
        <p id="results-summary">Loading properties...</p>
      </div>
    </div>
    <div id="search-results" class="results-grid"></div>
  </section>
</body>
</html>
