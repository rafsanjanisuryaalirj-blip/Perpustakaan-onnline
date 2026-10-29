let currentBookData = null;
let favorites = new Set();
let currentBorrowCount = 0; // Will be initialized from PHP side

// ====== CATEGORY FILTER FUNCTIONALITY ======
let allBooks = []; // Will be initialized from PHP side
let filteredBooks = [];
let currentCategoryFilter = '';

// Get unique categories from books
function getUniqueCategoriesFromBooks() {
    const categories = new Set();
    allBooks.forEach(book => {
        if (book.category) {
            categories.add(book.category);
        }
    });
    return Array.from(categories).sort();
}

// Initialize category filter dropdown
function initCategoryFilter() {
    const categorySelect = document.getElementById('categorySelect');
    if (!categorySelect) return;

    const categories = getUniqueCategoriesFromBooks();
    const currentValue = categorySelect.value;

    // Clear existing options except the first one
    while (categorySelect.children.length > 1) {
        categorySelect.removeChild(categorySelect.lastChild);
    }

    // Add categories
    categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat;
        option.textContent = cat;
        categorySelect.appendChild(option);
    });

    // Restore previous value if it exists
    if (currentValue && categories.includes(currentValue)) {
        categorySelect.value = currentValue;
        currentCategoryFilter = currentValue;
    }
}

// Handle category filter change
function onCategoryChange(event) {
    currentCategoryFilter = event.target.value;
    filterAndDisplayBooks();
}

// Filter books by search and category
function filterAndDisplayBooks() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchTerm = (searchInput?.value || '').toLowerCase();

    // Get sort option
    const sortSelect = document.getElementById('sortSelect');
    const sortBy = sortSelect?.value || 'newest';

    filteredBooks = allBooks.filter(book => {
        const matchSearch = !searchTerm ||
            (book.title || '').toLowerCase().includes(searchTerm) ||
            (book.author || '').toLowerCase().includes(searchTerm);
        const matchCategory = !currentCategoryFilter || book.category === currentCategoryFilter;
        return matchSearch && matchCategory;
    });

    // Apply Sorting
    if (sortBy === 'rating') {
        filteredBooks.sort((a, b) => {
            const ratingA = parseFloat(a.avg_rating) || 0;
            const ratingB = parseFloat(b.avg_rating) || 0;
            // First by rating, then by total reviews as tie-breaker
            if (ratingB !== ratingA) return ratingB - ratingA;
            return (parseInt(b.total_reviews) || 0) - (parseInt(a.total_reviews) || 0);
        });
    } else {
        // Default: Newest
        filteredBooks.sort((a, b) => parseInt(b.id) - parseInt(a.id));
    }

    updateBooksDisplay();
}

