-- ============================================================
-- Azure Breeze Villa - Database structure and sample data
-- IT1208 Web Technologies, CA2 (2026)
-- Import with phpMyAdmin (XAMPP) or: mysql -u root < db_structure.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS azure_breeze_villa
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE azure_breeze_villa;

-- Drop in reverse dependency order so the script can be re-run safely
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- users: guests and administrators
-- ------------------------------------------------------------
CREATE TABLE users (
  user_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name           VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  contact_number VARCHAR(20)  NOT NULL,
  password       VARCHAR(255) NOT NULL,            -- password_hash() output
  role           ENUM('guest','admin') NOT NULL DEFAULT 'guest',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- rooms: room types offered by the villa
-- ------------------------------------------------------------
CREATE TABLE rooms (
  room_id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  room_name       VARCHAR(100) NOT NULL,
  room_type       VARCHAR(50)  NOT NULL,           -- e.g. Suite, Villa, Family
  description     TEXT NOT NULL,
  facilities      VARCHAR(255) NOT NULL DEFAULT '', -- comma separated list
  price_per_night DECIMAL(10,2) NOT NULL,           -- LKR
  max_guests      TINYINT UNSIGNED NOT NULL,
  bed_info        VARCHAR(50)  NOT NULL DEFAULT '',
  image           VARCHAR(255) NOT NULL DEFAULT '', -- file name in assets/images/rooms/
  room_status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (room_id),
  CONSTRAINT chk_rooms_price  CHECK (price_per_night > 0),
  CONSTRAINT chk_rooms_guests CHECK (max_guests > 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- bookings: each booking belongs to one guest and one room
-- ------------------------------------------------------------
CREATE TABLE bookings (
  booking_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  room_id        INT UNSIGNED NOT NULL,
  check_in       DATE NOT NULL,
  check_out      DATE NOT NULL,
  guests         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  total_amount   DECIMAL(10,2) NOT NULL,            -- calculated on the server
  booking_status ENUM('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Pending',
  booking_date   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (booking_id),
  KEY idx_bookings_user (user_id),
  KEY idx_bookings_room_dates (room_id, check_in, check_out),
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookings_room FOREIGN KEY (room_id)
    REFERENCES rooms (room_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_bookings_dates CHECK (check_out > check_in)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- reviews: one rating and comment per finished stay
-- ------------------------------------------------------------
CREATE TABLE reviews (
  review_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id    INT UNSIGNED NOT NULL,                 -- one review per booking
  user_id       INT UNSIGNED NOT NULL,
  room_id       INT UNSIGNED NOT NULL,
  rating        TINYINT UNSIGNED NOT NULL,             -- 1 to 5 stars
  comment       VARCHAR(1000) NOT NULL,
  review_status ENUM('Visible','Hidden') NOT NULL DEFAULT 'Visible',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  UNIQUE KEY uq_reviews_booking (booking_id),
  KEY idx_reviews_room (room_id),
  CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings (booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users (user_id)       ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_room    FOREIGN KEY (room_id)    REFERENCES rooms (room_id)       ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;


-- ------------------------------------------------------------
-- contact_messages: enquiries sent from the Contact page
-- ------------------------------------------------------------
CREATE TABLE contact_messages (
  message_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  phone       VARCHAR(20)  DEFAULT NULL,
  subject     VARCHAR(150) NOT NULL,
  message     TEXT NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id)
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA (fictional guests only)
-- Login credentials for testing:
--   Admin : admin@azurebreeze.lk   / Admin@123
--   Guest : guest@example.com      / Guest@123
--   Guest : nimali@example.com     / Guest@123
--   Guest : kasun@example.com      / Guest@123
-- Passwords are stored as bcrypt hashes (PHP password_verify compatible).
-- ============================================================

INSERT INTO users (name, email, contact_number, password, role) VALUES
('Villa Administrator', 'admin@azurebreeze.lk', '0710000000',
 '$2y$12$kHxPaDdJNTO/JD06VJPhPO4x3/e2Lr9/WMRgPO7nGTH6lIn4Uhbra', 'admin'),
('Sample Guest', 'guest@example.com', '0711234567',
 '$2y$12$CIKUQaVdrnf/TsvdGa47buhCkNxpWFPzm5RCNdVxom7FJCp2qB9iS', 'guest'),
('Nimali Perera', 'nimali@example.com', '0772345678',
 '$2y$12$CIKUQaVdrnf/TsvdGa47buhCkNxpWFPzm5RCNdVxom7FJCp2qB9iS', 'guest'),
('Kasun Fernando', 'kasun@example.com', '0763456789',
 '$2y$12$CIKUQaVdrnf/TsvdGa47buhCkNxpWFPzm5RCNdVxom7FJCp2qB9iS', 'guest');

INSERT INTO rooms
  (room_name, room_type, description, facilities, price_per_night, max_guests, bed_info, image, room_status) VALUES
('Ocean View Suite', 'Suite',
 'Wake up to the sound of waves and breathtaking ocean views.',
 'Sea view, Infinity pool access, Air conditioning, Free Wi-Fi, Breakfast included',
 25000.00, 2, '1 King Bed', 'ocean-view-suite.jpg', 'active'),
('Garden Villa', 'Villa',
 'A private retreat surrounded by nature and tropical gardens.',
 'Garden view, Pool access, Air conditioning, Free Wi-Fi, Breakfast included',
 32000.00, 4, '2 Beds', 'garden-villa.jpg', 'active'),
('Family Suite', 'Family',
 'Spacious and comfortable for families and groups.',
 'Ocean view, Living area, Air conditioning, Free Wi-Fi, Breakfast included',
 38000.00, 4, '2 King Beds', 'family-suite.jpg', 'active');

-- total_amount = nights x price_per_night
-- Confirmed bookings for the same room never overlap.
INSERT INTO bookings
  (user_id, room_id, check_in, check_out, guests, total_amount, booking_status) VALUES
(2, 1, '2026-11-24', '2026-11-27', 2,  75000.00, 'Confirmed'),
(2, 2, '2026-12-02', '2026-12-05', 2,  96000.00, 'Pending'),
(3, 3, '2026-12-20', '2026-12-23', 4, 114000.00, 'Pending'),
(3, 1, '2026-12-01', '2026-12-03', 2,  50000.00, 'Confirmed'),
(4, 2, '2026-11-15', '2026-11-18', 3,  96000.00, 'Cancelled');

INSERT INTO contact_messages (name, email, phone, subject, message) VALUES
('Sample Visitor', 'visitor@example.com', '0701122334', 'Booking enquiry',
 'Hello, do you have a family suite available in December?');

-- Sample finished stays (fictional) so you can test reviews straight away
INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_amount, booking_status)
SELECT u.user_id, r.room_id, '2026-10-05', '2026-10-08', 2, 96000.00, 'Confirmed'
FROM users u, rooms r
WHERE u.email = 'guest@example.com' AND r.room_name = 'Garden Villa'
  AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.user_id = u.user_id AND b.room_id = r.room_id AND b.check_in = '2026-10-05');

INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_amount, booking_status)
SELECT u.user_id, r.room_id, '2026-10-10', '2026-10-12', 2, 50000.00, 'Confirmed'
FROM users u, rooms r
WHERE u.email = 'nimali@example.com' AND r.room_name = 'Ocean View Suite'
  AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.user_id = u.user_id AND b.room_id = r.room_id AND b.check_in = '2026-10-10');

INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_amount, booking_status)
SELECT u.user_id, r.room_id, '2026-09-20', '2026-09-23', 4, 114000.00, 'Confirmed'
FROM users u, rooms r
WHERE u.email = 'kasun@example.com' AND r.room_name = 'Family Suite'
  AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.user_id = u.user_id AND b.room_id = r.room_id AND b.check_in = '2026-09-20');

INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_amount, booking_status)
SELECT u.user_id, r.room_id, '2026-09-01', '2026-09-04', 2, 96000.00, 'Confirmed'
FROM users u, rooms r
WHERE u.email = 'nimali@example.com' AND r.room_name = 'Garden Villa'
  AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.user_id = u.user_id AND b.room_id = r.room_id AND b.check_in = '2026-09-01');

