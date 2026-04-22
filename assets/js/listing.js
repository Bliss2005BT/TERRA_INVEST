document.addEventListener('DOMContentLoaded', () => {
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
  const galleryThumbs = document.querySelectorAll('[data-gallery-thumb]');

  if (activePropertyImage && galleryThumbs.length) {
    galleryThumbs.forEach((thumb) => {
      thumb.addEventListener('click', () => {
        const fullImage = thumb.dataset.fullImage;
        if (!fullImage) {
          return;
        }

        activePropertyImage.src = fullImage;
        galleryThumbs.forEach((item) => item.classList.remove('is-active'));
        thumb.classList.add('is-active');
      });
    });
  }

  const imageInput = document.getElementById('images');
  const previewGrid = document.getElementById('image-preview-grid');

  if (!imageInput || !previewGrid) {
    return;
  }

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
});