// Update books grid display
function updateBooksDisplay() {
    const booksGrid = document.querySelector('.books-grid');
    if (!booksGrid) return;

    if (filteredBooks.length === 0) {
        booksGrid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 24px;">
                <iconify-icon icon="mdi:book-search-outline" style="font-size: 48px; opacity: 0.3; margin-bottom: 12px; display: block;"></iconify-icon>
                <h3 style="margin: 0 0 8px 0; color: var(--text);">Tidak ada buku</h3>
                <p style="margin: 0; color: var(--text-muted);">Coba ubah filter atau pencarian Anda</p>
            </div>
        `;
        return;
    }

    booksGrid.innerHTML = filteredBooks.map(book => {
        const has_available = book.available_copies > 0;
        const avgRating = book.avg_rating ? parseFloat(book.avg_rating).toFixed(1) : '0';
        const totalReviews = parseInt(book.total_reviews) || 0;

        return `
        <div class="book-card-vertical" style="animation: fadeInScale 0.3s ease-out;">
            <div class="book-cover-container">
                ${book.cover_image ?
                `<img src="../img/covers/${book.cover_image}" alt="${book.title}" loading="lazy">` :
                `<div class="no-image-placeholder"><iconify-icon icon="mdi:book-open-variant" style="font-size: 32px;"></iconify-icon></div>`
            }
                <div class="stock-badge-overlay" style="
                    background: ${has_available ? 'color-mix(in srgb, var(--success) 15%, transparent)' : 'color-mix(in srgb, var(--danger) 15%, transparent)'};
                    color: ${has_available ? 'var(--success)' : 'var(--danger)'};
                    border: none;
                ">
                    ${has_available ? `Tersedia (${book.available_copies})` : 'Dipinjam'}
                </div>
                <button class="btn-love ${favorites.has(parseInt(book.id)) ? 'loved' : ''}" onclick="toggleFavorite(event, ${book.id}, '${(book.title || '').replace(/'/g, "\\'")}')">
                    <iconify-icon icon="mdi:heart${favorites.has(parseInt(book.id)) ? '' : '-outline'}"></iconify-icon>
                </button>
            </div>
            <div class="book-card-body">
                <div class="book-category">${book.category || 'Umum'}</div>
                <div class="book-title" title="${book.title}">${book.title}</div>
                <div class="book-author">${book.author || '-'}</div>
                ${!has_available ? `<p style="font-size: 10px; color: var(--danger); margin: -8px 0 8px 0;">Stok sedang kosong</p>` : ''}
                
                <div class="book-card-footer">
                    <div class="shelf-info">
                        <iconify-icon icon="mdi:star" style="color: #FFD700;"></iconify-icon>
                        <span style="font-weight: 700;">${avgRating}</span>
                        <span style="opacity: 0.6; margin-left: 2px;">(${totalReviews})</span>
                        ${book.shelf ? `<span style="opacity: 0.6; font-size: 10px; margin-left: auto;" title="Detail: ${book.lokasi_rak || ''}">• Rak ${book.shelf} / ${book.row_number || '-'} / ${book.lokasi_rak || '-'}</span>` : ''}
                    </div>
                    <div class="action-buttons">
                        ${!has_available ? `
                        <button class="btn-icon-sm" style="color: var(--danger); border-color: var(--danger);" 
                                onclick="joinWaitlist('${(book.title || '').replace(/'/g, "\\'")}', '${(book.author || '').replace(/'/g, "\\'")}')" 
                                title="Ingatkan Saya">
                            <iconify-icon icon="mdi:bell-plus-outline"></iconify-icon>
                        </button>` : ''}
                        <button class="btn-icon-sm" onclick='openBookModal(${JSON.stringify(book).replace(/'/g, "&#39;")})' title="Detail">
                            <iconify-icon icon="mdi:eye"></iconify-icon>
                        </button>
                        <a href="book-rating.php?id=${book.id}" class="btn-icon-sm" title="Rating & Review" style="color: var(--primary);">
                            <iconify-icon icon="mdi:star-outline"></iconify-icon>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    }).join('');
}

// Helper to truncate ISBN list
function formatISBNList(isbnString) {
    if (!isbnString || isbnString === '-') return '-';
    const isbns = isbnString.split(', ');
    if (isbns.length <= 1) return isbnString;

    const displayed = isbns.slice(0, 1).join(', ');
    const remainingCount = isbns.length - 1;

    return `
        <span class="isbn-short">${displayed}</span>
        <span class="isbn-more-indicator" style="color: var(--primary); cursor: pointer; font-size: 0.85em; font-weight: 600; margin-left: 4px;" 
              onclick="toggleISBN(this, '${escapeHtml(isbnString).replace(/'/g, "\\'")}')">
            +${remainingCount} lagi
        </span>
    `;
}

function toggleISBN(element, fullString) {
    const parent = element.parentElement;
    parent.innerHTML = `
        <span class="isbn-full" style="display: block; max-height: 80px; overflow-y: auto; font-size: 0.9em; line-height: 1.4;">${fullString}</span>
        <span style="color: var(--text-muted); cursor: pointer; font-size: 0.8em; text-decoration: underline; display: block; margin-top: 4px;" 
              onclick="revertISBN(this, '${fullString.replace(/'/g, "\\'")}')">Sembunyikan</span>
    `;
}

function revertISBN(element, fullString) {
    const parent = element.parentElement;
    parent.innerHTML = formatISBNList(fullString);
}

// Load favorites on page load
async function loadFavorites() {
    try {
        const response = await fetch('api/favorites.php?action=get_favorites');
        const data = await response.json();
        if (data.success && data.data) {
            data.data.forEach(fav => {
                favorites.add(fav.id_buku);
                const btn = document.querySelector(`[data-book-id="${fav.id_buku}"] .btn-love`);
                if (btn) {
                    btn.classList.add('loved');
                    const icon = btn.querySelector('iconify-icon');
                    if (icon) icon.setAttribute('icon', 'mdi:heart');
                }
            });
        }
    } catch (error) {
        console.error('Error loading favorites:', error);
    }
}

