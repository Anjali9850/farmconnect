# FarmConnect - Developer Quick Reference

## 🚀 Quick Start (30 seconds)

```bash
# 1. Start XAMPP (Apache + MySQL)
# 2. Open browser
http://localhost/farmconnect/frontend/index.html

# 3. Log in with
Email: customer@test.com
Password: test123
```

---

## 📱 API Quick Calls

### Get Farmers List
```javascript
const farmers = await API.getFarmers();
// Returns: Array of farmers with stats (products_count, avg_rating, etc.)
```

### Get Farmer Profile
```javascript
const profile = await API.getFarmerProfile(2);
// Returns: Full profile with certs, practices, gallery, products, reviews
```

### Add Product to Cart (Sends Farmer Notification)
```javascript
await API.addToCart(1, 2); // product_id=1, quantity=2
// ✅ Farmer gets notification: "Customer added 2x Product to cart"
```

### Subscribe to Plan
```javascript
await API.subscribe('premium');
// ✅ Gets 15% discount, free delivery, priority support
// ✅ Notification: "You are now a Premium subscriber"
```

### Get Notifications
```javascript
const notifs = await API.getNotifications(20, 0); // limit=20, offset=0
// Returns: Array of notifications with is_read status

await API.markNotificationRead(5); // Mark specific notification as read
await API.markAllNotificationsRead(); // Mark all as read
```

### Farmer Operations
```javascript
// Upload certificate (triggers farmer notification when verified)
await API.addCertificate(formData); // FormData with cert_file, cert_name, cert_type

// Add farming practice
await API.addFarmPractice({
  practice_name: "Drip Irrigation",
  practice_type: "irrigation",
  is_organic: 1
});

// Upload farm image
await API.uploadGalleryImages(formData); // FormData with image, image_title

// Add customer review
await API.addFarmerReview(2, 5, "Excellent quality vegetables!");
```

### Wishlist
```javascript
await API.addToWishlist(1); // Add product to wishlist
await API.getWishlist(); // Get all wishlisted products
await API.removeFromWishlist(1); // Remove from wishlist
```

### Admin Operations
```javascript
await API.verifyCertificate(1, 1); // cert_id=1, is_verified=1 (approve)
await API.verifyCertificate(1, 0); // is_verified=0 (reject)
// ✅ Farmer gets notification about verification status
```

---

## 📊 Subscription Plans

| Plan | Price | Discount | Delivery | Support |
|------|-------|----------|----------|---------|
| Basic | ₹99 | 5% | ❌ | ❌ |
| Premium | ₹299 | 15% | ✅ Free | ✅ Priority |
| Farmer Support | ₹499 | 20% | ✅ Free | ✅ Priority |

---

## 🔐 Test Accounts (All use password: `test123`)

```
Customer:  customer@test.com
Farmer:    farmer@test.com  (approved)
Admin:     admin@test.com
```

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `/backend/config.php` | Database connection + helpers |
| `/backend/farmers/*` | Farmer profile APIs |
| `/backend/notifications/*` | Notification APIs |
| `/backend/subscriptions/*` | Subscription APIs |
| `/backend/orders/cart.php` | Cart (enhanced with notifications) |
| `/backend/orders/wishlist.php` | Wishlist APIs |
| `/frontend/assets/api.js` | Frontend API wrapper |
| `database/migration_v2.sql` | Database schema migration |

---

## 🔧 Common Tasks

### Enable a Feature
```php
// All features are enabled by default
// To disable, modify config.php or API endpoints
```

### Send Custom Notification
```php
// In any PHP file (after requiring config.php)
sendNotification($user_id, $type, $title, $message, $related_user_id, $related_order_id, $related_product_id);

// Example:
sendNotification(5, 'order', 'New Order', 'Order #123 received', null, 123, null);
```

### Check User Role
```php
$user = requireLogin(); // Get current user (throws error if not logged in)
$user = requireRole('farmer'); // Get user + check role (throws error if not farmer)
$user = requireRole('farmer', 'admin'); // Allow multiple roles
```

