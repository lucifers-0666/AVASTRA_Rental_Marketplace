# SpaceShare — Flexible Space Utilization & Time-Based Rental Marketplace

> **Note:** This repository originally hosted the *ParkFlow* (Smart Car Parking System) project. That idea has been discontinued and replaced with **SpaceShare**. See [`PROJECT_SPEC.md`](./PROJECT_SPEC.md) for the full specification.

## What is SpaceShare?

SpaceShare is a PHP + MySQL web marketplace where people or businesses can list **unused space** (warehouses, garages, shops, offices, storage rooms, event venues, etc.) and rent it out to others for a **flexible duration** — a day, a week, a month, or any custom range — based on the seeker's purpose, required size, location, availability, and budget.

**Core idea:** Unused space → flexible duration → requirement-based matching → booking → income.

This is **not** a standard property-listing CRUD site. Its distinguishing features are:

1. Flexible rental durations (day/week/month/custom dates)
2. Requirement-based search ("I need 300 sq.ft. for storage for 20 days")
3. Real-time availability management with booking-conflict prevention
4. Purpose-based matching (storage, office, event, workshop, pop-up shop)
5. Flexible, tiered pricing (daily/weekly/monthly)
6. A transparent, rule-based **match-percentage engine** for seeker requirements vs. available spaces

## Roles
- **Visitor** — browse and search public listings, no login required
- **Registered User** — a single account can act as both a **Space Seeker** (rents space) and a **Space Owner** (lists space)
- **Admin** — verifies listings, manages users, bookings, payments, complaints, reviews, categories, commission settings, reports, and audit logs

## Tech Stack
- **Backend:** Core PHP 8.x (OOP), PDO, sessions
- **Database:** MySQL 8.x
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript, AJAX/Fetch API
- **Maps:** Leaflet + OpenStreetMap
- **Charts:** Chart.js
- **Payment:** Razorpay (optional) + Cash/Pay Later fallback
- **Version control:** Git + GitHub

## Documentation
Full project specification — pages, database schema, matching/pricing algorithms, folder structure, team division, security requirements, and phased scope — is in [`PROJECT_SPEC.md`](./PROJECT_SPEC.md).

## MCA Project Status
This is an academic (MCA) project. Current phase: **planning/spec complete, development starting.**
