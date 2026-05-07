# FarmConnect - API Documentation & Setup Guide

## ✅ Database Migration Complete

All new tables have been created and seed data added:
- `farmer_profiles` - Farmer profile information
- `farmer_certifications` - Farmer certificate management  
- `farmer_gallery` - Farm images and media
- `farmer_reviews` - Farmer ratings and reviews
- `farming_practices` - Farming methods and practices
- `subscriptions` - User subscription plans
- `notifications` - User notifications
- `wishlist` - Product wishlist

---

## 🔐 Test Accounts (Password: `test123`)

```
Admin:    admin@test.com
Farmer:   farmer@test.com  
Customer: customer@test.com
```

To add these, run in MySQL:
```sql
INSERT INTO users (name, email, phone, password, role, approved, is_active) VALUES 
('Test Customer', 'customer@test.com', '9555555555', '$2y$10$hQcvDxJQSQrHVr4hBiKQROj4KhB7MNvDTqNf9X6oQ1S3hB1X8Yh8G', 'customer', 1, 1),
('Test Farmer', 'farmer@test.com', '0987654321', '$2y$10$hQcvDxJQSQrHVr4hBiKQROj4KhB7MNvDTqNf9X6oQ1S3hB1X8Yh8G', 'farmer', 1, 1),
('Test Admin', 'admin@test.com', '5555555555', '$2y$10$hQcvDxJQSQrHVr4hBiKQROj4KhB7MNvDTqNf9X6oQ1S3hB1X8Yh8G', 'admin', 1, 1);
```

---

## 📡 API Endpoints

### Authentication
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `POST /auth/logout.php` - Logout
- `GET /auth/session.php` - Get current session

### Farmers
- `GET /farmers/get_farmers.php` - List all approved farmers with stats
- `GET /farmers/get_profile.php?id=FARMER_ID` - Get detailed farmer profile
- `POST /farmers/update_profile.php` - Update farmer profile (farmers/admin)
- `POST /farmers/add_certificate.php` - Upload certification (farmers)
- `POST /farmers/verify_certificate.php` - Verify certificate (admin)
- `POST /farmers/add_practice.php` - Add farming practice (farmers)
- `POST /farmers/upload_gallery.php` - Upload farm image (farmers)
- `DELETE /farmers/delete_gallery_image.php` - Delete gallery image (farmers)
- `POST /farmers/add_review.php` - Add farmer review (customers)

### Products
- `GET /products/get_products.php` - List products (with filters)
- `POST /products/add_product.php` - Add product (farmers)
- `POST /products/update_product.php` - Update product (farmers)
- `DELETE /products/delete_product.php` - Delete product (farmers)

### Cart & Orders
- `GET /orders/cart.php` - Get user's cart
- `POST /orders/cart.php` - Add product to cart (triggers farmer notification)
- `DELETE /orders/cart.php` - Remove product from cart
- `POST /orders/place_order.php` - Create order
- `GET /orders/get_orders.php` - Get user orders
- `POST /orders/update_status.php` - Update order status (farmers)
- `POST /orders/accept_order.php` - Accept/reject order (farmers)

### Wishlist
- `GET /orders/wishlist.php` - Get user wishlist
- `POST /orders/wishlist.php` - Add to wishlist
- `DELETE /orders/wishlist.php` - Remove from wishlist

### Subscriptions
- `GET /subscriptions/get_plan.php` - Get available plans & current subscription
- `POST /subscriptions/subscribe.php` - Subscribe to plan

Plans available:
```json
{
  "basic": {"price": 99, "discount": 5%, "free_delivery": false, "priority_support": false},
  "premium": {"price": 299, "discount": 15%, "free_delivery": true, "priority_support": true},
  "farmer_support": {"price": 499, "discount": 20%, "free_delivery": true, "priority_support": true}
}
```

### Notifications
- `GET /notifications/get.php?limit=20&offset=0` - Get user notifications
- `POST /notifications/mark_read.php` - Mark notification as read
- `POST /notifications/mark_read.php` (mark_all=true) - Mark all as read

---

## 🚀 Key Features Implemented

### ✅ Farmer Visibility Fix
- Farmers list fetched dynamically from DB
- Shows: name, image, products count, rating, verification status

### ✅ Farmer Profile System
- **Profile info**: bio, location, farm size, farming type, experience
- **Certificates**: PDF/Image uploads with admin verification
- **Farming Practices**: Track methods (fertilizers, pesticides, irrigation, composting)
- **Gallery**: Upload farm photos/videos
- **Reviews**: Customer ratings and reviews
- **Products**: List of farmer's products

### ✅ Cart → Farmer Notifications
- When customer adds product to cart:
  - Farmer gets instant notification
  - Shows product name, quantity, customer name
  - Notification stored in DB with timestamp

