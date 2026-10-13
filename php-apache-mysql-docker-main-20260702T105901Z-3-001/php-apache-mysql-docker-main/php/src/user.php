<?php 
  session_start();
  require_once 'config/db.php';
  if (!isset($_SESSION['id_account'])) {
      $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
      header('location: signin.php');
      exit();
  }

  $user_guard_stmt = $conn->prepare("SELECT ban_account FROM account WHERE id_account = :id_account");
  $user_guard_stmt->bindParam(":id_account", $_SESSION['id_account'], PDO::PARAM_INT);
  $user_guard_stmt->execute();
  $user_guard_row = $user_guard_stmt->fetch(PDO::FETCH_ASSOC);

  if (($user_guard_row['ban_account'] ?? 0) == 1) {
      session_unset();
      session_destroy();
      session_start();
      $_SESSION['error'] = 'บัญชีนี้ถูกแบน';
      header('location: signin.php');
      exit();
  }

    $user_stmt = $conn->prepare("SELECT firstname, lastname FROM account WHERE id_account = :id_account");
    $user_stmt->bindParam(":id_account", $_SESSION['id_account'], PDO::PARAM_INT);
    $user_stmt->execute();
    $row = $user_stmt->fetch(PDO::FETCH_ASSOC) ?: ['firstname' => '', 'lastname' => ''];

    $search_checkin = trim($_GET['checkin'] ?? '');
    $search_checkout = trim($_GET['checkout'] ?? '');
    $search_guests = trim($_GET['guests'] ?? '');
    $search_requested = isset($_GET['search']);
    $available_rooms = [];

    if ($search_requested) {
      if ($search_checkin === '') {
        $_SESSION['error'] = 'กรุณากรอกวันที่เช็คอิน';
      } elseif ($search_checkout === '') {
        $_SESSION['error'] = 'กรุณากรอกวันที่เช็คเอ้าท์';
      } elseif (strtotime($search_checkin) >= strtotime($search_checkout)) {
        $_SESSION['error'] = 'วันที่เช็คเอ้าท์ต้องมากกว่าวันที่เช็คอิน';
      } else {
        $available_stmt = $conn->prepare("SELECT room_id, room_number FROM room WHERE NOT EXISTS (
          SELECT 1
          FROM booking_info b
          WHERE b.room_count = room.room_id
          AND b.check_in < :checkout
          AND b.check_out > :checkin
        ) ORDER BY room_number ASC");
        $available_stmt->bindParam(':checkin', $search_checkin);
        $available_stmt->bindParam(':checkout', $search_checkout);
        $available_stmt->execute();
        $available_rooms = $available_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($available_rooms) === 0) {
          $_SESSION['error'] = 'ไม่มีห้องว่างในช่วงวันที่ที่เลือก';
        }
      }
    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="user.css">
</head>

