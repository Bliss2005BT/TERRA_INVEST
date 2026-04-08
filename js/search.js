document.addEventListener('DOMContentLoaded', () => {
  const username = document.getElementById('search-username');
  const listingLink = document.getElementById('listing-link') || document.getElementById('listing-link-copy');
  const resultsContainer = document.getElementById('search-results');
  const resultsSummary = document.getElementById('results-summary');
  const keywordInput = document.getElementById('keyword');
  const locationInput = document.getElementById('location');
  const minPriceInput = document.getElementById('min-price');
  const maxPriceInput = document.getElementById('max-price');
  const minAreaInput = document.getElementById('min-area');
  const maxAreaInput = document.getElementById('max-area');
  const searchButton = document.getElementById('apply-search');
  const propertyTypeButtons = document.querySelectorAll('#property-type-bar button');

  let selectedPropertyType = 'All';

  fetch('php/session_data.php')
    .then((response) => response.json())
    .then((data) => {
      if (!data.isLoggedIn) {
        window.location.href = 'index.php?error=' + encodeURIComponent('Please log in to continue');
        return;
      }

      if (username && data.userName) {
        username.textContent = data.userName;
      }

      if (listingLink) {
        listingLink.href = data.subscription ? 'pages/add-listing.php' : 'pages/subscription.php';
        listingLink.textContent = data.subscription ? 'Add a Listing' : 'Choose Plan';
      }

      loadListings();
    })
    .catch(() => {
      window.location.href = 'index.php?error=' + encodeURIComponent('Unable to verify login session');
    });

  propertyTypeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      propertyTypeButtons.forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      selectedPropertyType = button.dataset.type || 'All';
      loadListings();
    });
  });

  if (searchButton) {
    searchButton.addEventListener('click', loadListings);
  }

  [keywordInput, locationInput, minPriceInput, maxPriceInput, minAreaInput, maxAreaInput]
    .filter(Boolean)
    .forEach((input) => {
      input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          loadListings();
        }
      });
    });

  function loadListings() {
    if (!resultsContainer || !resultsSummary) {
      return;
    }

    resultsSummary.textContent = 'Loading properties...';
    resultsContainer.innerHTML = '';

    const params = new URLSearchParams({
      keyword: keywordInput?.value.trim() || '',
      location: locationInput?.value.trim() || '',
      min_price: minPriceInput?.value.trim() || '',
      max_price: maxPriceInput?.value.trim() || '',
      min_area: minAreaInput?.value.trim() || '',
      max_area: maxAreaInput?.value.trim() || '',
      property_type: selectedPropertyType,
    });

    fetch(`pages/search-handler.php?${params.toString()}`)
      .then((response) => response.json())
      .then((data) => {
        if (!data.success || !Array.isArray(data.listings)) {
          throw new Error('Invalid response');
        }

        resultsSummary.textContent = `${data.count} listing(s) found`;

        if (!data.listings.length) {
          resultsContainer.innerHTML = '<div class="empty-results">No listings matched your filters.</div>';
          return;
        }

        resultsContainer.innerHTML = data.listings.map((listing) => {
          const imageTag = listing.image
            ? `<img src="${listing.image}" alt="${escapeHtml(listing.title)}">`
            : '<div class="empty-thumb">No image uploaded</div>';

          return `
            <article class="listing-card">
              ${imageTag}
              <div class="listing-content">
                <div class="listing-pill">${escapeHtml(listing.subscription_type)}</div>
                <h3>${escapeHtml(listing.title)}</h3>
                <p class="listing-location">${escapeHtml(listing.location)}</p>
                <div class="listing-meta">
                  <span>${escapeHtml(listing.property_type)}</span>
                  <span>${formatCurrency(listing.price)}</span>
                  <span>${Number(listing.area).toLocaleString()} sq ft</span>
                </div>
                <p class="listing-text">${escapeHtml((listing.description || '').slice(0, 130))}${listing.description && listing.description.length > 130 ? '...' : ''}</p>
                <a class="listing-view-btn" href="pages/view-listing.php?id=${listing.id}">View Listing</a>
              </div>
            </article>
          `;
        }).join('');
      })
      .catch(() => {
        resultsSummary.textContent = 'Unable to load listings right now.';
        resultsContainer.innerHTML = '<div class="empty-results">Please try again in a moment.</div>';
      });
  }

  function formatCurrency(value) {
    return `Rs. ${Number(value).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
});