### ✅ Subscription Module
- **3 Plans**: Basic, Premium, Farmer Support
- **Benefits**:
  - Discounts on products (5%, 15%, 20%)
  - Free delivery (Premium+)
  - Priority support (Premium+)
- **Auto-expiry**: 30-day subscriptions
- **Notifications**: Subscriber gets welcome notification

### ✅ Admin Features
- View all users (customers, farmers, admins)
- Verify farmer certificates
- Approve/reject farmer registrations
- Manage orders (view, update status)
- View all certifications pending verification

### ✅ Smart Notifications
- **Types**: order, product, farmer, system, promotion
- **Real-time**: User notifications stored in DB
- **Mark read**: Individual or bulk mark as read
- **Timestamps**: Auto-tracked with created_at and read_at

### ✅ Database Enhancements
- Proper relationships with foreign keys
- Indexes for performance
- User addresses and location data
- Product stock tracking
- Order delivery status tracking

---

## 📱 Frontend Integration

All API calls are available in `/frontend/assets/api.js`:

```javascript
// Farmers
API.getFarmers()
API.getFarmerProfile(farmerId)
API.updateFarmerProfile(formData)
API.addCertificate(formData)
API.verifyCertificate(certId, isVerified)
API.addFarmPractice(data)
API.uploadGalleryImages(formData)
API.deleteGalleryImage(imageId)

// Subscriptions
API.subscribe('premium')
API.getPlan()

// Notifications
API.getNotifications(limit, offset)
API.markNotificationRead(notificationId)
API.markAllNotificationsRead()

// Admin
API.approveFarmer(farmerId)
API.rejectFarmer(farmerId)
API.getPendingFarmers()
```

---

## 📂 New File Structure

```
backend/
├── farmers/
│   ├── get_farmers.php (FIXED - now with stats)
│   ├── get_profile.php (FIXED - full profile)
│   ├── update_profile.php (NEW - complete rewrite)
│   ├── add_certificate.php (FIXED)
│   ├── verify_certificate.php (NEW - admin cert verification)
│   ├── add_practice.php (FIXED)
│   ├── upload_gallery.php (FIXED)
│   └── delete_gallery_image.php (FIXED)
├── notifications/
│   ├── get.php (FIXED - better queries)
│   └── mark_read.php (FIXED - with timestamps)
├── subscriptions/
│   ├── get_plan.php (FIXED - shows all plans)
│   └── subscribe.php (FIXED - complete rewrite)
├── orders/
│   ├── cart.php (ENHANCED - sends notifications)
│   └── wishlist.php (NEW - wishlist functionality)
└── config.php (ENHANCED - added sendNotification helper)

database/
├── farmconnect.sql (original)
└── migration_v2.sql (NEW - all new tables)
```

---

## 🧪 Testing Checklist

### Farmers Page
- [ ] Load farmers list from `/frontend/index.html`
- [ ] See farmer names, images, products count, ratings
- [ ] Click farmer to view detailed profile
- [ ] View certifications, practices, gallery, products, reviews

### Cart → Notifications
- [ ] Add product to cart as customer
- [ ] Check farmer's notifications (farmer should see notification)
- [ ] Verify notification includes product name, quantity, customer info

### Subscriptions
- [ ] Login as customer
- [ ] Click "Subscribe"
- [ ] Select plan (Basic/Premium/Farmer Support)
- [ ] Subscribe and see discount applied to cart
- [ ] Receive subscription notification

### Admin Panel
- [ ] Login as admin
- [ ] View pending farmers
- [ ] Upload certificate and verify
- [ ] See verified badge on farmer profile

---

## ⚡ Performance Optimizations

- Indexes on: user_id, farmer_id, is_read, status, created_at
- Foreign keys for referential integrity
- Prepared statements for SQL injection prevention
- Pagination support for large datasets
- Efficient notification queries with LEFT JOINs

---

## 🔒 Security Features

- Password hashing (BCRYPT)
- Session validation on every request
- Role-based access control (requireRole helper)
- Input sanitization (clean function)
- File upload validation (type, size, extension)
- CORS headers configured in config.php

---

## 📝 Next Steps (Optional Enhancements)

1. **Search & Filter**
   - Filter farmers by location, rating, products count
   - Search products by name, category, price range

2. **Chat System**
   - Direct messaging between farmers and customers

3. **Order Tracking**
   - Real-time delivery status
   - GPS tracking for farmers

4. **Payment Integration**
   - Razorpay/Stripe integration for subscriptions
   - Transaction history

5. **Analytics Dashboard**
   - Sales charts for farmers
   - Customer insights for admin

---

## 📞 Support

For issues or questions, check:
1. Error logs in `/uploads/` folder
2. Database integrity in phpMyAdmin
3. API responses in browser developer console
4. Check session is active (API.session())

**All endpoints follow consistent response format:**
```json
{
  "success": true/false,
  "message": "Operation status",
  "data": {}
}
```
