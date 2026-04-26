document.addEventListener('DOMContentLoaded', () => {
  const MAX_DOCUMENT_SIZE = 5 * 1024 * 1024;
  const ALLOWED_DOCUMENT_MIME = ['application/pdf', 'image/jpeg', 'image/png'];
  const ALLOWED_DOCUMENT_EXT = ['pdf', 'jpg', 'jpeg', 'png'];

  const formatBytes = (size) => `${(size / (1024 * 1024)).toFixed(2)} MB`;

  const showDocumentError = (message) => {
    const banner = document.getElementById('document-error-banner');
    if (!banner) {
      return;
    }
    banner.textContent = message;
    banner.hidden = false;
  };

  const clearDocumentError = () => {
    const banner = document.getElementById('document-error-banner');
    if (!banner) {
      return;
    }
    banner.textContent = '';
    banner.hidden = true;
  };

  const renderDocumentMeta = (input) => {
    const meta = document.querySelector(`[data-document-meta="${input.id}"]`);
    if (!meta) {
      return;
    }

    meta.innerHTML = '';
    const file = input.files && input.files[0] ? input.files[0] : null;
    if (!file) {
      return;
    }

    const card = document.createElement('div');
    card.className = 'document-meta-card';

    const name = document.createElement('span');
    name.className = 'document-meta-name';
    name.textContent = file.name;

    const size = document.createElement('span');
    size.className = 'document-meta-size';
    size.textContent = formatBytes(file.size);

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'document-remove-btn';
    removeButton.textContent = 'Remove';
    removeButton.addEventListener('click', () => {
      input.value = '';
      meta.innerHTML = '';
      clearDocumentError();
    });

    card.appendChild(name);
    card.appendChild(size);
    card.appendChild(removeButton);
    meta.appendChild(card);
  };

  const validateDocumentInput = (input) => {
    clearDocumentError();
    const file = input.files && input.files[0] ? input.files[0] : null;
    if (!file) {
      renderDocumentMeta(input);
      return true;
    }

    const extension = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
    if (file.size > MAX_DOCUMENT_SIZE) {
      showDocumentError('File size must be less than 5MB');
      input.value = '';
      renderDocumentMeta(input);
      return false;
    }

    if (!ALLOWED_DOCUMENT_MIME.includes(file.type) || !ALLOWED_DOCUMENT_EXT.includes(extension)) {
      showDocumentError('Only PDF, JPG, PNG files are allowed');
      input.value = '';
      renderDocumentMeta(input);
      return false;
    }

    renderDocumentMeta(input);
    return true;
  };

  document.querySelectorAll('[data-user-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-user-menu-toggle]');
    if (!toggle) {
      return;
    }

    toggle.addEventListener('click', (event) => {
      event.stopPropagation();

      document.querySelectorAll('[data-user-menu].is-open').forEach((openMenu) => {
        if (openMenu !== menu) {
          openMenu.classList.remove('is-open');
          const openToggle = openMenu.querySelector('[data-user-menu-toggle]');
          if (openToggle) {
            openToggle.setAttribute('aria-expanded', 'false');
          }
        }
      });

      const isOpen = menu.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });

  document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-user-menu].is-open').forEach((menu) => {
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

  const activePropertyImage = document.getElementById('active-property-image');
  const galleryThumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));
  const prevButton = document.getElementById('gallery-prev');
  const nextButton = document.getElementById('gallery-next');
  let currentImageIndex = 0;

  const isValidImageSrc = (src) => {
    return typeof src === 'string' && src.trim() !== '' && !src.includes('undefined') && !src.includes('null');
  };

  const syncGallery = () => {
    if (!activePropertyImage || !galleryThumbs.length) {
      return;
    }

    const current = galleryThumbs[currentImageIndex];
    const thumbImage = current ? current.querySelector('img') : null;
    const fullImage = current
      ? (current.dataset.fullImage || (thumbImage ? thumbImage.getAttribute('src') : '') || '')
      : '';
    if (!isValidImageSrc(fullImage)) {
      console.error('Invalid image source:', fullImage);
      return;
    }

    activePropertyImage.src = fullImage;
    galleryThumbs.forEach((thumb, index) => {
      thumb.classList.toggle('is-active', index === currentImageIndex);
    });

  };

  const moveGallery = (step) => {
    if (!galleryThumbs.length) {
      return;
    }

    currentImageIndex = (currentImageIndex + step + galleryThumbs.length) % galleryThumbs.length;
    syncGallery();
  };

  if (activePropertyImage && galleryThumbs.length) {
    if (galleryThumbs.length <= 1) {
      if (prevButton) {
        prevButton.hidden = true;
      }
      if (nextButton) {
        nextButton.hidden = true;
      }
    }

    galleryThumbs.forEach((thumb, index) => {
      thumb.addEventListener('click', (event) => {
        event.preventDefault();
        currentImageIndex = index;
        syncGallery();
      });
    });

    if (prevButton) {
      prevButton.addEventListener('click', (event) => {
        event.preventDefault();
        moveGallery(-1);
      });
    }

    if (nextButton) {
      nextButton.addEventListener('click', (event) => {
        event.preventDefault();
        moveGallery(1);
      });
    }

    syncGallery();
  }

  document.querySelectorAll('.property-video-player[data-auto-aspect]').forEach((video) => {
    video.addEventListener('loadedmetadata', () => {
      const frame = video.closest('.property-video-frame');
      if (!frame) {
        return;
      }

      const isPortrait = video.videoWidth > 0 && video.videoHeight > 0 && video.videoHeight > video.videoWidth;
      frame.classList.toggle('is-portrait', isPortrait);
      frame.classList.toggle('is-landscape', !isPortrait);
    });
  });

  const imageInput = document.getElementById('images');
  const previewGrid = document.getElementById('image-preview-grid');
  if (imageInput && previewGrid) {
    imageInput.addEventListener('change', () => {
      previewGrid.innerHTML = '';

      if (!imageInput.files.length) {
        previewGrid.innerHTML = '<div class="preview-item">Image preview appears here</div>';
        return;
      }

      Array.from(imageInput.files).forEach((file) => {
        const extension = file.name.split('.').pop().toLowerCase();
        if (!['jpg', 'jpeg', 'png'].includes(extension)) {
          return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
          const wrapper = document.createElement('div');
          wrapper.className = 'preview-item';
          wrapper.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
          previewGrid.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
      });
    });
  }

  const documentInputs = document.querySelectorAll('[data-document-input]');
  const form = document.querySelector('.listing-form');
  const submitButton = document.getElementById('publish-listing-btn');
  const progressWrap = document.getElementById('document-upload-progress');
  const progressBar = document.getElementById('document-upload-progress-bar');

  documentInputs.forEach((input) => {
    input.addEventListener('change', () => {
      validateDocumentInput(input);
    });
  });

  if (form) {
    form.addEventListener('submit', (event) => {
      clearDocumentError();
      let hasAtLeastOneDocument = false;
      let isValid = true;

      documentInputs.forEach((input) => {
        if (input.files && input.files.length > 0) {
          hasAtLeastOneDocument = true;
        }

        if (!validateDocumentInput(input)) {
          isValid = false;
        }
      });

      if (!hasAtLeastOneDocument) {
        showDocumentError('Please upload at least one document');
        event.preventDefault();
        return;
      }

      if (!isValid) {
        event.preventDefault();
        return;
      }

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Uploading...';
      }

      if (progressWrap && progressBar) {
        progressWrap.hidden = false;
        let progressValue = 0;
        progressBar.style.width = '0%';
        const timer = window.setInterval(() => {
          progressValue = Math.min(progressValue + 8, 92);
          progressBar.style.width = `${progressValue}%`;
          if (progressValue >= 92) {
            window.clearInterval(timer);
          }
        }, 120);
      }
    });
  }
});
