/*
 * MangaZone - Main JavaScript
 */
import './styles/app.css';

// ========================
// PWA Service Worker
// ========================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((reg) => console.log('SW registered:', reg.scope))
            .catch((err) => console.log('SW registration failed:', err));
    });
}

// ========================
// Notification Badge Polling
// ========================
function updateNotificationBadge() {
    const badge = document.getElementById('notif-count');
    if (!badge) return;

    fetch('/notifications/count', { credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            if (data.count > 0) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        })
        .catch(() => {});
}

// Poll every 30 seconds
if (document.getElementById('notif-count')) {
    updateNotificationBadge();
    setInterval(updateNotificationBadge, 30000);
}

// ========================
// AJAX Pagination (Infinite Scroll)
// ========================
(function () {
    const grid = document.getElementById('manga-grid');
    const searchForm = document.getElementById('searchForm');
    if (!grid || !searchForm) return;

    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    let totalPages = 1;

    // Create load more button
    const loadMoreContainer = document.createElement('div');
    loadMoreContainer.className = 'text-center mt-4 mb-4';
    loadMoreContainer.id = 'load-more-container';
    loadMoreContainer.innerHTML = `
        <button class="btn btn-outline-primary btn-lg" id="load-more-btn" style="display: none;">
            <i class="fas fa-plus-circle"></i> Charger plus de mangas
        </button>
        <div id="loading-spinner" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    grid.parentNode.insertBefore(loadMoreContainer, grid.nextSibling);

    const loadMoreBtn = document.getElementById('load-more-btn');
    const spinner = document.getElementById('loading-spinner');

    // Initial check - if more than 12 items, enable pagination
    function getSearchParams() {
        const formData = new FormData(searchForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData) {
            if (value) params.set(key, value);
        }
        return params.toString();
    }

    // Check initial count
    const initialItems = grid.querySelectorAll('.manga-item');
    if (initialItems.length >= 12) {
        // There might be more
        loadMoreBtn.style.display = 'inline-block';
        hasMore = true;
    }

    function loadMoreMangas() {
        if (isLoading || !hasMore) return;

        isLoading = true;
        currentPage++;
        loadMoreBtn.style.display = 'none';
        spinner.style.display = 'block';

        const params = getSearchParams();
        const url = `/api/v1/mangas/search?page=${currentPage}&limit=12&${params}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';

                if (data.data && data.data.length > 0) {
                    data.data.forEach(manga => {
                        const col = createMangaCard(manga);
                        grid.appendChild(col);
                    });

                    totalPages = data.meta.pages;
                    hasMore = currentPage < totalPages;

                    if (hasMore) {
                        loadMoreBtn.style.display = 'inline-block';
                    }
                } else {
                    hasMore = false;
                }

                isLoading = false;
            })
            .catch(() => {
                spinner.style.display = 'none';
                loadMoreBtn.style.display = 'inline-block';
                isLoading = false;
                currentPage--;
            });
    }

    function createMangaCard(manga) {
        const col = document.createElement('div');
        col.className = 'col manga-item';

        const stars = Array.from({ length: 5 }, (_, i) =>
            i < Math.round(manga.rating)
                ? '<i class="fas fa-star text-warning"></i>'
                : '<i class="far fa-star text-warning"></i>'
        ).join('');

        const description = manga.description
            ? (manga.description.length > 100 ? manga.description.substring(0, 100) + '...' : manga.description)
            : '';

        const badges = [
            manga.is_new ? '<span class="badge bg-success">Nouveau</span>' : '',
            manga.status ? `<span class="badge bg-info">${manga.status}</span>` : '',
        ].filter(Boolean).join(' ');

        col.innerHTML = `
            <div class="card h-100">
                ${manga.cover_image
                    ? `<img src="${manga.cover_image}" class="card-img-top" alt="${manga.title}" loading="lazy" style="height: 300px; object-fit: cover;">`
                    : '<div class="card-img-top bg-light text-center py-5"><i class="fas fa-book fa-3x text-muted"></i></div>'
                }
                <div class="card-body">
                    <h5 class="card-title">${manga.title}</h5>
                    <h6 class="card-subtitle mb-2 text-muted">${manga.author}</h6>
                    <p class="card-text">${description}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="rating">${stars}</div>
                        <div>${badges}</div>
                    </div>
                    ${manga.year ? `<small class="text-muted"><i class="fas fa-calendar"></i> ${manga.year}</small>` : ''}
                </div>
                <div class="card-footer">
                    <a href="/manga/${manga.id}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                </div>
            </div>
        `;

        return col;
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreMangas);
    }

    // Reset pagination on new search
    searchForm.addEventListener('submit', () => {
        currentPage = 1;
        hasMore = true;
    });
})();

// ========================
// Smooth scroll on anchor links
// ========================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// ========================
// Character counter for textareas
// ========================
document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
    const maxLength = textarea.getAttribute('maxlength');
    const counter = document.createElement('small');
    counter.className = 'text-muted float-end';
    counter.textContent = `${textarea.value.length}/${maxLength}`;
    textarea.parentNode.appendChild(counter);

    textarea.addEventListener('input', () => {
        counter.textContent = `${textarea.value.length}/${maxLength}`;
        counter.classList.toggle('text-danger', textarea.value.length > maxLength * 0.9);
    });
});