// Toggle favorite (Optimistic UI Update)
async function toggleFavorite(e, bookId, bookTitle) {
    e.preventDefault();
    e.stopPropagation();

    const btn = e.currentTarget;
    const icon = btn.querySelector('iconify-icon');
    const wasLoved = btn.classList.contains('loved');

    // 1. Optimistic UI Update
    if (wasLoved) {
        btn.classList.remove('loved');
        icon.setAttribute('icon', 'mdi:heart-outline');
        favorites.delete(bookId);
    } else {
        btn.classList.add('loved');
        icon.setAttribute('icon', 'mdi:heart');
        favorites.add(bookId);
    }

    try {
        const formData = new FormData();
        formData.append('id_buku', bookId);

        const action = wasLoved ? 'remove' : 'add';

        // 2. Kirim request di background
        const response = await fetch(`api/favorites.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // 3. Revert jika gagal
        if (!data.success) {
            throw new Error(data.message || 'Gagal mengubah status');
        }
    } catch (error) {
        console.error('Error:', error);

        // Revert UI ke state awal
        if (wasLoved) {
            btn.classList.add('loved');
            icon.setAttribute('icon', 'mdi:heart');
            favorites.add(bookId);
        } else {
            btn.classList.remove('loved');
            icon.setAttribute('icon', 'mdi:heart-outline');
            favorites.delete(bookId);
        }
    }
}

// Modal functions
function openBookModal(bookData) {
    currentBookData = bookData;

    // Set cover image
    const coverImg = document.getElementById('modalBookCover');
    const coverIcon = document.getElementById('modalBookIcon');
    if (bookData.cover_image) {
        coverImg.src = '../img/covers/' + bookData.cover_image;
        coverImg.style.display = 'block';
        coverIcon.style.display = 'none';
    } else {
        coverImg.style.display = 'none';
        coverIcon.style.display = 'block';
    }

    // Set modal info
    document.getElementById('modalBookTitle').textContent = bookData.title || '-';
    document.getElementById('modalBookAuthor').textContent = bookData.author || '-';
    document.getElementById('modalBookCategory').textContent = bookData.category || 'Umum';
    document.getElementById('modalBookISBN').innerHTML = formatISBNList(bookData.isbn);
    document.getElementById('modalBookShelf').textContent = `Rak ${bookData.shelf || '-'} / Baris ${bookData.row_number || '-'} / Kolom ${bookData.lokasi_rak || '-'}`;

    // Handle Availability & Borrowers
    const borrowerSection = document.getElementById('modalBorrowerSection');
    const modalActions = document.querySelector('.modal-actions');

    // Clear Waitlist Button
    const existingWaitlistBtn = document.getElementById('modalWaitlistBtn');
    if (existingWaitlistBtn) existingWaitlistBtn.remove();

    if (bookData.total_copies > 0) {
        borrowerSection.style.display = 'block';
        borrowerSection.className = 'borrower-section';

        const isAllBorrowed = bookData.available_copies == 0;
        const statusClass = isAllBorrowed ? 'busy' : 'available';
        const statusText = isAllBorrowed ? 'Stok Habis' : `Tersedia (${bookData.available_copies}/${bookData.total_copies})`;

        let html = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 12px;">
                <span class="modal-book-item-label"><iconify-icon icon="mdi:information-outline"></iconify-icon> Status Ketersediaan</span>
                <span class="status-badge ${statusClass} position-inline">${statusText}</span>
            </div>
        `;

        // Highlight borrowers if any copies are out
        if (bookData.available_copies < bookData.total_copies && bookData.borrower_names) {
            const borrowers = bookData.borrower_names.split(', ');
            html += `
                <div style="margin-top: 16px; border-top: 1px solid var(--border); padding-top: 16px;">
                    <span class="modal-book-item-label" style="color: var(--danger); margin-bottom: 8px;">
                        <iconify-icon icon="mdi:account-group-outline"></iconify-icon> Saat ini dipinjam oleh:
                    </span>
                    <div class="borrower-list">
                        ${borrowers.map(name => `
                            <div class="borrower-item">
                                <iconify-icon icon="mdi:account-circle" style="color: var(--primary); font-size: 18px;"></iconify-icon>
                                <span style="font-weight: 500;">${escapeHtml(name)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } else if (isAllBorrowed && !bookData.borrower_names) {
            html += `<p style="font-size: 13px; color: var(--text-muted); margin: 10px 0;">Data peminjam tidak tersedia.</p>`;
        }

        borrowerSection.innerHTML = html;

        // Waitlist button if empty
        if (isAllBorrowed) {
            const waitlistBtn = document.createElement('button');
            waitlistBtn.id = 'modalWaitlistBtn';
            waitlistBtn.className = 'modal-btn';
            waitlistBtn.style.background = 'rgba(239, 68, 68, 0.1)';
            waitlistBtn.style.color = 'var(--danger)';
            waitlistBtn.style.border = '1px solid var(--danger)';
            waitlistBtn.style.display = 'flex';
            waitlistBtn.style.alignItems = 'center';
            waitlistBtn.style.justifyContent = 'center';
            waitlistBtn.style.gap = '8px';
            waitlistBtn.innerHTML = '<iconify-icon icon="mdi:bell-plus"></iconify-icon> Ingatkan Saya';
            waitlistBtn.onclick = () => joinWaitlist(bookData.title, bookData.author);
            if (modalActions) modalActions.prepend(waitlistBtn);
        }
    } else {
        borrowerSection.style.display = 'none';
    }

    // Set rating link
    document.getElementById('modalRatingBtn').href = 'book-rating.php?id=' + bookData.id;

    // Show modal
    document.getElementById('bookModal').classList.add('active');
}

async function joinWaitlist(title, author) {
    try {
        const response = await fetch('api/waitlist-join.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                author: author,
                action: 'join'
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                confirmButtonColor: 'var(--primary)'
            });
        } else {
            throw new Error(data.message || 'Gagal mendaftar antrean');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: error.message || 'Terjadi kesalahan sistem',
            confirmButtonColor: 'var(--primary)'
        });
    }
}

