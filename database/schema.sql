-- =============================================================
-- SpaceShare — Database Schema (V1)
-- MySQL 8.x · InnoDB · utf8mb4
-- Import first, then database/seed.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS spaceshare
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE spaceshare;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS commission_settings;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS space_availability;
DROP TABLE IF EXISTS space_purposes;
DROP TABLE IF EXISTS space_amenities;
DROP TABLE IF EXISTS space_images;
DROP TABLE IF EXISTS spaces;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS purposes;
DROP TABLE IF EXISTS amenities;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Users & access ----------

CREATE TABLE roles (
  id   TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id           TINYINT UNSIGNED NOT NULL DEFAULT 2,
  name              VARCHAR(100) NOT NULL,
  email             VARCHAR(150) NOT NULL UNIQUE,
  phone             VARCHAR(15)  DEFAULT NULL,
  password_hash     VARCHAR(255) NOT NULL,
  status            ENUM('active','suspended') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME DEFAULT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pr_user (user_id),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Lookups ----------

CREATE TABLE categories (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(50) NOT NULL UNIQUE,
  slug      VARCHAR(60) NOT NULL UNIQUE,
  icon      VARCHAR(50) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE amenities (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  icon VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE purposes (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  slug VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------- Spaces ----------

CREATE TABLE addresses (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  address_line1 VARCHAR(150) NOT NULL,
  address_line2 VARCHAR(150) DEFAULT NULL,
  city          VARCHAR(80) NOT NULL,
  state         VARCHAR(80) NOT NULL,
  pincode       VARCHAR(10) NOT NULL,
  latitude      DECIMAL(10,7) DEFAULT NULL,
  longitude     DECIMAL(10,7) DEFAULT NULL,
  INDEX idx_addr_city  (city),
  INDEX idx_addr_state (state)
) ENGINE=InnoDB;

CREATE TABLE spaces (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id          INT UNSIGNED NOT NULL,
  category_id       INT UNSIGNED NOT NULL,
  address_id        INT UNSIGNED NOT NULL,
  title             VARCHAR(120) NOT NULL,
  description       TEXT DEFAULT NULL,
  size_sqft         DECIMAL(10,2) NOT NULL,
  rate_daily        DECIMAL(10,2) NOT NULL,
  rate_weekly       DECIMAL(10,2) DEFAULT NULL,  -- NULL => priced as 7 x daily
  rate_monthly      DECIMAL(10,2) DEFAULT NULL,  -- NULL => priced as 30 x daily
  security_deposit  DECIMAL(10,2) NOT NULL DEFAULT 0,
  min_booking_days  INT UNSIGNED NOT NULL DEFAULT 1,
  max_booking_days  INT UNSIGNED DEFAULT NULL,
  status            ENUM('draft','pending_verification','published','rejected','suspended') NOT NULL DEFAULT 'draft',
  rejection_reason  VARCHAR(255) DEFAULT NULL,
  verified_by       INT UNSIGNED DEFAULT NULL,
  verified_at       DATETIME DEFAULT NULL,
  views_count       INT UNSIGNED NOT NULL DEFAULT 0,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_spaces_owner    FOREIGN KEY (owner_id)    REFERENCES users (id),
  CONSTRAINT fk_spaces_category FOREIGN KEY (category_id) REFERENCES categories (id),
  CONSTRAINT fk_spaces_address  FOREIGN KEY (address_id)  REFERENCES addresses (id),
  CONSTRAINT fk_spaces_verifier FOREIGN KEY (verified_by) REFERENCES users (id),
  INDEX idx_spaces_status   (status),
  INDEX idx_spaces_owner    (owner_id),
  INDEX idx_spaces_category (category_id)
) ENGINE=InnoDB;

CREATE TABLE space_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  space_id    INT UNSIGNED NOT NULL,
  file_path   VARCHAR(255) NOT NULL,
  is_primary  TINYINT(1) NOT NULL DEFAULT 0,
  sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_si_space (space_id),
  CONSTRAINT fk_si_space FOREIGN KEY (space_id) REFERENCES spaces (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE space_amenities (
  space_id   INT UNSIGNED NOT NULL,
  amenity_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (space_id, amenity_id),
  CONSTRAINT fk_sa_space   FOREIGN KEY (space_id)   REFERENCES spaces (id)    ON DELETE CASCADE,
  CONSTRAINT fk_sa_amenity FOREIGN KEY (amenity_id) REFERENCES amenities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE space_purposes (
  space_id   INT UNSIGNED NOT NULL,
  purpose_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (space_id, purpose_id),
  CONSTRAINT fk_sp_space   FOREIGN KEY (space_id)   REFERENCES spaces (id)   ON DELETE CASCADE,
  CONSTRAINT fk_sp_purpose FOREIGN KEY (purpose_id) REFERENCES purposes (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Open ranges (is_blocked = 0) define when a space CAN be booked;
-- blocked ranges (is_blocked = 1) carve out exceptions.
-- A space with no open range is not bookable.
CREATE TABLE space_availability (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  space_id   INT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date   DATE NOT NULL,
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  note       VARCHAR(120) DEFAULT NULL,
  INDEX idx_sav_space_dates (space_id, start_date, end_date),
  CONSTRAINT fk_sav_space FOREIGN KEY (space_id) REFERENCES spaces (id) ON DELETE CASCADE,
  CONSTRAINT chk_sav_dates CHECK (end_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE favorites (
  user_id    INT UNSIGNED NOT NULL,
  space_id   INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, space_id),
  CONSTRAINT fk_fav_user  FOREIGN KEY (user_id)  REFERENCES users (id)  ON DELETE CASCADE,
  CONSTRAINT fk_fav_space FOREIGN KEY (space_id) REFERENCES spaces (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Bookings & money ----------

-- Status flow: pending -> approved/rejected -> (payment) confirmed
--              -> active (rental running) -> completed; cancelled anytime before completed.
-- pending/approved/confirmed/active all "hold" the space for conflict checks.
CREATE TABLE bookings (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_ref       VARCHAR(20) NOT NULL UNIQUE,
  space_id          INT UNSIGNED NOT NULL,
  seeker_id         INT UNSIGNED NOT NULL,
  start_date        DATE NOT NULL,
  end_date          DATE NOT NULL,
  total_days        INT UNSIGNED NOT NULL,
  purpose_id        INT UNSIGNED DEFAULT NULL,
  price_months      INT UNSIGNED NOT NULL DEFAULT 0,
  price_weeks       INT UNSIGNED NOT NULL DEFAULT 0,
  price_days        INT UNSIGNED NOT NULL DEFAULT 0,
  base_amount       DECIMAL(10,2) NOT NULL,
  commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  deposit_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount      DECIMAL(10,2) NOT NULL,
  status            ENUM('pending','approved','rejected','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  owner_note        VARCHAR(255) DEFAULT NULL,
  cancel_reason     VARCHAR(255) DEFAULT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_b_space   FOREIGN KEY (space_id)   REFERENCES spaces (id),
  CONSTRAINT fk_b_seeker  FOREIGN KEY (seeker_id)  REFERENCES users (id),
  CONSTRAINT fk_b_purpose FOREIGN KEY (purpose_id) REFERENCES purposes (id),
  INDEX idx_b_space_dates (space_id, start_date, end_date),
  INDEX idx_b_seeker (seeker_id),
  INDEX idx_b_status (status),
  CONSTRAINT chk_b_dates CHECK (end_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id         INT UNSIGNED NOT NULL,
  method             ENUM('razorpay','cash','pay_later') NOT NULL DEFAULT 'cash',
  amount             DECIMAL(10,2) NOT NULL,
  gateway_order_id   VARCHAR(100) DEFAULT NULL,
  gateway_payment_id VARCHAR(100) DEFAULT NULL,
  status             ENUM('created','paid','failed','refunded') NOT NULL DEFAULT 'created',
  paid_at            DATETIME DEFAULT NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pay_booking (booking_id),
  INDEX idx_pay_status  (status),
  CONSTRAINT fk_pay_booking FOREIGN KEY (booking_id) REFERENCES bookings (id)
) ENGINE=InnoDB;

-- Owner earnings ledger (powers the Owner > Earnings page)
CREATE TABLE transactions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED DEFAULT NULL,
  user_id    INT UNSIGNED NOT NULL,
  type       ENUM('earning','commission','refund','payout') NOT NULL,
  amount     DECIMAL(10,2) NOT NULL,
  note       VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tx_user (user_id),
  CONSTRAINT fk_tx_booking FOREIGN KEY (booking_id) REFERENCES bookings (id),
  CONSTRAINT fk_tx_user    FOREIGN KEY (user_id)    REFERENCES users (id)
) ENGINE=InnoDB;

-- ---------- Post-rental & moderation ----------

CREATE TABLE reviews (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id  INT UNSIGNED NOT NULL UNIQUE,  -- one review per completed booking
  space_id    INT UNSIGNED NOT NULL,
  reviewer_id INT UNSIGNED NOT NULL,
  owner_id    INT UNSIGNED NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,
  comment     TEXT DEFAULT NULL,
  owner_reply TEXT DEFAULT NULL,
  status      ENUM('published','hidden','flagged') NOT NULL DEFAULT 'published',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rev_space (space_id),
  CONSTRAINT fk_rev_booking  FOREIGN KEY (booking_id)  REFERENCES bookings (id),
  CONSTRAINT fk_rev_space    FOREIGN KEY (space_id)    REFERENCES spaces (id),
  CONSTRAINT fk_rev_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id),
  CONSTRAINT fk_rev_owner    FOREIGN KEY (owner_id)    REFERENCES users (id),
  CONSTRAINT chk_rev_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE complaints (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id      INT UNSIGNED DEFAULT NULL,
  raised_by       INT UNSIGNED NOT NULL,
  against_user    INT UNSIGNED DEFAULT NULL,
  subject         VARCHAR(120) NOT NULL,
  description     TEXT NOT NULL,
  status          ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  resolution_note VARCHAR(255) DEFAULT NULL,
  handled_by      INT UNSIGNED DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_comp_status (status),
  CONSTRAINT fk_comp_booking FOREIGN KEY (booking_id)   REFERENCES bookings (id),
  CONSTRAINT fk_comp_raiser  FOREIGN KEY (raised_by)    REFERENCES users (id),
  CONSTRAINT fk_comp_against FOREIGN KEY (against_user) REFERENCES users (id),
  CONSTRAINT fk_comp_handler FOREIGN KEY (handled_by)   REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  type       VARCHAR(40) NOT NULL,
  title      VARCHAR(120) NOT NULL,
  message    VARCHAR(255) NOT NULL,
  link       VARCHAR(255) DEFAULT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_user_read (user_id, is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Admin ----------

CREATE TABLE commission_settings (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  percent        DECIMAL(5,2) NOT NULL,
  effective_from DATE NOT NULL,
  created_by     INT UNSIGNED DEFAULT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cs_admin FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;

-- Covers admin_actions: every privileged action is an audit row
-- (action e.g. 'space.approved', 'user.suspended', 'booking.cancelled').
CREATE TABLE audit_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id    INT UNSIGNED DEFAULT NULL,
  action      VARCHAR(60) NOT NULL,
  entity_type VARCHAR(40) DEFAULT NULL,
  entity_id   INT UNSIGNED DEFAULT NULL,
  meta        JSON DEFAULT NULL,
  ip_address  VARCHAR(45) DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_actor  (actor_id),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users (id)
) ENGINE=InnoDB;
