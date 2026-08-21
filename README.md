# Misty Munnar Tours

This repository contains the Misty Munnar Tours website: a glassmorphism-themed responsive tourism site with a booking system and an admin panel.

Features
- Glassmorphism front-end (HTML/CSS/JS) with responsive layout
- Dark / Light mode toggle
- AOS animations and Swiper carousel integration via CDN
- Booking form that posts to a PHP endpoint (stores data in MySQL via PDO)
- Admin panel (session-based authentication) to manage packages, bookings, enquiries and gallery
- WhatsApp quick-message button that opens WhatsApp with a prefilled message

Quick setup
1. Clone the repo.
2. Create a MySQL database and user.
3. Import db.sql to create the tables.

   mysql -u youruser -p yourdb < db.sql

4. Copy config.example.php to config.php and update DB credentials and site settings.
5. Ensure your PHP server can write sessions and uploads (for gallery).
6. Point your web server document root to the repository root or a subfolder depending on deployment.

Admin
- Create an admin user by inserting into the `admins` table. Use PHP's password_hash to create the password hash.

Notes
- This is a starting scaffold — refine the UI, add validation, sanitization, and production security (CSRF tokens, input validation, file upload checks).
