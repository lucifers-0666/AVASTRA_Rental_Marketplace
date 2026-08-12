# ParkFlow — Smart Parking Reservation & Management Platform
### MCA PHP Project — Full Specification

## 1. Project Definition
**Project Name:** ParkFlow — Smart Car Parking Management & Reservation System

**Description:** A web-based smart parking platform that combines real-time slot availability, intelligent slot recommendation, dynamic pricing, QR-based parking access, EV charging information, occupancy prediction, digital tickets and centralized parking management.

**Main objective — Users can:**
- Find nearby parking locations
- Check real-time slot availability
- Compare parking prices
- Get smart slot recommendations
- Book a parking slot
- Pay online
- Receive a digital parking ticket
- Enter using QR code
- Manage vehicles
- View booking history
- Rate parking facilities

**Admin manages:** Parking locations, parking slots, users, bookings, pricing, EV charging facilities, reports, reviews.

## 2. System Architecture
Three application views: **Guest**, **User**, **Admin** — all backed by a PHP backend and MySQL database.

## 3. Page Structure (~35 pages total)

### Guest View (7 pages)
1. Home (`/`)
2. Parking Search (`/parking`)
3. Parking Details (`/parking/{id}`)
4. Login (`/login`)
5. Registration (`/register`)
6. About / How It Works (`/about`)
7. Contact (`/contact`)

### User View (15 pages)
1. Dashboard (`/dashboard`)
2. Find Parking (`/user/find-parking`)
3. Parking Details (`/user/parking/{id}`)
4. Slot Selection (`/user/parking/{id}/slots`) — implements Smart Slot Recommendation
5. Booking Confirmation (`/user/booking`)
6. Payment (`/user/payment`)
7. Booking Success (`/user/booking/success`)
8. Digital Ticket (`/user/ticket/{id}`) — QR code
9. My Bookings (`/user/bookings`) — tabs: Upcoming/Active/Completed/Cancelled
10. Booking Details (`/user/bookings/{id}`)
11. My Vehicles (`/user/vehicles`)
12. Favorites (`/user/favorites`)
13. Reviews & Ratings (`/user/reviews`)
14. Profile & Settings (`/user/profile`)
15. Notifications

### Admin Panel (13 pages)
1. Admin Login (`/admin/login`)
2. Admin Dashboard (`/admin/dashboard`) — KPIs, charts (booking trends, occupancy, peak hours)
3. User Management (`/admin/users`)
4. Parking Management (`/admin/parking`)
5. Add/Edit Parking (`/admin/parking/create`)
6. Parking Details / Manage Slots (`/admin/parking/{id}`)
7. Slot Management (`/admin/slots`)
8. Booking Management (`/admin/bookings`)
9. Dynamic Pricing (`/admin/pricing`)
10. EV Charging Management (`/admin/ev-charging`)
11. Reviews Management (`/admin/reviews`)
12. Reports & Analytics (`/admin/reports`) — export PDF/CSV
13. Admin Settings (`/admin/settings`)

## 4. Database Structure (~15–18 tables)
users, roles, vehicles, parking_locations, parking_slots, slot_types, bookings, booking_items, payments, parking_tickets, pricing_rules, ev_chargers, favorites, reviews, notifications, contact_messages, admin_logs, (optional: password_resets)

**Core relationships:**
- USER → VEHICLES, BOOKINGS, FAVORITES, REVIEWS
- BOOKING → PARKING_SLOT → PARKING_LOCATION; BOOKING → PAYMENT; BOOKING → PARKING_TICKET
- PARKING_LOCATION → PARKING_SLOTS → EV_CHARGER; PARKING_LOCATION → PRICING_RULES

Recommended: keep **Parking Owner** as a DB role even without a dedicated UI, for future extensibility.

## 5. Key Business Algorithms

**Algorithm 1 — Slot Availability:** Available = Total − Occupied − Reserved, calculated against requested date/time (not a static flag).

**Algorithm 2 — Smart Slot Recommendation:**
Recommendation Score = Distance Score + Price Score + Availability Score + Rating Score + Vehicle Compatibility Score → sort descending → top 3 recommended slots.

**Algorithm 3 — Dynamic Pricing:**
Final Price = Base Price × Rule Multiplier (Peak 1.5x, Weekend 1.3x, High Demand 1.7x). Rule chosen based on time, day, demand, or special event. Keep rule-based, not overly complex.

**Algorithm 4 — Occupancy Prediction:** Start with historical-average based prediction per hour/day (e.g., "Expected occupancy at 10 AM: ~75%"); basic ML model optional later — don't over-claim AI.

## 6. QR Parking Workflow
Book → Pay → Confirm → Generate digital ticket → Generate unique QR → Scan at entry → Verify booking → Allow entry → Slot status = Occupied → (Exit) Scan/verify → Record exit → Slot = Available → Booking = Completed.

## 7. Suggested Project Structure (MVC-style, Core PHP)
```
parkflow/
├── public/            (index.php, login.php, assets/)
├── config/            (database.php, config.php)
├── controllers/       (AuthController, ParkingController, BookingController, PaymentController, VehicleController, AdminController)
├── models/            (User, Parking, ParkingSlot, Booking, Payment, Vehicle, Review)
├── views/             (guest/, user/, admin/)
├── services/          (BookingService, PricingService, RecommendationService, QRService, EmailService)
├── middleware/        (AuthMiddleware, AdminMiddleware, GuestMiddleware)
├── uploads/
└── database/          (parkflow.sql)
```
If Laravel is allowed: prefer **Laravel + MySQL** for proper routing, middleware, validation, ORM, and auth structure.

## 8. Technology Stack
- **Backend:** Core PHP (or Laravel if permitted)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (Bootstrap or Tailwind for UI)
- **Payment:** Sandbox/test payment gateway (e.g., Razorpay/Stripe test mode) — no real transactions for the college submission
- **QR Code:** PHP QR code library (e.g., `phpqrcode` or `endroid/qr-code` if using Composer/Laravel)
- **Maps:** Google Maps API or OpenStreetMap/Leaflet for location display
- **PDF Export:** `mPDF` or `TCPDF` for ticket/report downloads
- **Version Control:** Git + GitHub

## 9. Phased Scope

**V1 — Mandatory:** Auth, parking search, parking details, maps, slot management, real-time availability, booking, payment sandbox, QR ticket, user dashboard, admin dashboard, user management, parking management, booking management, reports.

**V2 — Innovation:** Smart slot recommendation, dynamic pricing, EV charging, occupancy prediction, favorites, reviews, vehicle management, notifications.

**V3 — Bonus (only if time permits):** Loyalty points, coupons, camera simulation, advanced analytics, AI chatbot, number-plate recognition.

## 10. Contributors
- Repo owner: lucifers-0666
- To be added as collaborators: `ukargathra2630`, `19JayPatel`

---
*Document generated 2026-08-12 as the canonical reference for the ParkFlow MCA PHP project.*
