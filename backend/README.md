# 📚 Book Banko - Admin Panel & REST API Backend

A PHP, MySQL, and Bootstrap 5 powered Content Management System & REST API backend built for the **Book Banko** educational app.

---

## 🎨 Theme & Brand Styling
The Admin Panel interface matches the Book Banko Flutter Application UI design system:
- **Font**: Plus Jakarta Sans
- **Primary Colors**: `#0061A4`, `#2196F3`, `#0B3D91`
- **Backgrounds**: Canvas `#F7FAFF`, Light Blue `#F0F6FF`, Container `#EAF6FF`
- **Shadows**: Soft blue tinted elevated card shadows

---

## 🚀 Quick Setup Instructions

### 1. Database Import
1. Start your MySQL server (via XAMPP, WampServer, Laragon, or standalone MySQL).
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Import the database file:
   ```sql
   source d:/Book_Banko/backend/database.sql;
   ```
   *(Or import `database.sql` directly into phpMyAdmin)*

### 2. Database Connection Configuration
Check [`backend/config/database.php`](file:///d:/Book_Banko/backend/config/database.php):
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'book_banko_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Start PHP Server (if not using Apache/XAMPP)
You can run the PHP built-in server inside the `backend` folder:
```bash
cd d:\Book_Banko\backend
php -S localhost:8080
```

### 4. Admin Login Credentials
- **URL**: `http://localhost:8080/auth/login.php` (or `http://localhost/Book_Banko/backend/auth/login.php`)
- **Username**: `admin`
- **Password**: `admin123`

---

## 📂 Architecture & Directory Structure

```
backend/
├── api/                         # RESTful JSON APIs for Flutter
│   ├── get_boards.php           # Active education boards
│   ├── get_standards.php        # Standards 6-12 & stream flags
│   ├── get_streams.php          # Higher secondary streams (Science/Commerce/Arts)
│   ├── get_categories.php       # Home screen learning categories
│   ├── get_modules.php          # Standard dashboard options (Textbooks, PYQs, Blueprint, M.IMP)
│   ├── get_subjects.php         # Filterable by board, standard, stream
│   ├── get_chapters.php         # Chapters with PDF links & page counts
│   ├── get_chapter_detail.php   # Single chapter details & view counter
│   ├── get_app_config.php       # App version, banners, announcements & maintenance
│   └── search.php               # Global search across subjects & chapters
├── assets/
│   └── css/custom.css           # Book Banko custom theme styling
├── auth/
│   ├── login.php                # Admin authentication
│   ├── logout.php               # Session termination
│   └── profile.php              # Admin profile & password change
├── config/
│   ├── constants.php            # App paths, URLs, brand colors
│   ├── database.php             # PDO database connection
│   └── helpers.php              # Auth, CSRF, file upload & JSON formatters
├── includes/
│   ├── header.php               # Head metadata & stylesheets
│   ├── navbar.php               # Top navigation & user dropdown
│   ├── sidebar.php              # Collapsible brand sidebar
│   └── footer.php               # Scripts, DataTables, & responsive handlers
├── modules/
│   ├── dashboard/               # Live metrics, quick shortcuts, recent uploads
│   ├── boards/                  # CRUD for GSEB, CBSE, NCERT, ICSE
│   ├── standards/               # CRUD for Standards 6 to 12
│   ├── streams/                 # CRUD for Science, Commerce, Arts
│   ├── categories/              # CRUD for Learning Categories
│   ├── modules/                 # CRUD for Dashboard Modules
│   ├── subjects/                # CRUD for Subjects with Board/Standard linkage
│   ├── chapters/                # CRUD for Chapter PDFs with upload dropzone
│   ├── banners/                 # CRUD for Promotional Banners & notices
│   └── settings/                # App version, maintenance mode, force update
├── uploads/
│   ├── pdfs/                    # Uploaded textbook chapters & PDF materials
│   ├── icons/                   # Subject and category icons
│   └── banners/                 # Home banner graphics
├── database.sql                 # Complete DB schema & pre-seeded datasets
└── index.php                    # Entrypoint router
```

---

## 📱 Connecting to the Flutter App

In your Flutter app (`lib/`):
Replace mock data with HTTP GET requests pointing to your backend:
- `http://<your-ip-or-domain>/backend/api/get_boards.php`
- `http://<your-ip-or-domain>/backend/api/get_standards.php`
- `http://<your-ip-or-domain>/backend/api/get_subjects.php?board_id=1&standard_number=9`
- `http://<your-ip-or-domain>/backend/api/get_chapters.php?subject_id=2`
- `http://<your-ip-or-domain>/backend/api/get_app_config.php`