<body>
  <div class="top-left-bar">
      <h3 class="mt-4">Welcome User, <?php echo $row['firstname'] . ' ' . $row['lastname'] ?> 
      <a href="logout.php" class="btn btn-danger">Logout</a></h3>
    </div>

    <!-- Error/Success Message -->
    <?php if(isset($_SESSION['error'])) { ?>
      <div class="alert alert-danger" role="alert" style="margin-top: 80px;">
          <?php 
              echo $_SESSION['error'];
              unset($_SESSION['error']);
          ?>
      </div>
    <?php } ?>
    <?php if(isset($_SESSION['success'])) { ?>
      <div class="alert alert-success" role="alert" style="margin-top: 80px;">
          <?php 
              echo $_SESSION['success'];
              unset($_SESSION['success']);
          ?>
      </div>
    <?php } ?>

    <!-- Booking History Bar -->
    <div class="booking-history-bar">
      <h5>📋 ประวัติการจอง</h5>
      <?php 
        if (isset($_SESSION['id_account'])) {
            $user_id = $_SESSION['id_account'];
            
            // ดึงข้อมูลการจองจาก database
            $booking_stmt = $conn->prepare("SELECT * FROM booking_info WHERE user_id = :user_id");
            $booking_stmt->bindParam(":user_id", $user_id);
            $booking_stmt->execute();
            
            if ($booking_stmt->rowCount() > 0) {
                echo '<table class="table table-sm table-striped">';
                echo '<thead><tr><th>ห้อง</th><th>Check-in</th><th>Check-out</th><th>จำนวนคน</th><th>จัดการ</th></tr></thead>';
                echo '<tbody>';
                
                while ($booking_row = $booking_stmt->fetch(PDO::FETCH_ASSOC)) {
                    // ดึงเลขห้องจาก room table ด้วย room_id
                    $room_detail_stmt = $conn->prepare("SELECT room_number FROM room WHERE room_id = :room_id");
                    $room_detail_stmt->bindParam(":room_id", $booking_row['room_count']);
                    $room_detail_stmt->execute();
                    $room_detail = $room_detail_stmt->fetch(PDO::FETCH_ASSOC);
                    $room_number = $room_detail['room_number'];
                    
                    echo '<tr>';
                    echo '<td>ห้อง ' . $room_number . '</td>';
                    echo '<td>' . $booking_row['check_in'] . '</td>';
                    echo '<td>' . $booking_row['check_out'] . '</td>';
                    echo '<td>' . $booking_row['member'] . ' คน</td>';
                    echo '<td><a href="cancel_booking.php?booking_id=' . $booking_row['booking_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'ยืนยันการยกเลิก?\')">ยกเลิก</a></td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-info">ไม่มีประวัติการจอง</div>';
            }
        }
      ?>
    </div>

    <!-- Search + Booking Form -->
    <div class="booking-form">
      <h4>📅 จองห้องพัก</h4>
      <form action="user.php" method="GET" class="mb-4">
        <div class="mb-3">
          <label for="checkin" class="form-label">วันที่เช็คอิน</label>
          <input type="date" class="form-control" id="checkin" name="checkin" value="<?php echo htmlspecialchars($search_checkin); ?>" required>
        </div>

        <div class="mb-3">
          <label for="checkout" class="form-label">วันที่เช็คเอ้าท์</label>
          <input type="date" class="form-control" id="checkout" name="checkout" value="<?php echo htmlspecialchars($search_checkout); ?>" required>
        </div>

        <div class="mb-3">
          <label for="guests_search" class="form-label">จำนวนผู้เข้าพัก</label>
          <select class="form-select" id="guests_search" name="guests" required>
            <option value="" disabled <?php echo $search_guests === '' ? 'selected' : ''; ?>>-- เลือกจำนวนคน --</option>
            <option value="1" <?php echo $search_guests === '1' ? 'selected' : ''; ?>>1 คน</option>
            <option value="2" <?php echo $search_guests === '2' ? 'selected' : ''; ?>>2 คน</option>
          </select>
        </div>

        <button type="submit" name="search" value="1" class="btn btn-outline-primary">ค้นหา</button>
      </form>

      <?php if ($search_requested && $search_checkin !== '' && $search_checkout !== '' && strtotime($search_checkin) < strtotime($search_checkout)) { ?>
        <div class="mb-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h5 class="mb-0">ห้องว่างในช่วงวันที่ที่เลือก</h5>
            <?php if (count($available_rooms) > 0) { ?>
              <span class="badge bg-primary">พบห้องว่าง <?php echo count($available_rooms); ?> ห้อง</span>
            <?php } ?>
          </div>
          <?php if (count($available_rooms) > 0) { ?>
            <div class="room-grid">
              <?php foreach ($available_rooms as $available_room) { ?>
                <form action="booking_db.php" method="POST" class="room-card">
                  <input type="hidden" name="checkin" value="<?php echo htmlspecialchars($search_checkin); ?>">
                  <input type="hidden" name="checkout" value="<?php echo htmlspecialchars($search_checkout); ?>">
                  <input type="hidden" name="guests" value="<?php echo htmlspecialchars($search_guests); ?>">
                  <input type="hidden" name="room" value="<?php echo htmlspecialchars($available_room['room_id']); ?>">

                  <div class="room-card__image">
                    <div class="room-badge">ห้องว่าง</div>
                    <div class="room-number">ห้อง <?php echo htmlspecialchars($available_room['room_number']); ?></div>
                  </div>

                  <div class="room-card__body">
                    <div class="room-meta">พร้อมจองในช่วงวันที่ที่เลือก</div>
                    <div class="room-dates">
                      <?php echo htmlspecialchars($search_checkin); ?> - <?php echo htmlspecialchars($search_checkout); ?>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary w-100 mt-3">จองห้องนี้</button>
                  </div>
                </form>
              <?php } ?>
            </div>
          <?php } else { ?>
            <div class="alert alert-info mb-0">ไม่มีห้องว่างในช่วงวันที่ที่เลือก</div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

  </body>
</html>