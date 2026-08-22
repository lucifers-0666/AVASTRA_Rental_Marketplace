# SpaceShare — Local Setup (XAMPP)

## 1. Clone into htdocs
```bash
git clone https://github.com/lucifers-0666/spaceshare-rental-marketplace.git spaceshare
cd spaceshare
```

## 2. Create the database
Import in phpMyAdmin (or MySQL CLI) in this order:
1. `database/schema.sql` — tables, foreign keys, indexes
2. `database/seed.sql` — roles, categories, amenities, purposes, default 10% commission

## 3. Create the admin account
```bash
php database/seed_admin.php              # default password: Admin@123
php database/seed_admin.php 'MyPass!23'  # custom password
```
Login email: `admin@spaceshare.local`

## 4. Configure
`config/config.php` works out of the box with XAMPP defaults
(DB `spaceshare`, user `root`, empty password, URL `http://localhost/spaceshare`).
To override without touching tracked files, create `config/local.php` (git-ignored):
```php
<?php
define('DB_PASS', 'yourpassword');
define('APP_URL', 'http://localhost/spaceshare');
```

## 5. Run
Start Apache + MySQL in the XAMPP control panel, then open
`http://localhost/spaceshare`.

## Branch workflow
- `main` — stable code only, merged via pull request
- `admin` / `user` / `visitor-design` — one branch per collaborator
- Never push directly to `main`
