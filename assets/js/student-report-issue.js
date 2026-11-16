// assets/js/student-report-issue.js

document.addEventListener('DOMContentLoaded', function () {
    
    // Character counter.
    const descriptionInput = document.getElementById('description');
    const characterCount = document.getElementById('characterCount');

    if (descriptionInput && characterCount) {
        descriptionInput.addEventListener('input', function () {
            characterCount.textContent = this.value.length;
        });
    }

    // Optional photo preview.
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];

            if (!file) {
                photoPreview.src = '';
                photoPreview.style.display = 'none';
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                photoPreview.src = '';
                photoPreview.style.display = 'none';
                return;
            }

            const imageUrl = URL.createObjectURL(file);
            photoPreview.src = imageUrl;
            photoPreview.style.display = 'block';

            photoPreview.onload = function () {
                URL.revokeObjectURL(imageUrl);
            };
        });
    }
});