<?php
// admin/rooms.php - add, view, edit, deactivate/activate and delete rooms
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$errors  = [];
$editing = false;
$form    = ['room_id' => 0, 'room_name' => '', 'room_type' => '', 'description' => '', 'facilities' => '',
            'price_per_night' => '', 'max_guests' => '2', 'bed_info' => '', 'room_status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $roomId = (int)($_POST['room_id'] ?? 0);

    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
        header('Location: ' . BASE_URL . '/admin/rooms.php');
        exit;
    }

    // Activate / deactivate (the room stays in the database, guests just cannot book it)
    if ($action === 'toggle') {
        $to = ($_POST['to'] ?? '') === 'active' ? 'active' : 'inactive';
        $pdo->prepare('UPDATE rooms SET room_status = ? WHERE room_id = ?')->execute([$to, $roomId]);
        flash('success', 'Room ' . ($to === 'active' ? 'activated.' : 'deactivated. Guests can no longer book it.'));
        header('Location: ' . BASE_URL . '/admin/rooms.php');
        exit;
    }

    // Delete (only rooms that never had bookings; the foreign key protects booking history)
    if ($action === 'delete') {
        $count = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE room_id = ?');
        $count->execute([$roomId]);
        if ((int)$count->fetchColumn() > 0) {
            flash('error', 'This room has booking history and cannot be deleted. Deactivate it instead.');
        } else {
            $pdo->prepare('DELETE FROM rooms WHERE room_id = ?')->execute([$roomId]);
            flash('success', 'Room deleted.');
        }
        header('Location: ' . BASE_URL . '/admin/rooms.php');
        exit;
    }

    // Add or update
    if ($action === 'save') {
        $form = [
            'room_id'         => $roomId,
            'room_name'       => trim($_POST['room_name'] ?? ''),
            'room_type'       => trim($_POST['room_type'] ?? ''),
            'description'     => trim($_POST['description'] ?? ''),
            'facilities'      => trim($_POST['facilities'] ?? ''),
            'price_per_night' => trim($_POST['price_per_night'] ?? ''),
            'max_guests'      => trim($_POST['max_guests'] ?? ''),
            'bed_info'        => trim($_POST['bed_info'] ?? ''),
            'room_status'     => ($_POST['room_status'] ?? '') === 'inactive' ? 'inactive' : 'active',
        ];
        $editing = $roomId > 0;

        if (mb_strlen($form['room_name']) < 2 || mb_strlen($form['room_name']) > 100) { $errors['room_name'] = 'Enter a room name (2-100 characters).'; }
        if (mb_strlen($form['room_type']) < 2 || mb_strlen($form['room_type']) > 50)   { $errors['room_type'] = 'Enter a room type (2-50 characters).'; }
        if ($form['description'] === '' || mb_strlen($form['description']) > 2000)     { $errors['description'] = 'Enter a description (up to 2000 characters).'; }
        if (mb_strlen($form['facilities']) > 255)                                      { $errors['facilities'] = 'Facilities must be 255 characters or fewer.'; }
        if (!is_numeric($form['price_per_night']) || $form['price_per_night'] <= 0 || $form['price_per_night'] > 1000000) {
            $errors['price_per_night'] = 'Enter a price between 1 and 1,000,000.';
        }
        if (!ctype_digit($form['max_guests']) || (int)$form['max_guests'] < 1 || (int)$form['max_guests'] > 10) {
            $errors['max_guests'] = 'Max guests must be between 1 and 10.';
        }
        if (mb_strlen($form['bed_info']) > 50) { $errors['bed_info'] = 'Bed info must be 50 characters or fewer.'; }

        $newImage = null;
        if (!empty($_FILES['image']['name'])) {
            $imageError = '';
            $newImage = save_room_image($_FILES['image'], $imageError);
            if ($newImage === null) {
                $errors['image'] = $imageError;
            }
        }

        if (!$errors) {
            try {
                if ($editing) {
                    $old = $pdo->prepare('SELECT image FROM rooms WHERE room_id = ?');
                    $old->execute([$roomId]);
                    $existing = $old->fetch();
                    if (!$existing) {
                        $errors['room_name'] = 'This room no longer exists.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE rooms SET room_name = ?, room_type = ?, description = ?, facilities = ?,
                                               price_per_night = ?, max_guests = ?, bed_info = ?, image = ?, room_status = ?
                                               WHERE room_id = ?');
                        $stmt->execute([$form['room_name'], $form['room_type'], $form['description'], $form['facilities'],
                                        $form['price_per_night'], (int)$form['max_guests'], $form['bed_info'],
                                        $newImage ?? $existing['image'], $form['room_status'], $roomId]);
                        flash('success', 'Room updated.');
                    }
                } else {
                    $stmt = $pdo->prepare('INSERT INTO rooms (room_name, room_type, description, facilities, price_per_night, max_guests, bed_info, image, room_status)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$form['room_name'], $form['room_type'], $form['description'], $form['facilities'],
                                    $form['price_per_night'], (int)$form['max_guests'], $form['bed_info'],
                                    $newImage ?? 'default-room.jpg', $form['room_status']]);
                    flash('success', 'Room added.');
                }
                if (!$errors) {
                    header('Location: ' . BASE_URL . '/admin/rooms.php');
                    exit;
                }
            } catch (PDOException $ex) {
                error_log('Room save failed: ' . $ex->getMessage());
                $errors['room_name'] = 'Something went wrong while saving. Please try again.';
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE room_id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    if ($row = $stmt->fetch()) {
        $form    = $row;
        $editing = true;
    }
}

$rooms = $pdo->query('SELECT * FROM rooms ORDER BY room_status ASC, price_per_night ASC')->fetchAll();

$pageTitle = 'Manage Rooms';
$adminPage = 'rooms';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="admin-title">Manage Rooms</h1>

<div class="panel">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Photo</th><th>Room</th><th>Type</th><th>Price / night</th><th>Guests</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
          <tr>
            <td><img class="thumb" src="<?= BASE_URL ?>/assets/images/rooms/<?= e($r['image']) ?>" alt="<?= e($r['room_name']) ?>"></td>
            <td><strong><?= e($r['room_name']) ?></strong></td>
            <td><?= e($r['room_type']) ?></td>
            <td><?= e(money($r['price_per_night'])) ?></td>
            <td><?= (int)$r['max_guests'] ?></td>
            <td><span class="status status-<?= $r['room_status'] === 'active' ? 'confirmed' : 'inactive' ?>"><?= e(ucfirst($r['room_status'])) ?></span></td>
            <td>
              <div class="row-actions">
                <a class="btn btn-outline-dark btn-sm" href="?edit=<?= (int)$r['room_id'] ?>#room-form">Edit</a>
                <form method="post" action=""><?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle"><input type="hidden" name="room_id" value="<?= (int)$r['room_id'] ?>">
                  <input type="hidden" name="to" value="<?= $r['room_status'] === 'active' ? 'inactive' : 'active' ?>">
                  <button class="btn btn-sm <?= $r['room_status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= $r['room_status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                </form>
                <form method="post" action="" data-confirm="Delete this room permanently?"><?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="room_id" value="<?= (int)$r['room_id'] ?>">
                  <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rooms): ?><tr><td colspan="7" class="muted-cell">No rooms yet. Add one below.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel" id="room-form">
  <div class="panel-title"><?= $editing ? 'Edit room' : 'Add a new room' ?>
    <?php if ($editing): ?><a class="panel-link" href="<?= BASE_URL ?>/admin/rooms.php">Cancel edit</a><?php endif; ?>
  </div>
  <form method="post" action="" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="room_id" value="<?= (int)$form['room_id'] ?>">

    <div class="form-grid">
      <div class="form-group"><label for="room_name">Room name</label>
        <input type="text" id="room_name" name="room_name" value="<?= e($form['room_name']) ?>" required>
        <span class="form-error"><?= e($errors['room_name'] ?? '') ?></span></div>
      <div class="form-group"><label for="room_type">Room type</label>
        <input type="text" id="room_type" name="room_type" value="<?= e($form['room_type']) ?>" placeholder="Suite, Villa, Family" required>
        <span class="form-error"><?= e($errors['room_type'] ?? '') ?></span></div>
      <div class="form-group"><label for="price_per_night">Price per night (LKR)</label>
        <input type="number" id="price_per_night" name="price_per_night" value="<?= e($form['price_per_night']) ?>" min="1" step="1" required>
        <span class="form-error"><?= e($errors['price_per_night'] ?? '') ?></span></div>
      <div class="form-group"><label for="max_guests">Max guests</label>
        <input type="number" id="max_guests" name="max_guests" value="<?= e($form['max_guests']) ?>" min="1" max="10" required>
        <span class="form-error"><?= e($errors['max_guests'] ?? '') ?></span></div>
      <div class="form-group"><label for="bed_info">Beds</label>
        <input type="text" id="bed_info" name="bed_info" value="<?= e($form['bed_info']) ?>" placeholder="1 King Bed">
        <span class="form-error"><?= e($errors['bed_info'] ?? '') ?></span></div>
      <div class="form-group"><label for="room_status">Status</label>
        <select id="room_status" name="room_status">
          <option value="active" <?= $form['room_status'] === 'active' ? 'selected' : '' ?>>Active (guests can book)</option>
          <option value="inactive" <?= $form['room_status'] === 'inactive' ? 'selected' : '' ?>>Inactive (hidden)</option>
        </select></div>
    </div>
    <div class="form-group"><label for="description">Description</label>
      <textarea id="description" name="description" rows="3" required><?= e($form['description']) ?></textarea>
      <span class="form-error"><?= e($errors['description'] ?? '') ?></span></div>
    <div class="form-group"><label for="facilities">Facilities (comma separated)</label>
      <input type="text" id="facilities" name="facilities" value="<?= e($form['facilities']) ?>" placeholder="Sea view, Air conditioning, Free Wi-Fi">
      <span class="form-error"><?= e($errors['facilities'] ?? '') ?></span></div>
    <div class="form-group"><label for="image">Room photo (JPG, PNG or WebP, max 2 MB)<?= $editing ? ' - leave empty to keep the current photo' : '' ?></label>
      <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
      <span class="form-error"><?= e($errors['image'] ?? '') ?></span></div>
    <button class="btn btn-gold" type="submit"><?= $editing ? 'Save changes' : 'Add room' ?></button>
  </form>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
