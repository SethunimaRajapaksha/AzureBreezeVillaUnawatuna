<?php
// includes/functions.php - booking helpers shared by the Rooms, Room details and Booking pages

/** Turn "2026-12-01" into a DateTimeImmutable, or null when the text is not a real date. */
function parse_date(?string $text): ?DateTimeImmutable
{
    if (!$text) {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $text);
    return ($d && $d->format('Y-m-d') === $text) ? $d : null;
}

/** Number of nights between two valid dates (check-out minus check-in). */
function nights_between(string $checkIn, string $checkOut): int
{
    return (int)(new DateTimeImmutable($checkIn))->diff(new DateTimeImmutable($checkOut))->format('%r%a');
}

/**
 * Server-side validation of a stay. Fills $errors and returns true when everything is valid.
 * (The browser also checks this in JavaScript, but the server must never trust the browser.)
 */
function validate_stay(?string $checkIn, ?string $checkOut, int $guests, array &$errors): bool
{
    $in    = parse_date($checkIn);
    $out   = parse_date($checkOut);
    $today = new DateTimeImmutable('today');

    if (!$in || !$out) {
        $errors[] = 'Please choose valid check-in and check-out dates.';
    } else {
        if ($in < $today) {
            $errors[] = 'Check-in date cannot be in the past.';
        }
        if ($out <= $in) {
            $errors[] = 'Check-out date must be after the check-in date.';
        } elseif (nights_between($checkIn, $checkOut) > 30) {
            $errors[] = 'Online bookings are limited to 30 nights.';
        }
    }
    if ($guests < 1 || $guests > 10) {
        $errors[] = 'Number of guests must be between 1 and 10.';
    }
    return !$errors;
}

/** All active rooms (no dates chosen yet), optionally filtered by room type. */
function list_active_rooms(PDO $pdo, string $type = ''): array
{
    $sql    = "SELECT * FROM rooms WHERE room_status = 'active'";
    $params = [];
    if ($type !== '') {
        $sql .= ' AND room_type = ?';
        $params[] = $type;
    }
    $stmt = $pdo->prepare($sql . ' ORDER BY price_per_night ASC');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Rooms that are active, big enough for the party and NOT blocked by a CONFIRMED booking
 * that overlaps the requested dates. Two stays overlap when:
 *   new_check_in < existing_check_out  AND  new_check_out > existing_check_in
 */
function find_available_rooms(PDO $pdo, string $checkIn, string $checkOut, int $guests, string $type = ''): array
{
    $sql = "SELECT r.* FROM rooms r
            WHERE r.room_status = 'active'
              AND r.max_guests >= :guests
              AND NOT EXISTS (
                    SELECT 1 FROM bookings b
                    WHERE b.room_id = r.room_id
                      AND b.booking_status = 'Confirmed'
                      AND :check_in < b.check_out
                      AND :check_out > b.check_in)";
    $params = [':guests' => $guests, ':check_in' => $checkIn, ':check_out' => $checkOut];
    if ($type !== '') {
        $sql .= ' AND r.room_type = :type';
        $params[':type'] = $type;
    }
    $stmt = $pdo->prepare($sql . ' ORDER BY r.price_per_night ASC');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** True when one particular room is free (no confirmed booking overlaps) for the dates. */
function room_is_available(PDO $pdo, int $roomId, string $checkIn, string $checkOut): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings
                           WHERE room_id = :room_id AND booking_status = 'Confirmed'
                             AND :check_in < check_out AND :check_out > check_in");
    $stmt->execute([':room_id' => $roomId, ':check_in' => $checkIn, ':check_out' => $checkOut]);
    return (int)$stmt->fetchColumn() === 0;
}

/** Split the comma separated facilities text into a clean array. */
function facility_list(string $text): array
{
    return array_values(array_filter(array_map('trim', explode(',', $text))));
}

/** Nice booking reference, e.g. 42 becomes "BK-0042". */
function booking_ref(int $id): string
{
    return 'BK-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

/**
 * Finds another CONFIRMED booking for the same room that overlaps the given dates.
 * Returns that booking (with the room name) or null when the dates are free.
 * $excludeBookingId stops a booking from clashing with itself.
 */
function find_conflicting_booking(PDO $pdo, int $roomId, string $checkIn, string $checkOut, int $excludeBookingId = 0): ?array
{
    $stmt = $pdo->prepare("SELECT b.booking_id, b.check_in, b.check_out, r.room_name
                           FROM bookings b JOIN rooms r ON r.room_id = b.room_id
                           WHERE b.room_id = ? AND b.booking_status = 'Confirmed'
                             AND b.booking_id <> ? AND ? < b.check_out AND ? > b.check_in
                           LIMIT 1");
    $stmt->execute([$roomId, $excludeBookingId, $checkIn, $checkOut]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Validates and stores an uploaded room photo in assets/images/rooms/.
 * Returns the new file name, or null with a message in $error.
 */
function save_room_image(array $file, string &$error): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'The image could not be uploaded. Please try again.';
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'The image must be smaller than 2 MB.';
        return null;
    }
    $info  = @getimagesize($file['tmp_name']);       // reads the real file type, not the file name
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($types[$info['mime']])) {
        $error = 'Only JPG, PNG or WebP images are allowed.';
        return null;
    }
    $name = 'room-' . bin2hex(random_bytes(8)) . '.' . $types[$info['mime']];   // random name, never the user's
    $dir  = dirname(__DIR__) . '/assets/images/rooms/';
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        $error = 'The image could not be saved on the server.';
        return null;
    }
    return $name;
}
