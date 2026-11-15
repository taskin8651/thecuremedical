document.addEventListener('click', function (e) {
    const button = e.target.closest('[data-upload-button]');

    if (button) {
        const box = button.closest('.custom-upload-box');
        box.querySelector('[data-upload-input]').click();
    }
});

document.addEventListener('change', function (e) {
    if (!e.target.matches('[data-upload-input]')) return;

    const input = e.target;
    const preview = document.getElementById(input.dataset.preview);

    preview.innerHTML = '';

    Array.from(input.files).forEach(file => {

        const item = document.createElement('div');
        item.className = 'upload-preview-item';

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.onload = () => URL.revokeObjectURL(img.src);
            item.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.className = 'file-icon';
            icon.innerHTML = '<i class="bi bi-file-earmark-pdf"></i>';
            item.appendChild(icon);
        }

        const name = document.createElement('small');
        name.textContent = file.name;

        item.appendChild(name);
        preview.appendChild(item);
    });
});

// Drag & Drop
document.addEventListener('dragover', function (e) {
    const zone = e.target.closest('[data-dropzone]');
    if (zone) {
        e.preventDefault();
        zone.classList.add('drag-active');
    }
});

document.addEventListener('dragleave', function (e) {
    const zone = e.target.closest('[data-dropzone]');
    if (zone && !zone.contains(e.relatedTarget)) {
        zone.classList.remove('drag-active');
    }
});

document.addEventListener('drop', function (e) {
    const zone = e.target.closest('[data-dropzone]');
    if (!zone) return;

    e.preventDefault();
    zone.classList.remove('drag-active');

    const input = zone.closest('.custom-upload-box')
        .querySelector('[data-upload-input]');

    const files = Array.from(e.dataTransfer.files);

    if (!input.multiple && files.length > 1) {
        alert('Please select only one file.');
        return;
    }

    const transfer = new DataTransfer();

    files.forEach(file => transfer.items.add(file));

    input.files = transfer.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
});