function closeBookModal() {
    document.getElementById('bookModal').classList.remove('active');
    currentBookData = null;
}

// Request return function
function requestReturn(borrowId) {
    if (!confirm('Apakah Anda ingin mengajukan pengembalian buku ini?')) {
        return;
    }

    fetch('api/student-request-return.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'borrow_id=' + borrowId
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Permintaan pengembalian telah dikirim ke admin!');
                location.reload();
            } else {
                alert(data.message || 'Gagal mengajukan pengembalian');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
}

// KPI Modal helpers
function createListOverlay(title, itemsHtml) {
    const existing = document.getElementById('statsListOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'statsListOverlay';
    overlay.className = 'modal-overlay active';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.right = '0';
    overlay.style.bottom = '0';
    overlay.style.background = 'rgba(15, 23, 42, 0.4)';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '2200';
    overlay.style.opacity = '0';
    overlay.style.transition = 'opacity 0.25s ease';
    overlay.style.backdropFilter = 'blur(6px)';

    const container = document.createElement('div');
    container.className = 'modal-container';
    container.style.background = 'var(--card)';
    container.style.borderRadius = '20px';
    container.style.maxWidth = '1000px';
    container.style.width = '90%';
    container.style.maxHeight = '85vh';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.25)';
    container.style.transform = 'translateY(20px)';
    container.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
    container.style.border = '1px solid var(--border)';
    container.style.overflow = 'hidden';

    // Header
    const header = document.createElement('div');
    header.className = 'modal-header';
    header.style.display = 'flex';
    header.style.justifyContent = 'space-between';
    header.style.alignItems = 'center';
    header.style.padding = '20px 24px';
    header.style.borderBottom = '1px solid var(--border)';
    header.style.flexShrink = '0';
    header.style.background = 'var(--card)';

    const h = document.createElement('h2');
    h.textContent = title;
    h.style.margin = '0';
    h.style.fontSize = '18px';
    h.style.fontWeight = '700';
    h.style.color = 'var(--text)';
    header.appendChild(h);

    const closeBtn = document.createElement('button');
    closeBtn.className = 'modal-close';
    closeBtn.innerHTML = '<iconify-icon icon="mdi:close" width="20" height="20"></iconify-icon>';
    closeBtn.style.background = 'var(--bg)';
    closeBtn.style.border = '1px solid var(--border)';
    closeBtn.style.cursor = 'pointer';
    closeBtn.style.color = 'var(--text-muted)';
    closeBtn.style.width = '32px';
    closeBtn.style.height = '32px';
    closeBtn.style.display = 'flex';
    closeBtn.style.alignItems = 'center';
    closeBtn.style.justifyContent = 'center';
    closeBtn.style.borderRadius = '8px';

    closeBtn.onclick = () => {
        overlay.style.opacity = '0';
        container.style.transform = 'translateY(20px)';
        setTimeout(() => overlay.remove(), 250);
    };
    header.appendChild(closeBtn);
    container.appendChild(header);

    // Body
    const body = document.createElement('div');
    body.className = 'modal-body';
    body.style.flex = '1';
    body.style.overflowY = 'auto';
    body.style.padding = '0';
    body.innerHTML = itemsHtml;
    container.appendChild(body);

    overlay.appendChild(container);
    document.body.appendChild(overlay);

    requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        container.style.transform = 'translateY(0)';
    });
}

