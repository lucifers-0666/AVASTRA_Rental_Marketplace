# SpaceShare — Flexible Space Utilization & Time-Based Rental Marketplace
### MCA PHP Project — Full Specification (Replaces ParkFlow)

## 1. Project Definition
**Project Name:** SpaceShare — Flexible Space Utilization & Time-Based Rental Marketplace

**One-line definition:** A web platform where people or businesses can list unused space and rent it to other users for a flexible period (day/week/month/custom), matched by purpose, size, location, availability, and budget.

**Core idea:** Unused space → flexible duration → requirement matching → booking → income.

**Not this:** A standard "Add Property → Search Property → Contact Owner" real-estate CRUD clone. The differentiators are:
1. Flexible duration (1 day to 6+ months, custom dates)
2. Requirement-based search ("I need 300 sq.ft. for storage for 20 days")
3. Availability management (system knows exact open/booked date ranges)
4. Purpose-based matching (storage, office, event, workshop, pop-up shop)
5. Booking conflict prevention (no overlapping bookings)
6. Flexible pricing (daily/weekly/monthly/custom)

## 2. Roles
- **Visitor** — browse/search public listings, no login required; cannot book/list/pay/review.
- **Registered User** — single account can act as both **Space Seeker** (rents) and **Space Owner** (lists), no need for separate registration flows.
- **Admin** — verifies listings, manages users/bookings/payments/complaints/reviews/categories/commission/reports/audit logs.

## 3. Tech Stack
- **Backend:** Core PHP 8.x + OOP PHP, PDO, sessions, REST-style endpoints, server-side validation (no framework required unless college permits)
- **Database:** MySQL 8.x
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript, AJAX/Fetch API (pick one CSS framework — Bootstrap, not Bootstrap+Tailwind)
- **Maps:** Leaflet + OpenStreetMap (avoids Google Maps billing complexity)
- **Charts:** Chart.js (admin dashboard)
- **Payment:** Razorpay (optional layer) + Cash/Pay Later fallback so payment integration isn't a demo blocker
- **Extras:** Flatpickr (date picker), DataTables (admin tables), SweetAlert
- **Server:** XAMPP (dev), Apache+PHP+MySQL (deployment)
- **Version control:** Git + GitHub, with branches for two-person team

## 4. System Architecture
```
Visitor / Space Seeker / Space Owner
              │
   HTML + Bootstrap + JS (Frontend)
              │
        PHP Backend (Core PHP OOP)
              │
   ┌─────────┐─────────┐
   │          │          │
 MySQL   File Storage    APIs
                          │
                     Maps / Payment
```

## 5. Page Structure (~40–49 screens, reusable templates encouraged)

### Visitor / Public (8–10 pages)
1. Home (`index.php`) — hero search, popular locations, categories, how it works, featured spaces
2. Browse Spaces (`spaces.php`) — filters: city, area, price, size, type, purpose, availability, amenities
3. Space Details (`space-details.php`) — images, owner, size, description, amenities, rules, price, availability calendar, map, reviews, booking CTA
4. Search Results (merge into spaces.php with dynamic filters)
5. How It Works (`how-it-works.php`)
6. List Your Space (`list-space.php`) — CTA to register as owner
7. About (`about.php`)
8. Contact (`contact.php`)
9. FAQ (`faq.php`)
10. Login/Register (`login.php`, `register.php`)

### Registered User — Shared Dashboard
- **Dashboard** (`dashboard.php`) — My Bookings, Active Rentals, My Spaces, Pending Requests, Earnings summary

### Space Seeker (8–10 screens)
1. Search Spaces (`search.php`) — advanced filters
2. Space Details (shared with visitor)
3. Booking Request (`booking.php`) — dates, duration, purpose, auto price calc
4. Booking Confirmation (`booking-confirmation.php`) — price, deposit, platform fee, total
5. My Bookings (`my-bookings.php`) — Pending/Confirmed/Active/Completed/Cancelled tabs
6. Booking Details (`booking-details.php`) — status timeline
7. Payments (`payments.php`)
8. Reviews (`review.php`) — post-completion rating

### Space Owner (7–9 screens)
1. My Spaces (`my-spaces.php`) — Active/Pending/Rejected/Inactive
2. Add Space (`add-space.php`) — basic info, location (lat/lng), pricing (daily/weekly/monthly), availability window, amenities
3. Edit Space (`edit-space.php`)
4. Space Availability (`availability.php`) — calendar, block dates
5. Booking Requests (`booking-requests.php`) — accept/reject
6. Owner Bookings (`owner-bookings.php`)
7. Earnings (`earnings.php`) — revenue, pending payout, completed bookings
8. Owner Reviews (`owner-reviews.php`)

### Admin (10–12 screens)
1. Dashboard (`admin/dashboard.php`) — totals, charts (bookings/revenue by month, popular categories, top locations)
2. User Management (`admin/users.php`)
3. Space Management (`admin/spaces.php`)
4. Space Verification (`admin/space-verification.php`) — approve/reject listings
5. Booking Management (`admin/bookings.php`)
6. Payment Management (`admin/payments.php`)
7. Complaints/Disputes (`admin/complaints.php`)
8. Reviews Moderation (`admin/reviews.php`)
9. Categories/Amenities (`admin/categories.php`)
10. Pricing/Commission Settings (`admin/settings.php`)
11. Reports (`admin/reports.php`)
12. Audit Logs (`admin/audit-logs.php`) — who approved/changed/blocked what and when

