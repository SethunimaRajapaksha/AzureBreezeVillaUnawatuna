# Azure Breeze Villa - Villa and Room Booking System

A dynamic web application for **Azure Breeze Villa**, a beachfront villa in **Unawatuna, Sri Lanka**.
Visitors can explore the villa, search room availability, register, and send booking requests.
The administrator manages rooms and confirms or cancels bookings.

- **GitHub repository:** https://github.com/SethunimaRajapaksha/AzureBreezeVillaUnawatuna
- **Technologies:** HTML5, CSS3, JavaScript, PHP (PDO) and MySQL (MariaDB)

## Implemented features

### Guest side
- Home page with hero, availability search, featured rooms (loaded from the database), facilities and gallery
- Rooms page with **availability search** (dates, guests, room type); only rooms free of confirmed bookings are shown
- Room details page and a photo gallery with category filter and full-size viewer
- About, Facilities and Contact pages (contact messages are saved in the database; Google Map embed)
- Guest **registration, login and logout** (PHP sessions, `password_hash()` / `password_verify()`)
- **Booking form** with live night count and total price (JavaScript); the server recalculates the price and checks availability again
- **My Bookings**: only the logged-in guest's bookings, status badges (Pending / Confirmed / Cancelled), cancel pending bookings
- **Profile**: change name, phone number and password

### Administrator side
- **Dashboard** with booking statistics, revenue, monthly bar chart and recent bookings
- **Manage Bookings**: filter by status, search, **confirm** (blocked when another confirmed booking overlaps the same room and dates) or cancel
- **Manage Rooms**: add, view, edit (details, price, photo), deactivate / activate and delete rooms
- **Messages**: read and delete contact form enquiries

### Responsive design
Layouts for desktop, tablet and mobile using CSS media queries (mobile menu, stacked cards).

## Software requirements
- XAMPP (Apache + MySQL/MariaDB) with PHP 8.0 or newer
- A modern web browser (Chrome, Edge, Firefox)

## Installation
1. Install and start **XAMPP**. In the XAMPP Control Panel start **Apache** and **MySQL**.
2. Copy this project folder into `C:\xampp\htdocs\` (for example `C:\xampp\htdocs\AzureBreezeVillaUnawatuna`).
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), choose the **Import** tab (without selecting a database) and import `db_structure.sql`.
   This creates the database `azure_breeze_villa` with tables and sample data.
4. Check the database settings in `config/db.php` (defaults: host `127.0.0.1`, port `3306`, user `root`, empty password).
5. Open the site: `http://localhost/AzureBreezeVillaUnawatuna/` (the base URL is detected automatically).
6. Edit the villa contact details in `config/config.php` (address, phone, email, check-in/out times, WhatsApp).

## Sample login credentials (fictional test accounts)

| Role  | Name                | Email                     | Password  |
| :---  | :------------------ | :------------------------ | :-------- |
| Admin | Villa Administrator | admin@azurebreezevilla.lk | Admin@123 |
| Guest | Kumari              | guest@example.com         | Guest1234 |
| Guest | Nimali Perera       | nimali@example.com        | Guest@123 |
| Guest | Kasun Fernando      | kasun@example.com         | Guest@123 |
| Guest | Sunil Shantha       | sunilshantha@gmail.com    | Guest@123 |

## Database
Tables in `db_structure.sql`:
- `users` (user_id, name, email, contact_number, password, role)
- `rooms` (room_id, room_name, room_type, description, facilities, price_per_night, max_guests, bed_info, image, room_status)
- `bookings` (booking_id, user_id FK, room_id FK, check_in, check_out, guests, total_amount, booking_status, booking_date)
- `contact_messages` (message_id, name, email, phone, subject, message, created_at)

Foreign keys: `bookings.user_id` references `users`, `bookings.room_id` references `rooms`.
Overlap rule: two stays overlap when `new_check_in < existing_check_out AND new_check_out > existing_check_in`.
Only **confirmed** bookings block a room.

## Security and good practice
- PDO **prepared statements** for every query with user input
- Passwords stored with `password_hash()`; one generic message for failed logins; `session_regenerate_id()` after login
- **CSRF tokens** on every form that changes data
- Output escaped with `htmlspecialchars()` (XSS protection)
- Access control: `require_login()` for guests, `require_admin()` for admin pages; guests can only see and cancel their **own** pending bookings
- Both client-side (JavaScript) and server-side (PHP) validation; the server recalculates prices and re-checks availability
- Database transactions with row locks to stop two people booking or confirming the same dates at the same time
- Image upload checks the real file type and size and saves under a random name
- Open-redirect protection on the login redirect; honeypot field against spam on the contact form

## Project structure
```
config/      config.php (settings), db.php (PDO connection)
includes/    header, footer, admin layout, auth helpers, booking functions
guest/       book.php, my-bookings.php, profile.php
admin/       dashboard.php, bookings.php, rooms.php, messages.php
assets/      css/, js/, images/
index.php, rooms.php, room.php, about.php, gallery.php, facilities.php, contact.php
login.php, register.php, logout.php, db_structure.sql
```

## Use of generative AI (declaration)
I used **Claude (Anthropic)** as an assistant during this project. It helped me plan the system, create the colour palette and page mock-ups, and write and explain parts of the PHP, JavaScript, CSS and SQL code. I reviewed, desided the colour palette, tested and adjusted the code myself and I can explain how it works.

## External resources and credits
- **Fonts:** Inter and Playfair Display (Google Fonts)
- **Map:** Google Maps embed on the Contact page
- **Photographs:** Selected imagery sourced from Pinterest for design reference and mood boarding
- **Logo:** Custom-designed logo created specifically for Azure Breeze Villa
- **No external PHP or JavaScript libraries are used.**

## Author
Sethunima Rajapaksha . 24IT0526 . Web Technologies
