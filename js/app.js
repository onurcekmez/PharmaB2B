/**
 * PharmaB2B - Main JavaScript File
 * 
 * Contains:
 * - Form validation (client-side)
 * - AJAX medicine search
 * - AJAX stock filtering
 * - AJAX order status refresh
 * - Utility functions
 */

// =====================================================
// LOGIN FORM VALIDATION (JavaScript Validation)
// =====================================================
function validateLoginForm() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    let valid = true;

    // Clear previous errors
    clearErrors();

    // Username validation
    if (!username.value.trim()) {
        showError(username, 'Kullanıcı adı gereklidir');
        valid = false;
    } else if (username.value.trim().length < 3) {
        showError(username, 'Kullanıcı adı en az 3 karakter olmalıdır');
        valid = false;
    }

    // Password validation
    if (!password.value) {
        showError(password, 'Şifre gereklidir');
        valid = false;
    } else if (password.value.length < 4) {
        showError(password, 'Şifre en az 4 karakter olmalıdır');
        valid = false;
    }

    return valid;
}

// =====================================================
// ORDER FORM VALIDATION
// =====================================================
function validateOrderForm() {
    const quantities = document.querySelectorAll('.qty-input');
    let hasItem = false;

    clearErrors();

    quantities.forEach(input => {
        const val = parseInt(input.value) || 0;
        if (val > 0) hasItem = true;
        if (val < 0) {
            showError(input, 'Miktar negatif olamaz');
            return false;
        }
    });

    if (!hasItem) {
        alert('Lütfen en az bir ilaç seçin ve miktar girin.');
        return false;
    }

    return true;
}

// =====================================================
// FORM ERROR HELPERS
// =====================================================
function showError(input, message) {
    input.classList.add('input-error');
    input.style.borderColor = '#dc2626';

    // Create error message element
    let errorEl = input.parentElement.querySelector('.error-text');
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'error-text';
        errorEl.style.color = '#dc2626';
        errorEl.style.fontSize = '0.8rem';
        errorEl.style.marginTop = '0.25rem';
        errorEl.style.display = 'block';
        input.parentElement.appendChild(errorEl);
    }
    errorEl.textContent = message;
}

function clearErrors() {
    document.querySelectorAll('.input-error').forEach(el => {
        el.classList.remove('input-error');
        el.style.borderColor = '';
    });
    document.querySelectorAll('.error-text').forEach(el => el.remove());
}

// =====================================================
// AJAX: MEDICINE SEARCH (Real-time, as user types)
// =====================================================
function initMedicineSearch() {
    const searchInput = document.getElementById('medicine-search');
    const categoryFilter = document.getElementById('category-filter');
    const resultsContainer = document.getElementById('medicine-results');

    if (!searchInput || !resultsContainer) return;

    let debounceTimer;

    // Search on keyup with debounce
    searchInput.addEventListener('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchMedicines();
        }, 300);
    });

    // Filter on category change
    if (categoryFilter) {
        categoryFilter.addEventListener('change', fetchMedicines);
    }

    function fetchMedicines() {
        const query = searchInput.value.trim();
        const category = categoryFilter ? categoryFilter.value : '';

        // AJAX call using Fetch API
        fetch(`ajax/search_medicines.php?q=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}`)
            .then(response => response.json())
            .then(data => {
                renderMedicineTable(data, resultsContainer);
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                resultsContainer.innerHTML = '<p class="text-muted">Arama sırasında hata oluştu.</p>';
            });
    }
}

/**
 * Render medicine results into a table
 */
function renderMedicineTable(medicines, container) {
    if (medicines.length === 0) {
        container.innerHTML = '<p class="text-muted mt-2">Sonuç bulunamadı.</p>';
        return;
    }

    let html = `<table>
        <thead>
            <tr>
                <th>İlaç Adı</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Birim Fiyat (₺)</th>
                <th>Son Kullanma</th>`;
    if (window.IS_ADMIN) {
        html += `<th>İşlemler</th>`;
    }
    html += `</tr>
        </thead>
        <tbody>`;

    medicines.forEach(med => {
        const stockClass = med.stock_quantity < 50 ? 'color:#dc2626;font-weight:600' : '';
        html += `<tr>
            <td>${escapeHtml(med.medicine_name)}</td>
            <td>${escapeHtml(med.category)}</td>
            <td style="${stockClass}">${med.stock_quantity}</td>
            <td>${parseFloat(med.unit_price).toFixed(2)}</td>
            <td>${med.expiration_date}</td>`;
        if (window.IS_ADMIN) {
            html += `<td>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                    <input type="hidden" name="delete_med_id" value="${med.medicine_id}">
                    <button type="submit" class="btn btn-danger btn-sm">Sil</button>
                </form>
            </td>`;
        }
        html += `</tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// =====================================================
// AJAX: ORDER STATUS REFRESH
// =====================================================
function refreshOrderStatus(orderId, badgeElement) {
    fetch(`ajax/order_status.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                badgeElement.textContent = getStatusLabel(data.status);
                badgeElement.className = 'badge badge-' + data.status;
            }
        })
        .catch(error => console.error('Status refresh error:', error));
}

/**
 * Refresh all order statuses on the page
 */
function refreshAllStatuses() {
    const badges = document.querySelectorAll('[data-order-id]');
    badges.forEach(badge => {
        const orderId = badge.getAttribute('data-order-id');
        refreshOrderStatus(orderId, badge);
    });
}

// =====================================================
// ORDER TOTAL CALCULATOR
// =====================================================
function initOrderCalculator() {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const totalDisplay = document.getElementById('order-total');

    if (!qtyInputs.length || !totalDisplay) return;

    qtyInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    function calculateTotal() {
        let total = 0;
        qtyInputs.forEach(input => {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.getAttribute('data-price')) || 0;
            total += qty * price;
        });
        totalDisplay.textContent = total.toFixed(2) + ' ₺';
    }
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Get Turkish label for order status
 */
function getStatusLabel(status) {
    const labels = {
        'pending':    'Beklemede',
        'approved':   'Onaylandı',
        'rejected':   'Reddedildi',
        'shipped':    'Kargoda',
        'delivered':  'Teslim Edildi',
        'cancelled':  'İptal',
        'preparing':  'Hazırlanıyor',
        'in_transit': 'Yolda'
    };
    return labels[status] || status;
}

// =====================================================
// TABLE FILTER (Client-side row filtering by name)
// Used on create_order.php and manage_stock.php
// =====================================================
function filterTable(input, tbodyId) {
    var filter = input.value.toLowerCase();
    var tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    var rows = tbody.getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        // First column is medicine name (or second if ID is first)
        var cells = rows[i].getElementsByTagName('td');
        var found = false;
        for (var j = 0; j < cells.length; j++) {
            var text = cells[j].textContent || cells[j].innerText;
            if (text.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        rows[i].style.display = found ? '' : 'none';
    }
}

// =====================================================
// INITIALIZE ON PAGE LOAD
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AJAX medicine search if on medicines page
    initMedicineSearch();

    // Initialize order calculator if on create order page
    initOrderCalculator();
});
