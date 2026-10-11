document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-image-form]').forEach(form => {
        const input = form.querySelector('[data-image-input]');
        const previews = form.querySelector('[data-image-previews]');
        const countLabel = form.querySelector('[data-image-count]');
        if (!input || !previews || !countLabel) return;

        let selectedFiles = Array.from(input.files || []);
        let invalidSelection = false;
        let objectUrls = [];
        const updateCount = () => {
            const remaining = form.querySelectorAll('[data-remove-image]:not(:checked)').length;
            const total = remaining + selectedFiles.length;
            const max = Number(input.dataset.maxTotal || 4);
            countLabel.textContent = `${total} / ${max} images`;
        };

        const syncInput = () => {
            const transfer = new DataTransfer();
            selectedFiles.forEach(file => transfer.items.add(file));
            input.files = transfer.files;
        };

        const renderPreviews = () => {
            objectUrls.forEach(url => URL.revokeObjectURL(url));
            objectUrls = [];
            previews.replaceChildren();
            selectedFiles.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'announcement-preview-item';
                const image = document.createElement('img');
                image.alt = 'Selected image preview';
                image.src = URL.createObjectURL(file);
                objectUrls.push(image.src);
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = 'Remove';
                remove.setAttribute('aria-label', `Remove selected image ${index + 1}`);
                remove.addEventListener('click', () => {
                    selectedFiles = selectedFiles.filter((_, fileIndex) => fileIndex !== index);
                    syncInput();
                    renderPreviews();
                    updateCount();
                });
                item.append(image, remove);
                previews.append(item);
            });
        };

        input.addEventListener('change', () => {
            const removedCount = form.querySelectorAll('[data-remove-image]:checked').length;
            const existingCount = Number(input.dataset.existingCount || 0);
            const available = Number(input.dataset.maxTotal || 4) - existingCount + removedCount;
            const picked = Array.from(input.files || []);
            const issues = [];
            picked.forEach((file, index) => {
                if (index >= available) issues.push(`"${file.name}" exceeds the 4-image limit for this post.`);
                if (file.size > 2 * 1024 * 1024) issues.push(`"${file.name}" is larger than 2MB.`);
                if (!/\.(jpe?g|png|webp)$/i.test(file.name)) issues.push(`"${file.name}" has an unsupported file extension. Please upload JPG, PNG, or WebP.`);
            });
            invalidSelection = issues.length > 0;
            if (invalidSelection) {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Image selection rejected', text: issues.join('\n') });
                selectedFiles = [];
            } else {
                selectedFiles = picked;
            }
            syncInput();
            renderPreviews();
            updateCount();
        });

        form.querySelectorAll('[data-remove-image]').forEach(checkbox => checkbox.addEventListener('change', updateCount));
        form.addEventListener('submit', event => {
            if (!invalidSelection) return;
            event.preventDefault();
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Fix the image selection', text: 'Remove unsupported images and select no more than 4 images of 2 MB or less.' });
        });
        renderPreviews();
        updateCount();
    });

    document.querySelectorAll('form[data-confirm-delete]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            if (!window.Swal) {
                if (window.confirm(form.dataset.confirmDelete)) form.submit();
                return;
            }
            Swal.fire({
                title: 'Are you sure?',
                text: form.dataset.confirmDelete,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#7a1f1f',
                cancelButtonText: 'Cancel'
            }).then(result => { if (result.isConfirmed) form.submit(); });
        });
    });
});