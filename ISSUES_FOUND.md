# 🔴 Critical Issues Found - FarmConnect

## Issues & Solutions

### Issue #1: Subscription Response Handling Bug
**Location**: `frontend/index.html` line ~640  
**Problem**: Function tries to access `res.discount_pct` but API returns `discount_percent`
```javascript
// WRONG
showToast('✓ Subscription activated! ' + res.discount_pct + '% discount applied.', 'success');

// CORRECT - Need to parse from response.data
```
**Fix**: Update to access from `res.data.discount_percent`

---

### Issue #2: Missing Admin Users Endpoint
**Location**: `backend/auth/admin_users.php` - **FILE MISSING**  
**Problem**: Frontend calls `API.getUsers()` and `API.manageUser()` but endpoint doesn't exist
**Fix**: Create endpoint to list users and manage farmer approval

---

### Issue #3: Search Input Not Wired
**Location**: `frontend/index.html` line ~517  
**Problem**: Search input has event listener but doesn't call `loadProducts()`
```javascript
// Event listener exists but handler doesn't load products
document.getElementById('searchInput').addEventListener('input', function () { ... });
```
**Fix**: Actually call `loadProducts()` with search term

---

### Issue #4: Farmer Profile Image URLs Broken
**Location**: `frontend/index.html` - farmer card rendering  
**Problem**: API returns relative path for `profile_photo`, but not converted to full URL
```javascript
// Returns: "farmer_profiles/farmer_2_123456.jpg"
// Needs: "http://localhost/farmconnect/backend/uploads/farmer_profiles/farmer_2_123456.jpg"
```
**Fix**: Add BASE_URL prefix in farmer card rendering

---

### Issue #5: Exotic Fruits Filter Not Supported
**Location**: `frontend/index.html` line ~706  
**Problem**: Frontend sends `is_exotic: 1` parameter but backend doesn't support it
**Fix**: Add support in backend for `is_exotic` parameter OR remove from frontend

---

### Issue #6: No Real Fruit Images
**Location**: Database products table  
**Problem**: No products have images, all show placeholder
**Fix**: Add seed products with real image URLs from Unsplash/public sources

---

## Status: FIXING NOW
