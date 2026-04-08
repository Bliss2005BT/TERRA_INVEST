document.addEventListener('DOMContentLoaded', () => {
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
