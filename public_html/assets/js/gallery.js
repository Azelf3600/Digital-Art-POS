// public_html/assets/js/gallery.js
// Filtering and sorting handled server-side via form GET
// This file only handles: live search dropdown + favorites

document.addEventListener('DOMContentLoaded', () => {

    // ── Live Search ──
    const searchInp = document.getElementById('gallery-search-input');
    const searchRes = document.getElementById('search-results');
    const searchClr = document.getElementById('search-clear');
    let searchTimeout = null;

    searchInp?.addEventListener('input', () => {
        const q = searchInp.value.trim();
        searchClr.style.display = q ? 'block' : 'none';

        clearTimeout(searchTimeout);

        if (q.length < 2) {
            searchRes.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => doSearch(q), 300);
    });

    // Enter key — go to first result
    searchInp?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const first = searchRes.querySelector('a.search-result-item');
            if (first) window.location.href = first.href;
        }
        if (e.key === 'Escape') {
            searchRes.style.display = 'none';
        }
    });

    searchClr?.addEventListener('click', () => {
        searchInp.value         = '';
        searchRes.style.display = 'none';
        searchClr.style.display = 'none';
        searchInp.focus();
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.gallery-search')) {
            searchRes.style.display = 'none';
        }
    });

    async function doSearch(q) {
        try {
            const res = await fetch(GALLERY_API + 'search-artworks.php?q=' + encodeURIComponent(q));
            if (!res.ok) throw new Error('Search failed');

            const text = await res.text();
            if (!text.trim()) throw new Error('Empty response');

            const data = JSON.parse(text);

            if (!data.artworks || data.artworks.length === 0) {
                searchRes.innerHTML     = `<div class="search-no-results">No results for "${esc(q)}"</div>`;
                searchRes.style.display = 'block';
                return;
            }

            searchRes.innerHTML = data.artworks.map(art => `
                <a href="artwork.php?id=${art.id}" class="search-result-item">
                    <img src="${art.thumbnail_url}" alt="${esc(art.title)}">
                    <div>
                        <div class="search-result-item__title">${esc(art.title)}</div>
                        <div class="search-result-item__cat">${esc(art.category || '')}</div>
                    </div>
                    <div class="search-result-item__price">${art.price_formatted}</div>
                </a>
            `).join('');

            searchRes.style.display = 'block';

        } catch (err) {
            console.error('Search error:', err);
        }
    }

    // ── Favorites ──
    document.querySelectorAll('.artwork-card__fav').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id       = btn.dataset.id;
            const isActive = btn.classList.contains('active');
            const action   = isActive
                ? 'actions/favorite-remove.php'
                : 'actions/favorite-add.php';

            try {
                const res  = await fetch(action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `artwork_id=${id}`,
                });
                const data = await res.json();
                if (data.success) {
                    btn.classList.toggle('active');
                    btn.textContent = btn.classList.contains('active') ? '♥' : '♡';
                }
            } catch (e) {
                console.error('Favorite error:', e);
            }
        });
    });

    // ── Utility ──
    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

});