function renderBooksListHtml(list) {
    if (!list || list.length === 0) return '<div style="text-align: center; color: var(--text-muted); padding: 60px 24px;"><iconify-icon icon="mdi:book-search-outline" width="48" height="48" style="opacity: 0.2; margin-bottom: 12px; display: block;"></iconify-icon><p style="margin: 0; font-weight: 500;">Tidak ada data buku ditemukan</p></div>';

    let html = `
        <div style="overflow-x: auto; width: 100%;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0;">
                <thead>
                    <tr style="background: var(--bg); border-bottom: 2px solid var(--border);">
                        <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Judul Buku</th>
                        <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">ISBN</th>
                        <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Penulis</th>
                        <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Status</th>
                        <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Stok</th>
                    </tr>
                </thead>
                <tbody>
    `;

    list.forEach((item) => {
        const title = (item.title || '-').toString();
        const isbn = (item.isbn || '-').toString();
        const author = (item.author || '-').toString();
        const availableCount = parseInt(item.available_copies) || 0;
        const totalCount = parseInt(item.total_copies) || 0;
        const has_available = availableCount > 0;
        const statusText = has_available ? `Tersedia (${availableCount})` : 'Dipinjam';
        const statusClass = has_available ? 'badge-available' : 'badge-borrowed';

        let availabilityInfo = `<span style="color: ${has_available ? 'var(--success)' : 'var(--danger)'}; font-weight: 600;">${availableCount} / ${totalCount} Eksemplar</span>`;
        if (!has_available) {
            availabilityInfo = `<div style="color: var(--danger); font-weight: 600;">Semua dipinjam</div>`;
        }

        html += `
            <tr style="border-bottom: 1px solid var(--border); transition: all 0.2s ease;">
                <td style="padding: 16px 24px; color: var(--text); font-weight: 600;">${escapeHtml(title)}</td>
                <td style="padding: 16px 24px; color: var(--text-muted); max-width: 250px;">${formatISBNList(item.isbn)}</td>
                <td style="padding: 16px 24px; color: var(--text-muted);">${escapeHtml(author)}</td>
                <td style="padding: 16px 24px; text-align: center;">
                    <span class="cover-status-badge ${statusClass}" style="position: static; padding: 4px 10px; font-size: 10px;">${statusText}</span>
                </td>
                <td style="padding: 16px 24px; color: var(--text);">${availabilityInfo}</td>
            </tr>
        `;
    });

    html += `</tbody></table></div>`;
    return html;
}

