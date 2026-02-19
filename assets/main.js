// ========================================
// BOOKSTORE - MAIN SCRIPT
// ========================================

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeQuickView();
    initializeSearch();
    initializeFilters();
    initializeTooltips();
});

// ========================================
// ANIMATIONS
// ========================================

function initializeAnimations() {
    // Observe elements for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = `fadeUp 0.8s ease-out forwards`;
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.book-card').forEach(card => {
        observer.observe(card);
    });

    // Add stagger animation to cards
    document.querySelectorAll('.book-card').forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
}

// ========================================
// QUICK VIEW MODAL
// ========================================

function initializeQuickView() {
    // Quick view buttons
    document.querySelectorAll('[data-quick-view]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const bookId = this.getAttribute('data-book-id');
            showQuickViewModal(bookId);
        });
    });

    // Close modal when clicking outside
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuickViewModal();
            }
        });
    }

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQuickViewModal();
        }
    });
}

function showQuickViewModal(bookId) {
    // Fetch book details via AJAX
    fetch(`get_book_details.php?id=${bookId}`)
        .then(response => response.json())
        .then(data => {
            // Create modal content
            const qtyInput = document.createElement('input');
            const modal = document.getElementById('quickViewModal');
            
            if (!modal) {
                console.error('Modal not found');
                return;
            }

            const starRating = Array(5).fill().map((_, i) => {
                const starClass = i < Math.floor(data.rating) ? '★' : '☆';
                return `<span class="star">${starClass}</span>`;
            }).join('');

            const content = `
                <div class="row">
                    <div class="col-md-5">
                        <img src="${data.image}" alt="${data.title}" class="img-fluid rounded" style="max-height: 400px; object-fit: cover; width: 100%;">
                        ${data.discount ? `<div class="badge-sale mt-2">-${data.discount}%</div>` : ''}
                    </div>
                    <div class="col-md-7">
                        <div class="mb-3">
                            <div class="star-rating">${starRating} <span class="rating-count">(${data.reviews} รีวิว)</span></div>
                        </div>
                        <p class="text-muted small mb-2">${data.author}</p>
                        <h3 class="price-tag mb-3">฿${data.price.toLocaleString('th-TH')}</h3>
                        ${data.original_price ? `<p class="text-muted"><del>฿${data.original_price.toLocaleString('th-TH')}</del></p>` : ''}
                        
                        <div class="mb-3">
                            ${data.stock > 0 ? `<span class="badge-stock in-stock">✓ มีในสต็อก (${data.stock} เล่ม)</span>` : `<span class="badge-stock out-of-stock">✗ สินค้าหมด</span>`}
                        </div>

                        <p class="mb-3 text-muted">${data.description}</p>

                        <div class="mb-3">
                            <label class="form-label">จำนวน:</label>
                            <div class="input-group">
                                <button class="btn btn-outline-secondary" type="button" onclick="decreaseQty()">−</button>
                                <input type="number" class="form-control text-center" id="modal-qty" value="1" min="1" max="${data.stock}">
                                <button class="btn btn-outline-secondary" type="button" onclick="increaseQty()">+</button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-lg" onclick="addToCartFromModal(${data.id})" ${data.stock <= 0 ? 'disabled' : ''}>
                                <i class="fas fa-shopping-cart"></i> เพิ่มลงตะกร้า
                            </button>
                            <button class="btn btn-outline-light" onclick="toggleWishlistModal(${data.id}, this)">
                                <i class="fas fa-heart"></i> เพิ่มไปรายการโปรด
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Clear previous content and insert new
            const bodyContent = modal.querySelector('.modal-body');
            bodyContent.innerHTML = content;
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('ไม่สามารถโหลดรายละเอียดสินค้า', 'error');
        });
}

function closeQuickViewModal() {
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

function generateStars(rating) {
    let stars = '';
    for (let i = 0; i < 5; i++) {
        if (i < Math.floor(rating)) {
            stars += '<span class="star">★</span>';
        } else if (i < rating) {
            stars += '<span class="star">☆</span>';
        } else {
            stars += '<span class="star">☆</span>';
        }
    }
    return stars;
}

function increaseQty() {
    const qty = document.getElementById('modal-qty') || document.getElementById('qty');
    if (qty) qty.value = parseInt(qty.value) + 1;
}

function decreaseQty() {
    const qty = document.getElementById('modal-qty') || document.getElementById('qty');
    if (qty && parseInt(qty.value) > 1) qty.value = parseInt(qty.value) - 1;
}

function addToCartFromModal(bookId) {
    const qty = document.getElementById('modal-qty').value;
    addToCart(bookId, qty);
    closeQuickViewModal();
}

// ========================================
// SEARCH FUNCTIONALITY
// ========================================

function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const query = e.target.value;
            if (query.length >= 2) {
                performSearch(query);
            }
        }, 300));
    }
}

function performSearch(query) {
    fetch(`search_books_ajax.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            updateBooksList(data);
        })
        .catch(error => console.error('Search error:', error));
}