### Get Database Connection
```php
$db = getDB(); // Returns mysqli connection (singleton)
```

### Validate Input
```php
$email = clean($_POST['email']); // Sanitizes HTML/tags
```

---

## 🚨 Error Handling

All endpoints return standard JSON:
```json
{
  "success": true/false,
  "message": "Human readable message",
  "data": {}
}
```

HTTP Status Codes:
- `200` - Success
- `201` - Created
- `400` - Bad request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not found
- `500` - Server error

---

## 📊 Database Quick Reference

### Get Farmers with Rating
```sql
SELECT u.id, u.name, AVG(fr.rating) as avg_rating, COUNT(p.id) as products_count
FROM users u
LEFT JOIN farmer_reviews fr ON u.id = fr.farmer_id
LEFT JOIN products p ON u.id = p.farmer_id
WHERE u.role = 'farmer' AND u.approved = 1
GROUP BY u.id;
```

### Get User's Notifications
```sql
SELECT * FROM notifications 
WHERE user_id = 5 
ORDER BY created_at DESC;
```

### Get Active Subscriptions
```sql
SELECT * FROM subscriptions 
WHERE user_id = 5 AND is_active = 1 AND expires_at > NOW();
```

---

## 🐛 Debugging Tips

1. **Check Response Format**
   ```javascript
   const res = await API.someFunctionCall();
   console.log(res); // Should have: success, message, data
   ```

2. **Check Database**
   ```sql
   -- Check if data exists
   SELECT * FROM farmers;
   SELECT * FROM notifications WHERE user_id = 5;
   SELECT * FROM subscriptions WHERE is_active = 1;
   ```

3. **Check Server Logs**
   - Apache error log: `C:\xampp\apache\logs\error.log`
   - MySQL log: `C:\xampp\mysql\data\farmconnect.err`

4. **Enable Verbose Logging**
   - Modify `config.php` to set `display_errors = 1` (dev only)

---

## 📈 Performance Tips

1. **Pagination**: Always paginate large result sets
   ```javascript
   API.getNotifications(20, 0); // 20 items per page
   ```

2. **Lazy Load**: Load farmer profiles only when needed
   ```javascript
   API.getFarmerProfile(farmerId); // Only on profile view
   ```

3. **Cache**: Frontend can cache farmer data
   ```javascript
   const cache = {};
   cache[farmerId] = profile; // Avoid repeated API calls
   ```

4. **Index Queries**: All frequently queried columns are indexed

---

## 🎯 Common Workflows

### Customer Adds Product to Cart
```javascript
1. API.addToCart(productId, quantity)
2. ✅ Product added to cart
3. ✅ Farmer gets notification
4. Notification shows: "Customer added Qty x ProductName to cart"
```

### Farmer Uploads Certificate
```javascript
1. API.addCertificate(formData)
2. ✅ Certificate uploaded (pending verification)
3. Admin reviews in admin panel
4. API.verifyCertificate(certId, isVerified)
5. ✅ Farmer gets notification (approved/rejected)
6. ✅ Verified badge appears on farmer profile
```

### Customer Subscribes to Plan
```javascript
1. API.subscribe('premium')
2. ✅ Subscription created for 30 days
3. ✅ 15% discount applied to all products
4. ✅ Notification: "You are now a Premium subscriber"
5. API.getPlan() shows current subscription + available plans
```

---

## 🔗 Important URLs

```
Frontend Home:      http://localhost/farmconnect/frontend/index.html
Admin Panel:        http://localhost/farmconnect/frontend/admin.html
Farmer Dashboard:   http://localhost/farmconnect/frontend/farmer.html
Customer Dashboard: http://localhost/farmconnect/frontend/customer.html

API Base:           http://localhost/farmconnect/backend
Database:           http://localhost/phpmyadmin
```

---

**Last Updated**: May 7, 2026  
**Version**: 1.0  
**Status**: ✅ Production Ready