function renderBorrowsListHtml(list) {
    if (!list || list.length === 0) return '<div style="text-align: center; color: var(--text-muted); padding: 60px 24px;"><iconify-icon icon="mdi:clock-check-outline" width="48" height="48" style="opacity: 0.2; margin-bottom: 12px; display: block;"></iconify-icon><p style="margin: 0; font-weight: 500;">Tidak ada aktivitas peminjaman</p></div>';

    let html = `
        <div style="overflow-x: auto; width: 100%;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0;">
                <thead>
                    <tr style="background: var(--bg); border-bottom: 2px solid var(--border);">
                        <th style="padding: 16px 24px; text-align: left; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Judul Buku</th>
                        <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Tgl Pinjam</th>
                        <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Batas Kembali</th>
                        <th style="padding: 16px 24px; text-align: center; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
    `;

    list.forEach((item) => {
        const title = (item.title || '-').toString();
        const borrowedAt = item.borrowed_at ? new Date(item.borrowed_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
        const dueAt = item.due_at ? new Date(item.due_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
        const status = (item.status || 'borrowed').toLowerCase();

        let statusBadge = '';
        if (status === 'overdue') {
            statusBadge = `<span class="cover-status-badge badge-borrowed" style="position: static; padding: 4px 10px; font-size: 10px; background: var(--danger);">TERLAMBAT</span>`;
        } else if (status === 'returned') {
            statusBadge = `<span class="cover-status-badge badge-available" style="position: static; padding: 4px 10px; font-size: 10px;">KEMBALI</span>`;
        } else {
            statusBadge = `<span class="cover-status-badge" style="position: static; padding: 4px 10px; font-size: 10px; background: var(--primary); color: white;">DIPINJAM</span>`;
        }

        html += `
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="padding: 16px 24px; color: var(--text); font-weight: 600;">${escapeHtml(title)}</td>
                <td style="padding: 16px 24px; text-align: center; color: var(--text-muted);">${borrowedAt}</td>
                <td style="padding: 16px 24px; text-align: center; color: var(--text-muted);">${dueAt}</td>
                <td style="padding: 16px 24px; text-align: center;">${statusBadge}</td>
            </tr>
        `;
    });

    html += `</tbody></table></div>`;
    return html;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// KPI Modal Handlers
function showTotalBooksModal() {
    if (typeof BOOKS_AVAILABLE !== 'undefined') {
        createListOverlay('Daftar Semua Buku', renderBooksListHtml(BOOKS_AVAILABLE));
    }
}

function showCurrentBorrowsModal() {
    if (typeof STUDENT_CURRENT_BORROWS !== 'undefined') {
        createListOverlay('Buku yang Sedang Dipinjam', renderBorrowsListHtml(STUDENT_CURRENT_BORROWS));
    }
}

function showActiveBorrowsModal() {
    if (typeof STUDENT_ACTIVE_BORROWS !== 'undefined') {
        createListOverlay('Buku yang Aktif Dipinjam', renderBorrowsListHtml(STUDENT_ACTIVE_BORROWS));
    }
}

function showOverdueBorrowsModal() {
    if (typeof STUDENT_OVERDUE_BORROWS !== 'undefined') {
        createListOverlay('Buku yang Terlambat (Overdue)', renderBorrowsListHtml(STUDENT_OVERDUE_BORROWS));
    }
}

// Category selection function (dari kategori pill)
function selectCategory(e, category) {
    if (e) e.preventDefault();

    // Update pills
    document.querySelectorAll('.category-pill').forEach(pill => {
        pill.classList.remove('active');
    });

    const targetPill = e ? e.target.closest('.category-pill') : null;
    if (targetPill) targetPill.classList.add('active');

    // Update dropdown dan kategori input
    const categoryInput = document.getElementById('categoryInput');
    const categoryLabel = document.getElementById('categoryLabel');

    if (categoryInput) categoryInput.value = category;
    if (categoryLabel) categoryLabel.textContent = category === '' ? 'Kategori' : category;

    // Update dropdown items
    const items = document.querySelectorAll('.dropdown-item');
    items.forEach(item => {
        item.classList.remove('active');
        if (item.textContent.trim() === (category === '' ? 'Semua Kategori' : category)) {
            item.classList.add('active');
        }
    });

    // Trigger filter
    const form = document.querySelector('.modern-search-bar-form');
    if (form) form.submit();
}

function toggleAllCategories() {
    alert('Fitur melihat semua kategori akan ditampilkan di sini');
}

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    // Sync data from PHP
    if (typeof window.allBooks !== 'undefined') {
        allBooks = window.allBooks;
        filteredBooks = [...allBooks];
    }

    loadFavorites();
    initCategoryFilter();

    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', filterAndDisplayBooks);
    }

    const categorySelect = document.getElementById('categorySelect');
    if (categorySelect) {
        categorySelect.addEventListener('change', onCategoryChange);
    }

    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', filterAndDisplayBooks);
    }

    // Modal click-outside handler
    const bookModal = document.getElementById('bookModal');
    if (bookModal) {
        bookModal.addEventListener('click', (e) => {
            if (e.target.id === 'bookModal') closeBookModal();
        });
    }
});