function updateBooksList(books) {
    const container = document.getElementById('booksContainer');
    if (!container) return;

    if (books.length === 0) {
        container.innerHTML = '<div class="alert alert-info text-center">ไม่พบหนังสือที่ตรงกับการค้นหา</div>';
        return;
    }

    container.innerHTML = books.map(book => `
        <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
            <div class="book-card">
                <div class="img-wrap">
                    <img src="${book.image}" alt="${book.title}" loading="lazy">
                </div>
                <div class="card-overlay">
                    ${book.discount ? `<div class="badge-sale">-${book.discount}%</div>` : ''}
                    <h5 class="book-title">${book.title}</h5>
                    <p class="text-muted small mb-2">${book.author}</p>
                    <div class="mb-2">
                        <span class="star-rating">${generateStars(book.rating)}</span>
                    </div>
                    <h6 class="price-tag">฿${book.price.toLocaleString('th-TH')}</h6>
                    ${book.stock > 0 ? `<span class="badge-stock in-stock">มีสต็อก</span>` : `<span class="badge-stock out-of-stock">หมด</span>`}
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn-cart-mini" onclick="addToCart(${book.id})">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                        <button class="btn-wish-mini" onclick="toggleWishlist(${book.id}, this)">
                            <i class="fas fa-heart"></i>
                        </button>
                        <button class="btn btn-sm btn-primary flex-grow-1" data-quick-view data-book-id="${book.id}">
                            ดูเพิ่มเติม
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    // Re-initialize quick view for new elements
    initializeQuickView();
}

// ========================================
// FILTER FUNCTIONALITY
// ========================================

function initializeFilters() {
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            applyFilter(filter);
            
            // Update active state
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    document.querySelectorAll('[data-sort]').forEach(btn => {
        btn.addEventListener('click', function() {
            const sort = this.getAttribute('data-sort');
            applySort(sort);
        });
    });
}

function applyFilter(filter) {
    fetch(`filter_books_ajax.php?filter=${filter}`)
        .then(response => response.json())
        .then(data => updateBooksList(data))
        .catch(error => console.error('Filter error:', error));
}

function applySort(sort) {
    fetch(`sort_books_ajax.php?sort=${sort}`)
        .then(response => response.json())
        .then(data => updateBooksList(data))
        .catch(error => console.error('Sort error:', error));
}

// ========================================
// WISHLIST
// ========================================

function toggleWishlist(bookId, element) {
    const isWished = element.classList.contains('active');
    
    fetch('wishlist_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${isWished ? 'remove' : 'add'}&book_id=${bookId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            element.classList.toggle('active');
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('ไม่สามารถทำรายการได้', 'error');
    });
}

function toggleWishlistModal(bookId, element) {
    toggleWishlist(bookId, element);
}

// ========================================
// CART
// ========================================

function addToCart(bookId, qty = 1) {
    fetch('cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&book_id=${bookId}&quantity=${qty}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('เพิ่มลงตะกร้าแล้ว!', 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('ไม่สามารถเพิ่มลงตะกร้าได้', 'error');
    });
}

function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('[data-cart-count]');
            if (badge) {
                badge.textContent = data.count;
            }
        });
}

// ========================================
// NOTIFICATIONS
// ========================================

function showNotification(message, type = 'info') {
    const alertClass = `alert-${type}`;
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${getIcon(type)} me-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function getIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}


// ========================================
// TOOLTIPS
// ========================================

function initializeTooltips() {
    // Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }
}

// ========================================
// UTILITIES
// ========================================

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ========================================
// PAGE LOAD ANIMATIONS
// ========================================

window.addEventListener('load', function() {
    document.body.style.opacity = '1';
    
    // Animate elements on page load
    document.querySelectorAll('[data-animate]').forEach((el, index) => {
        el.style.animation = `fadeUp 0.8s ease-out ${index * 0.1}s backwards`;
    });
});

// ========================================
// DARK MODE SUPPORT
// ========================================

function initializeDarkMode() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
        document.documentElement.style.colorScheme = 'dark';
    }
}

initializeDarkMode();

// ========================================
// EXPORTS
// ========================================

window.showQuickViewModal = showQuickViewModal;
window.closeQuickViewModal = closeQuickViewModal;
window.toggleWishlist = toggleWishlist;
window.addToCart = addToCart;
window.showNotification = showNotification;
window.increaseQty = increaseQty;
window.decreaseQty = decreaseQty;
window.addToCartFromModal = addToCartFromModal;
