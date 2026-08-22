-- SpaceShare — Seed data (run after schema.sql)
USE spaceshare;

INSERT INTO roles (id, name) VALUES (1, 'admin'), (2, 'user');

INSERT INTO categories (name, slug, icon) VALUES
  ('Warehouse',    'warehouse',    'bi-box-seam'),
  ('Garage',       'garage',       'bi-car-front'),
  ('Room',         'room',         'bi-door-closed'),
  ('Shop',         'shop',         'bi-shop'),
  ('Office',       'office',       'bi-briefcase'),
  ('Event Space',  'event-space',  'bi-calendar-event'),
  ('Storage Unit', 'storage-unit', 'bi-archive'),
  ('Parking',      'parking',      'bi-p-square');

INSERT INTO amenities (name, icon) VALUES
  ('CCTV',            'bi-camera-video'),
  ('Vehicle Access',  'bi-truck'),
  ('24/7 Access',     'bi-clock'),
  ('Power Backup',    'bi-lightning-charge'),
  ('WiFi',            'bi-wifi'),
  ('Security Guard',  'bi-shield-check'),
  ('Climate Control', 'bi-thermometer-half'),
  ('Loading Dock',    'bi-box-arrow-in-down'),
  ('Shelving',        'bi-bookshelf'),
  ('Fire Safety',     'bi-fire');

INSERT INTO purposes (name, slug) VALUES
  ('Storage',             'storage'),
  ('Pop-up Shop',         'pop-up-shop'),
  ('Workshop',            'workshop'),
  ('Event',               'event'),
  ('Temporary Office',    'temporary-office'),
  ('Parking',             'parking'),
  ('Inventory Overflow',  'inventory-overflow'),
  ('Moving / Relocation', 'moving-relocation');

INSERT INTO commission_settings (percent, effective_from) VALUES (10.00, CURDATE());

-- Admin account: create via CLI so the password goes through password_hash():
--   php database/seed_admin.php
