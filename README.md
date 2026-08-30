# AVASTRA — Flexible Space Utilization & Time-Based Rental Marketplace

<div align="center">

![AVASTRA Banner](assets/images/PHP%20LOGO/transparent-logo.svg)

### *OPEN TO WHAT MATTERS.*

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Database](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/Academic-MCA%20Capstone-1B5E3A?style=flat-square)]()

</div>

---

## 📌 Project Overview

**AVASTRA** is an India-first flexible space utilization and rental marketplace. It bridges the gap between space owners with underutilized or vacant properties (warehouses, garages, office desks, vacant retail shops, storage units, event venues, studios) and space seekers who require space for custom, flexible durations—whether daily, weekly, monthly, or exact date ranges—tailored to specific operational purposes.

Unlike traditional real-estate portals, AVASTRA focuses on **time-based space sharing**, **requirement-based matching**, **booking conflict prevention**, and **transparent pricing**.

---

## ✨ Key Features & Differentiators

1. **Flexible Time-Based Rental Durations:** Rent space by the hour, day, week, month, or custom date ranges.
2. **Requirement-Based Search & Matching Engine:** Rule-based algorithm evaluating location, size, purpose, date availability, budget, and amenities to yield a transparent Match Percentage.
3. **Real-Time Booking Conflict Prevention:** Server-side SQL date overlap algorithm ensuring zero double-bookings:
   $$\text{existing\_start} < \text{new\_end} \quad \text{AND} \quad \text{existing\_end} > \text{new\_start}$$
4. **Purpose-Based Space Categorization:** Support for office desks, recording studios, meeting rooms, storage, logistics warehouses, event pop-ups, and workshops.
5. **Figma-Aligned Operational Control Center:** Admin portal featuring a clean white sidebar layout, light-green brand palette (`#1B5E3A`, `#E7F5EC`), `DM Serif Display` editorial typography, Chart.js metrics, and comprehensive moderation workflows.

---

## 🛠️ Technology Stack

* **Backend Logic:** Core PHP 8.x (Object-Oriented Architecture), PDO (Prepared Statements), Native Session Authentication.
* **Database Management:** MySQL 8.x (18 relational tables with bcrypt security hashing).
* **Presentation Layer:** HTML5, CSS3 (AVASTRA Centralized Design Tokens), Bootstrap 5, Vanilla JavaScript.
* **Libraries & Visualizations:** Chart.js (Data Analytics), DataTables, Leaflet.js / OpenStreetMap, SweetAlert2.

---

## 👥 User Roles & Access Control

* **Visitor (Public):** Browse and search verified space listings, filter by category/city/price, view space detail pages, access platform guides.
* **Registered User (Unified Account):** Dual-capability account allowing a user to act seamlessly as both a **Space Seeker** (rents spaces) and a **Space Owner** (lists properties).
* **Administrator:** Master oversight panel to approve/reject space listings, verify owner accounts, manage bookings, track payments/payouts, resolve customer dispute tickets, configure commission rates, and audit system activity logs.

---

## 📂 Repository Architecture

```text
AVASTRA_Rental_Marketplace/
├── admin/                     # Admin Operational Control Center
│   ├── includes/              # Sidebar, Header, Navbar, Footer partials
│   ├── dashboard.php          # 6 KPI cards, Needs Attention queue, Chart.js trends
│   ├── verify-spaces.php      # Listing verification queue & approval modals
│   ├── users.php              # User account management & status toggles
│   ├── owners.php             # Space owner directory & revenue stats
│   ├── spaces.php             # Master marketplace space directory
│   ├── bookings.php           # Reservation duration manager
│   ├── payments.php           # Financial transactions & platform fee tracker
│   ├── complaints.php         # Customer dispute & issue tickets
│   ├── reviews.php            # Customer reviews moderation
│   ├── analytics.php          # Marketplace insights & dual-axis charts
│   ├── notifications.php      # System notifications center
│   ├── settings.php           # Commission %, deposit %, & support settings
│   └── audit-logs.php         # Immutable admin activity audit trail
├── assets/                    # Static CSS, JS, SVG & Image assets
│   ├── css/admin.css          # AVASTRA Centralized CSS Tokens & Components
│   └── images/PHP LOGO/       # Official AVASTRA transparent SVG logo assets
├── classes/                   # OOP PHP Models
│   ├── Database.php           # Singleton PDO Connection Handler
│   ├── Auth.php               # Session Authentication & Authorization Guards
│   └── Admin.php              # Optimized Admin database queries
├── config/                    # Global Configuration & Environment Constants
│   └── database.php           # Database credentials & APP_URL constants
├── db/                        # Database SQL Scripts
│   └── schema.sql             # 18-Table MySQL Schema & Seed Data
└── README.md                  # Project Documentation
```

---

## ⚡ Local Environment Setup Instructions

### 1. Prerequisites
* XAMPP / WAMP / MAMP (PHP 8.x + MySQL 8.x / MariaDB).
* Web Server (Apache).

### 2. Database Installation
1. Start Apache & MySQL in XAMPP.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL Command Line.
3. Create database `spaceshare_db`:
   ```sql
   CREATE DATABASE spaceshare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Import `db/schema.sql` into `spaceshare_db`.

### 3. Application Execution
1. Link project directory to XAMPP web root `htdocs`:
   `E:\xampp\htdocs\AVASTRA_Rental_Marketplace` -> `e:\AVASTRA-MCA-P1\AVASTRA_Rental_Marketplace`
2. Open browser and access the Admin Login Portal:
   👉 **[http://localhost/AVASTRA_Rental_Marketplace/admin/login.php](http://localhost/AVASTRA_Rental_Marketplace/admin/login.php)**

### 🔑 Default Admin Login Credentials
* **Email:** `admin@spaceshare.com`
* **Password:** `admin123`

---

## 📄 License & Academic Note

This project is developed as part of an **MCA Capstone Project**. All rights reserved.

© 2026 **AVASTRA**. *OPEN TO WHAT MATTERS.*
