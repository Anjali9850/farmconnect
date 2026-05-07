// ============================================================
//  FarmConnect – api.js  (shared across all pages)
//  Handles all backend communication and shared utilities
// ============================================================

const API = (function () {
    // ── Base URL: adjust if your folder name differs ──────────
    const BASE = '/farmconnect/backend';

    async function request(path, method, body, isForm) {
        method  = method  || 'GET';
        isForm  = isForm  || false;

        var opts = {
            method:      method,
            credentials: 'include'   // send session cookie
        };

        if (body) {
            if (isForm) {
                opts.body = body;    // FormData — no Content-Type header needed
            } else {
                opts.headers = { 'Content-Type': 'application/json' };
                opts.body    = JSON.stringify(body);
            }
        }

        try {
            var res  = await fetch(BASE + path, opts);
            var text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                // PHP returned HTML error — surface it
                return {
                    success: false,
                    message: 'Server returned non-JSON. Likely a PHP error. Raw: ' +
                             text.substring(0, 300),
                    data: null
                };
            }
        } catch (e) {
            return {
                success: false,
                message: 'Network error — is XAMPP Apache running? (' + e.message + ')',
                data: null
            };
        }
    }

    return {
        // ── Auth ──────────────────────────────────────────────
        session:  function ()              { return request('/auth/session.php'); },
        login:    function (email, pass)   { return request('/auth/login.php',    'POST', { email: email, password: pass }); },
        register: function (data)          { return request('/auth/register.php', 'POST', data); },
        logout:   function ()              { return request('/auth/logout.php'); },

        // ── Products ──────────────────────────────────────────
        getProducts: function (params) {
            var qs = '';
            if (params && typeof params === 'object') {
                var parts = [];
                for (var k in params) {
                    if (params[k] !== '' && params[k] !== null && params[k] !== undefined) {
                        parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
                    }
                }
                if (parts.length) qs = '?' + parts.join('&');
            }
            return request('/products/get_products.php' + qs);
        },
        addProduct:    function (formData)    { return request('/products/add_product.php',    'POST', formData, true); },
        updateProduct: function (formData)    { return request('/products/update_product.php', 'POST', formData, true); },
        deleteProduct: function (id)          { return request('/products/delete_product.php', 'POST', { id: id }); },

        // ── Cart ──────────────────────────────────────────────
        getCart:        function ()           { return request('/orders/cart.php'); },
        addToCart:      function (pid, qty)   { return request('/orders/cart.php', 'POST',   { product_id: pid, quantity: qty || 1 }); },
        removeFromCart: function (pid)        { return request('/orders/cart.php', 'DELETE', { product_id: pid }); },

        // ── Orders ────────────────────────────────────────────
        placeOrder:    function ()            { return request('/orders/place_order.php',  'POST'); },
        getOrders:     function (filter)      { var qs = filter ? '?filter=' + filter : ''; return request('/orders/get_orders.php' + qs); },
        updateStatus:  function (oid, status) { return request('/orders/update_status.php','POST', { order_id: oid, status: status }); },
        acceptOrder:   function (oid, action) { return request('/orders/accept_order.php', 'POST', { order_id: oid, action: action }); },

        // ── Farmers ───────────────────────────────────────────
        getFarmers:     function ()           { return request('/farmers/get_farmers.php'); },
        getFarmerProfile: function (id)       { return request('/farmers/get_profile.php?id=' + id); },
        updateFarmerProfile: function (data) { return request('/farmers/update_profile.php', 'POST', data); },
        addCertificate: function (formData)  { return request('/farmers/add_certificate.php', 'POST', formData, true); },
        verifyCertificate: function (certId, isVerified) { return request('/farmers/verify_certificate.php', 'POST', { cert_id: certId, is_verified: isVerified }); },
        addFarmPractice: function (data)     { return request('/farmers/add_practice.php', 'POST', data); },
        deleteGalleryImage: function (imgId) { return request('/farmers/delete_gallery_image.php', 'POST', { image_id: imgId }); },
        uploadGalleryImages: function (formData) { return request('/farmers/upload_gallery.php', 'POST', formData, true); },
        addFarmerReview: function (farmerId, rating, reviewText) { return request('/farmers/add_review.php', 'POST', { farmer_id: farmerId, rating: rating, review_text: reviewText }); },

        // ── Subscriptions ─────────────────────────────────────
        subscribe:  function (planType)   { return request('/subscriptions/subscribe.php', 'POST', { plan_type: planType }); },
        getPlan:    function ()           { return request('/subscriptions/get_plan.php'); },

        // ── Notifications ─────────────────────────────────────
        getNotifications: function (limit, offset) { 
            var qs = '?limit=' + (limit || 20) + '&offset=' + (offset || 0);
            return request('/notifications/get.php' + qs); 
        },
        markNotificationRead: function (nid) { return request('/notifications/mark_read.php', 'POST', { notification_id: nid }); },
        markAllNotificationsRead: function () { return request('/notifications/mark_read.php', 'POST', { mark_all: true }); },

        // ── Wishlist ──────────────────────────────────────────
        addToWishlist: function (productId) { return request('/orders/wishlist.php', 'POST', { product_id: productId }); },
        removeFromWishlist: function (productId) { return request('/orders/wishlist.php', 'DELETE', { product_id: productId }); },
        getWishlist: function () { return request('/orders/wishlist.php'); },

        // ── Admin ─────────────────────────────────────────────
        getUsers:   function ()              { return request('/auth/admin_users.php'); },
        manageUser: function (uid, action)   { return request('/auth/admin_users.php', 'POST', { user_id: uid, action: action }); },
        approveFarmer: function (farmerId) { return request('/auth/admin_users.php', 'POST', { user_id: farmerId, action: 'approve' }); },
        rejectFarmer: function (farmerId) { return request('/auth/admin_users.php', 'POST', { user_id: farmerId, action: 'reject' }); },
        getPendingFarmers: function () { return request('/auth/admin_users.php?role=farmer&approved=0'); }
    };
}());

