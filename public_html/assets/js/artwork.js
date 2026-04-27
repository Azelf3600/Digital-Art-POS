// public_html/assets/js/artwork.js

document.addEventListener('DOMContentLoaded', () => {

    // ── Favorite Button ──
    const favBtn = document.getElementById('fav-btn');

    favBtn?.addEventListener('click', async () => {
        const id       = favBtn.dataset.id;
        const isActive = favBtn.classList.contains('active');
        const action   = isActive
            ? 'actions/favorite-remove.php'
            : 'actions/favorite-add.php';

        // Optimistic UI update
        favBtn.classList.toggle('active');
        favBtn.textContent = favBtn.classList.contains('active') ? '♥' : '♡';

        try {
            const res = await fetch(action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `artwork_id=${id}`,
            });

            const data = await res.json();

            if (!data.success) {
                // Revert on failure
                favBtn.classList.toggle('active');
                favBtn.textContent = favBtn.classList.contains('active') ? '♥' : '♡';
            }
        } catch (err) {
            // Revert on error
            favBtn.classList.toggle('active');
            favBtn.textContent = favBtn.classList.contains('active') ? '♥' : '♡';
            console.error('Favorite error:', err);
        }
    });

    // ── Thumbnail Click (for future multiple images) ──
    document.querySelectorAll('.artwork-thumb').forEach(thumb => {
        thumb.addEventListener('click', () => {
            document.querySelectorAll('.artwork-thumb').forEach(t =>
                t.classList.remove('artwork-thumb--active')
            );
            thumb.classList.add('artwork-thumb--active');

            const mainImg = document.getElementById('artwork-main-img');
            const thumbImg = thumb.querySelector('img');
            if (mainImg && thumbImg) {
                mainImg.src = thumbImg.src;
            }
        });
    });

    // ── Image Zoom on Click ──
    const mainImg = document.getElementById('artwork-main-img');
    const imgWrap = mainImg?.closest('.artwork-image-wrap');

    if (imgWrap) {
        imgWrap.style.cursor = 'zoom-in';
        imgWrap.addEventListener('click', () => {
            openLightbox(mainImg.src, mainImg.alt);
        });
    }

    // ── Lightbox ──
    function openLightbox(src, alt) {
        const overlay = document.createElement('div');
        overlay.className = 'lightbox';
        overlay.innerHTML = `
            <div class="lightbox__inner">
                <button class="lightbox__close">✕</button>
                <img src="${src}" alt="${alt}" class="lightbox__img">
            </div>
        `;

        // Styles injected inline so no extra CSS needed
        Object.assign(overlay.style, {
            position:        'fixed',
            inset:           '0',
            background:      'rgba(0,0,0,0.92)',
            zIndex:          '9999',
            display:         'flex',
            alignItems:      'center',
            justifyContent:  'center',
            padding:         '2rem',
            cursor:          'zoom-out',
            animation:       'fade-up 0.2s ease',
        });

        const inner = overlay.querySelector('.lightbox__inner');
        Object.assign(inner.style, {
            position:        'relative',
            maxWidth:        '90vw',
            maxHeight:       '90vh',
        });

        const img = overlay.querySelector('.lightbox__img');
        Object.assign(img.style, {
            maxWidth:        '100%',
            maxHeight:       '90vh',
            objectFit:       'contain',
            borderRadius:    '8px',
            display:         'block',
        });

        const closeBtn = overlay.querySelector('.lightbox__close');
        Object.assign(closeBtn.style, {
            position:        'absolute',
            top:             '-1.5rem',
            right:           '-1.5rem',
            background:      'rgba(255,255,255,0.1)',
            border:          'none',
            borderRadius:    '50%',
            width:           '32px',
            height:          '32px',
            color:           '#fff',
            cursor:          'pointer',
            fontSize:        '0.8rem',
            display:         'flex',
            alignItems:      'center',
            justifyContent:  'center',
        });

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        const close = () => {
            overlay.remove();
            document.body.style.overflow = '';
        };

        closeBtn.addEventListener('click', (e) => { e.stopPropagation(); close(); });
        overlay.addEventListener('click', close);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); }, { once: true });
    }

});