## 6. Database — ~15–20 Tables
`users`, `roles`, `spaces`, `space_images`, `space_amenities`, `space_availability`, `categories`, `bookings`, `booking_details`, `payments`, `reviews`, `complaints`, `notifications`, `favorites`, `addresses`, `transactions`, `commission_settings`, `audit_logs`, `admin_actions`, `password_resets`

**Key relationships:**
- User → Spaces (one owner, many spaces)
- Space → Images (one space, many images)
- Space → Availability (one space, many date ranges)
- User → Bookings (one user, many bookings)
- Space → Bookings (one space, many bookings over time)
- Booking → Payment(s)
- Completed Booking → Review

## 7. Core Backend Logic (What Makes This More Than CRUD)

### A. Booking Conflict Detection
Overlap check for date ranges:
```
Existing Start < New End  AND  Existing End > New Start  →  CONFLICT
```
This is one of the most important pieces of logic in the project.

### B. Flexible Pricing Engine
Given daily/weekly/monthly rates, compute the best applicable combination for a custom duration (e.g., 12 days = 1 week package + 5 daily days). Avoid advanced dynamic/seasonal pricing in V1.

### C. Requirement-Based Matching Engine (the signature "unique" feature)
User submits: space type, location, size range, dates, budget, required amenities.
System scores each candidate space using a transparent, rule-based weighted formula:
- Location: 20%
- Size: 20%
- Purpose: 20%
- Availability: 20%
- Budget: 10%
- Amenities: 10%

Output: a **match percentage** per space (e.g., "Match: 96%"), not just a filtered list. Keep this rule-based and explainable — do not overcomplicate with opaque AI/ML, since a transparent formula is easier to defend in viva.

## 8. Key User Journeys

**Seeker:** Visitor → Search → Space Details → Register/Login → Select Dates → Booking Request → Owner Approval → Payment → Booking Confirmed → Rental Period → Completion → Review

**Owner:** Register → Create Space → Upload Photos → Submit for Verification → Admin Approval → Space Published → Receive Booking Request → Accept → Customer Pays → Rental → Income

**Admin:** Login → Check Pending Spaces → Verify Owner → Approve Space → Monitor Bookings/Payments → Handle Complaints → Generate Reports

## 9. Team Division (2-person team)
- **Person A (Backend + Core Logic):** PHP architecture, MySQL schema, authentication, booking engine, date-conflict detection, matching engine, pricing calculation, payment status, security, AJAX/APIs, admin business logic.
- **Person B (Frontend + User/Owner Modules):** Figma design, Bootstrap UI, homepage, listing/search UI, space details, owner dashboard, booking screens, responsive design, JS interactions, then integrates with backend.
- **Shared:** DB schema understanding, auth flow, booking logic, user flow, security, final demo — both must be able to explain the other's code in viva.

## 10. Security Requirements
- Passwords: `password_hash()` / `password_verify()`
- SQL: PDO prepared statements only — never raw string concatenation
- File uploads: validate type, size, filename; block arbitrary PHP uploads
- Sessions: secure login, role checks, session regeneration
- Access control: `/admin/...` routes must enforce role checks, not just hide links

## 11. Explicitly Out of Scope
No React, Node.js, Laravel (unless required), microservices, Docker/Kubernetes, AI chatbot, blockchain, or complex recommendation AI. Core PHP done properly is sufficient and appropriate for MCA level.

## 12. Suggested Folder Structure
```
spaceshare/
├── public/        (index.php, login.php, register.php, spaces.php, space-details.php)
├── user/          (dashboard.php, bookings.php, payments.php, profile.php)
├── owner/         (dashboard.php, add-space.php, my-spaces.php, availability.php, requests.php, earnings.php)
├── admin/         (dashboard.php, users.php, spaces.php, bookings.php, payments.php, complaints.php, reports.php)
├── config/        (database.php)
├── includes/      (header.php, footer.php, auth.php, functions.php)
├── assets/        (css/, js/, images/)
└── uploads/
```

## 13. Phased Scope

**MVP:** Registration, login, space listing, search, space details, availability, booking, owner approval, payment status, admin, reviews.

**V2:** Smart matching engine, map search, favorites, notifications, cancellation/refund.

**V3:** Dynamic pricing, owner verification, digital agreement, QR check-in/out, automated invoices.

**V4 (startup-level, optional):** Business accounts, recurring rental, corporate booking, public API, demand prediction, "unused-space earning calculator."

## 14. Demo Script (5–7 minutes)
1. Login as owner → create a 300 sq.ft. storage space listing.
2. Admin approves the listing.
3. Login as customer → search: 300 sq.ft., storage, 15 days.
4. System shows match results with percentage score (e.g., "Match: 96%").
5. Customer selects dates → system auto-calculates rental + fees.
6. Booking confirmed.
7. Owner dashboard shows booking received + expected earnings.

## 15. Recommended Add-On Feature
**"Tell us what space you need"** — a structured requirement form (space type, location, area range, dates, budget, required amenities) that feeds directly into the matching engine, rather than only offering a passive browse/filter experience. This is the feature that most clearly differentiates SpaceShare from a standard property listing site.

## 16. One-Paragraph Summary (for teammate/viva)
"We are building a marketplace where unused space becomes a rentable resource. Owners can monetize rooms, garages, warehouses, shops, or other usable spaces for flexible periods. Renters can tell the system what kind of space they need, where, for how many days, for what purpose, and within what budget. Our PHP system finds suitable available spaces, prevents double booking, calculates the rental price, manages payment, and keeps the complete booking history."

---
*This document replaces the earlier ParkFlow (Smart Car Parking System) specification, which has been discontinued. Stored 2026-08-20 as the canonical reference for the SpaceShare MCA PHP project, hosted in this repository (originally created under the ParkFlow name).*