// ============================================================
//  Toast notifications
// ============================================================
function showToast(msg, type) {
    type = type || 'success';
    var colours = { success: '#2E7D32', error: '#c62828', info: '#1565C0' };
    var bg = colours[type] || colours.success;

    var container = document.getElementById('toastContainer');
    if (!container) {
        container    = document.createElement('div');
        container.id = 'toastContainer';
        container.setAttribute('style',
            'position:fixed;bottom:22px;right:22px;z-index:99999;' +
            'display:flex;flex-direction:column;gap:8px;');
        document.body.appendChild(container);
    }

    var t = document.createElement('div');
    t.setAttribute('style',
        'background:' + bg + ';color:white;padding:12px 20px;border-radius:10px;' +
        'font-size:0.87rem;font-weight:500;box-shadow:0 4px 18px rgba(0,0,0,0.22);' +
        'display:flex;align-items:center;gap:10px;min-width:230px;' +
        'animation:fcToastIn 0.3s ease;font-family:"DM Sans",Arial,sans-serif;');
    var dot = document.createElement('span');
    dot.setAttribute('style',
        'width:8px;height:8px;background:rgba(255,255,255,0.55);' +
        'border-radius:50%;flex-shrink:0;');
    t.appendChild(dot);
    t.appendChild(document.createTextNode(msg));
    container.appendChild(t);

    setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 3500);
}

// Inject toast animation once
(function () {
    if (document.getElementById('fcToastStyle')) return;
    var s = document.createElement('style');
    s.id  = 'fcToastStyle';
    s.textContent = '@keyframes fcToastIn{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}}';
    document.head.appendChild(s);
}());

// ============================================================
//  Session guard – call on dashboard pages
//  requiredRole: 'customer' | 'farmer' | 'admin' | null (any)
// ============================================================
async function checkSession(requiredRole) {
    var res = await API.session();
    if (!res.success) {
        // Not logged in — send to homepage
        window.location.href = '/farmconnect/frontend/index.html';
        return null;
    }
    var user = res.data;
    if (requiredRole && user.role !== requiredRole) {
        // Logged in but wrong role — redirect to correct dashboard
        var pages = {
            customer: '/farmconnect/frontend/customer.html',
            farmer:   '/farmconnect/frontend/farmer.html',
            admin:    '/farmconnect/frontend/admin.html'
        };
        window.location.href = pages[user.role] || '/farmconnect/frontend/index.html';
        return null;
    }
    return user;
}

// ============================================================
//  Shared formatting helpers
// ============================================================
function formatCurrency(n) {
    return '₹' + parseFloat(n || 0).toFixed(2);
}

function formatDate(str) {
    if (!str) return '—';
    try {
        return new Date(str).toLocaleDateString('en-IN', {
            day: 'numeric', month: 'short', year: 'numeric'
        });
    } catch (e) { return str; }
}

function statusBadge(s) {
    var map = {
        pending:    { bg: '#fef3c7', color: '#d97706' },
        processing: { bg: '#dbeafe', color: '#2563eb' },
        completed:  { bg: '#d1fae5', color: '#059669' },
        cancelled:  { bg: '#fee2e2', color: '#dc2626' }
    };
    var c = map[s] || { bg: '#f3f4f6', color: '#6b7280' };
    return '<span style="padding:3px 10px;border-radius:20px;background:' + c.bg +
           ';color:' + c.color + ';font-size:0.75rem;font-weight:600;text-transform:capitalize">' +
           (s || '—') + '</span>';
}
