// public_html/assets/js/commission.js

document.addEventListener('DOMContentLoaded', () => {

    // ── Success Overlay Close ──
    const overlay   = document.getElementById('success-overlay');
    const closeBtn  = document.getElementById('success-close');

    closeBtn?.addEventListener('click', () => {
        overlay.style.display = 'none';
        // Clean URL so refreshing doesn't re-show overlay
        window.history.replaceState({}, document.title, 'commission.php');
    });

    // Also close on backdrop click
    overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.style.display = 'none';
            window.history.replaceState({}, document.title, 'commission.php');
        }
    });

    // ── Character Count ──
    const descTextarea = document.getElementById('description');
    const descCount    = document.getElementById('desc-count');

    descTextarea?.addEventListener('input', () => {
        const len = descTextarea.value.length;
        descCount.textContent = len;
        descCount.style.color = len > 1800 ? '#ef4444' : '';
    });

    // ── File Upload Preview ──
    const fileInput   = document.getElementById('references');
    const previewWrap = document.getElementById('file-preview');
    const uploadWrap  = document.getElementById('file-upload');
    const uploadArea  = document.getElementById('upload-area');

    let selectedFiles = [];

    fileInput?.addEventListener('change', () => {
        handleFiles(fileInput.files);
    });

    uploadArea?.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadWrap.classList.add('file-upload--dragover');
    });

    uploadArea?.addEventListener('dragleave', () => {
        uploadWrap.classList.remove('file-upload--dragover');
    });

    uploadArea?.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadWrap.classList.remove('file-upload--dragover');
        handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        const allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize  = 5 * 1024 * 1024;
        const maxFiles = 5;

        Array.from(files).forEach(file => {
            if (selectedFiles.length >= maxFiles) return;
            if (!allowed.includes(file.type)) return;
            if (file.size > maxSize) return;
            selectedFiles.push(file);
        });

        renderPreviews();
    }

    function renderPreviews() {
        previewWrap.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const item = document.createElement('div');
                item.className = 'file-preview-item';
                item.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button type="button" class="file-preview-item__remove" data-index="${index}">✕</button>
                `;
                previewWrap.appendChild(item);

                item.querySelector('.file-preview-item__remove').addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    renderPreviews();
                });
            };
            reader.readAsDataURL(file);
        });

        if (uploadArea) {
            uploadArea.querySelector('.file-upload__text').textContent =
                selectedFiles.length > 0
                    ? `${selectedFiles.length} file${selectedFiles.length > 1 ? 's' : ''} selected`
                    : 'Click or drag images here';
        }
    }

    // ── Form Validation ──
    const form = document.getElementById('commission-form');

    form?.addEventListener('submit', (e) => {
        const tier = document.querySelector('input[name="tier"]:checked');
        if (!tier) {
            e.preventDefault();
            document.getElementById('tier-cards')?.scrollIntoView({ behavior: 'smooth' });
            alert('Please select a commission tier before submitting.');
        }
    });

});