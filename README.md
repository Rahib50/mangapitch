# MangaPitch (CSE370 Project)

## Requirements
- XAMPP (Apache + MySQL)
- phpMyAdmin (comes with XAMPP)

## Setup (Database)
1. Start **Apache** and **MySQL** from XAMPP Control Panel.
2. Open phpMyAdmin.
3. Go to **Import** and import `sql/schema.sql`.

This creates the database **`mangapitch`** and all tables.

## Run (Local)
1. Put this folder inside `xampp/htdocs/` (or create a virtual host).
2. Visit `/index.php` in your browser.

## Notes
- Auth pages are currently placeholders (no real DB login yet).
- DB connection settings are in `config/db.php`.