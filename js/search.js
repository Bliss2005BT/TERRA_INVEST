document.addEventListener('DOMContentLoaded', () => {
  const username = document.getElementById('search-username');
  const listingLink = document.getElementById('listing-link') || document.getElementById('listing-link-copy');
  const resultsContainer = document.getElementById('search-results');
  const resultsSummary = document.getElementById('results-summary');
  const resultsFeedback = document.getElementById('results-feedback');
  const resultsLoader = document.getElementById('results-loader');
  const keywordInput = document.getElementById('keyword');
  const locationInput = document.getElementById('location');
  const minPriceInput = document.getElementById('min-price');
  const maxPriceInput = document.getElementById('max-price');
  const minAreaInput = document.getElementById('min-area');
  const maxAreaInput = document.getElementById('max-area');
  const searchButton = document.getElementById('apply-search');
  const propertyTypeButtons = document.querySelectorAll('#property-type-bar button');

  let selectedPropertyType = 'All';
  let activeController = null;
  let requestSequence = 0;

  fetch('php/session_data.php', {
    headers: {
      Accept: 'application/json',
    },
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Session request failed');
      }

      return response.json();
    })
    .then((data) => {
      if (!data.isLoggedIn) {
        window.location.href = 'index.php?error=' + encodeURIComponent('Please log in to continue');
        return;
      }

      if (username && data.userName) {
        username.textContent = data.userName;
      }

      if (listingLink) {
        listingLink.href = 'pages/subscription.php';
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

    const validationError = validateFilters();
    if (validationError) {
      setFeedback(validationError, 'error');
      resultsSummary.textContent = 'Fix the filters and try again.';
      resultsContainer.innerHTML = '';
      hideLoader();
      return;
    }

    if (activeController) {
      activeController.abort();
    }

    activeController = new AbortController();
    const currentRequest = ++requestSequence;
    const timeoutId = window.setTimeout(() => {
      activeController?.abort();
    }, 12000);

    setFeedback('', '');
    showLoader();
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

    fetch(`search_api.php?${params.toString()}`, {
      signal: activeController.signal,
      headers: {
        Accept: 'application/json',
      },
    })
      .then(async (response) => {
        const data = await response.json().catch(() => null);

        if (!response.ok) {
          throw new Error(data?.message || 'Search request failed');
        }

        return data;
      })
      .then((data) => {
        if (currentRequest !== requestSequence) {
          return;
        }

        if (!data.success || !Array.isArray(data.listings)) {
          throw new Error(data.message || 'Invalid response');
        }

        resultsSummary.textContent = `${data.count} listing(s) found`;
        hideLoader();

        if (!data.listings.length) {
          setFeedback('No results found for the current filters.', 'empty');
          resultsContainer.innerHTML = '<div class="empty-results">No listings matched your filters.</div>';
          return;
        }

        setFeedback('', '');
        resultsContainer.innerHTML = data.listings.map((listing) => {
          const imageTag = listing.image
            ? `<img src="${listing.image}" alt="${escapeHtml(listing.title)}">`
            : '<div class="empty-thumb">No image uploaded</div>';
          const badgeText = listing.subscription_type === 'featured'
            ? '&#11088; Featured'
            : escapeHtml(listing.badge_label || listing.subscription_name || listing.subscription_type);
          const cardClass = `listing-card plan-${escapeHtml(listing.subscription_type || 'basic')}`;

          return `
            <article class="${cardClass}">
              ${imageTag}
              <div class="listing-content">
                <div class="listing-pill badge-${escapeHtml(listing.subscription_type || 'basic')}">${badgeText}</div>
                <h3>${escapeHtml(listing.title)}</h3>
                <p class="listing-location">${escapeHtml(listing.location)}</p>
                <div class="listing-meta">
                  <span>${escapeHtml(listing.property_type)}</span>
                  <span>${formatCurrency(listing.price)}</span>
                  <span>${Number(listing.area).toLocaleString()} sq ft</span>
                </div>
                <p class="listing-text">${escapeHtml((listing.description || '').slice(0, 130))}${listing.description && listing.description.length > 130 ? '...' : ''}</p>
                <p class="listing-expiry">Valid until ${formatDate(listing.listing_expiry_date)}</p>
                <a class="listing-view-btn" href="pages/view-listing.php?id=${listing.id}">View Listing</a>
              </div>
            </article>
          `;
        }).join('');
      })
      .catch((error) => {
        if (error.name === 'AbortError') {
          if (currentRequest !== requestSequence) {
            return;
          }

          resultsSummary.textContent = 'Refreshing search...';
          return;
        }

        hideLoader();
        resultsSummary.textContent = 'Unable to load listings right now.';
        setFeedback(error.message || 'Please try again in a moment.', 'error');
        resultsContainer.innerHTML = '<div class="empty-results">Please try again in a moment.</div>';
      })
      .finally(() => {
        window.clearTimeout(timeoutId);
        if (currentRequest === requestSequence) {
          hideLoader();
        }
      });
  }

  function validateFilters() {
    const minPrice = minPriceInput?.value.trim() || '';
    const maxPrice = maxPriceInput?.value.trim() || '';
    const minArea = minAreaInput?.value.trim() || '';
    const maxArea = maxAreaInput?.value.trim() || '';

    if (minPrice && maxPrice && Number(minPrice) > Number(maxPrice)) {
      return 'Minimum price cannot be greater than maximum price.';
    }

    if (minArea && maxArea && Number(minArea) > Number(maxArea)) {
      return 'Minimum area cannot be greater than maximum area.';
    }

    return '';
  }

  function setFeedback(message, type) {
    if (!resultsFeedback) {
      return;
    }

    resultsFeedback.textContent = message;
    resultsFeedback.className = 'results-feedback';

    if (message && type) {
      resultsFeedback.classList.add(`results-feedback-${type}`);
    }
  }

  function showLoader() {
    if (resultsLoader) {
      resultsLoader.hidden = false;
    }
  }

  function hideLoader() {
    if (resultsLoader) {
      resultsLoader.hidden = true;
    }
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

  function formatDate(value) {
    if (!value) {
      return 'N/A';
    }

    const parsed = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) {
      return value;
    }

    return parsed.toLocaleDateString('en-IN', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  }
});
