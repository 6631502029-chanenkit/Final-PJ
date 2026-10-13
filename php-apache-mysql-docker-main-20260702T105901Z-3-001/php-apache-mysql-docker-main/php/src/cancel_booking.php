<?php 

    session_start();
    require_once 'config/db.php';

    // เช็คว่า user login อยู่
    if (!isset($_SESSION['id_account'])) {
        $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
        header("location: signin.php");
        exit();
    }

    $ban_check_stmt = $conn->prepare("SELECT ban_account FROM account WHERE id_account = :user_id");
    $ban_check_stmt->bindParam(":user_id", $_SESSION['id_account'], PDO::PARAM_INT);
    $ban_check_stmt->execute();
    $ban_check_row = $ban_check_stmt->fetch(PDO::FETCH_ASSOC);

    if (($ban_check_row['ban_account'] ?? 0) == 1) {
        $_SESSION['error'] = 'บัญชีนี้ถูกแบน';
        header("location: signin.php");
        exit();
    }

    // เช็คว่ามี booking_id มาจาก URL
    if (!isset($_GET['booking_id'])) {
        $_SESSION['error'] = 'ไม่พบข้อมูลการจอง';
        header("location: user.php");
        exit();
    }

    $booking_id = $_GET['booking_id'];
    $user_id = $_SESSION['id_account'];

    try {
        // ดึงข้อมูลการจองเพื่อเช็คว่าเป็นของ user คนนี้หรือไม่
        $check_stmt = $conn->prepare("SELECT * FROM booking_info WHERE booking_id = :booking_id AND user_id = :user_id");
        $check_stmt->bindParam(":booking_id", $booking_id);
        $check_stmt->bindParam(":user_id", $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $booking_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $room_id = $booking_data['room_count']; // ดึง room_id ที่จอง
            
            // ลบข้อมูลการจองจาก booking_info
            $delete_stmt = $conn->prepare("DELETE FROM booking_info WHERE booking_id = :booking_id");
            $delete_stmt->bindParam(":booking_id", $booking_id);
            
            if ($delete_stmt->execute()) {
                // เช็คว่าห้องนี้ยังมีการจองอื่นอยู่หรือไม่
                $check_other_booking = $conn->prepare("SELECT COUNT(*) as count FROM booking_info WHERE room_count = :room_id");
                $check_other_booking->bindParam(":room_id", $room_id);
                $check_other_booking->execute();
                $other_booking = $check_other_booking->fetch(PDO::FETCH_ASSOC);
                
                // ถ้าไม่มีการจองอื่นแล้ว ให้เปลี่ยน room_status เป็น 0 (ห้องว่าง)
                if ($other_booking['count'] == 0) {
                    $update_room = $conn->prepare("UPDATE room SET room_status = 0 WHERE room_id = :room_id");
                    $update_room->bindParam(":room_id", $room_id);
                    $update_room->execute();
                }
                
                $_SESSION['success'] = 'ยกเลิกการจองสำเร็จ';
            } else {
                $_SESSION['error'] = 'เกิดข้อผิดพลาดในการลบข้อมูล';
            }
        } else {
            $_SESSION['error'] = 'ไม่มีประวัติการจอง';
        }

    } catch(PDOException $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }

    header("location: user.php");
    exit();

?>