-- Sample reviews (guest@example.com has a finished stay with NO review, so you can write one)
INSERT IGNORE INTO reviews (booking_id, user_id, room_id, rating, comment)
SELECT b.booking_id, b.user_id, b.room_id, 5, 'Waking up to the sound of the waves was magical. The room was spotless and the staff were so friendly.'
FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN rooms r ON r.room_id = b.room_id
WHERE u.email = 'nimali@example.com' AND r.room_name = 'Ocean View Suite' AND b.check_in = '2026-10-10';

INSERT IGNORE INTO reviews (booking_id, user_id, room_id, rating, comment)
SELECT b.booking_id, b.user_id, b.room_id, 4, 'Lots of space for our family and the pool was a big hit with the kids. Breakfast could have more variety.'
FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN rooms r ON r.room_id = b.room_id
WHERE u.email = 'kasun@example.com' AND r.room_name = 'Family Suite' AND b.check_in = '2026-09-20';

INSERT IGNORE INTO reviews (booking_id, user_id, room_id, rating, comment)
SELECT b.booking_id, b.user_id, b.room_id, 5, 'A quiet, green retreat only minutes from the beach. We will definitely come back.'
FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN rooms r ON r.room_id = b.room_id
WHERE u.email = 'nimali@example.com' AND r.room_name = 'Garden Villa' AND b.check_in = '2026-09-01';

-- ============================================================
-- USEFUL QUERIES (used by the PHP code)
-- ============================================================

-- 1) Overlap check: a room is NOT available if a CONFIRMED booking overlaps
--    the requested dates (:check_in, :check_out).
--    SELECT COUNT(*) FROM bookings
--    WHERE room_id = :room_id
--      AND booking_status = 'Confirmed'
--      AND :check_in  < check_out
--      AND :check_out > check_in;

-- 2) Available rooms for a search (dates + number of guests):
--    SELECT r.* FROM rooms r
--    WHERE r.room_status = 'active'
--      AND r.max_guests >= :guests
--      AND r.room_id NOT IN (
--            SELECT room_id FROM bookings
--            WHERE booking_status = 'Confirmed'
--              AND :check_in < check_out AND :check_out > check_in);

-- 3) A guest's own bookings (never trust a user_id sent from the browser):
--    SELECT b.*, r.room_name FROM bookings b
--    JOIN rooms r ON r.room_id = b.room_id
--    WHERE b.user_id = :session_user_id ORDER BY b.booking_date DESC;
