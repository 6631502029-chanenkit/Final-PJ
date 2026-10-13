<?php 

    session_start();
    require_once 'config/db.php';

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

    if (isset($_POST['submit'])) {
        $user_id = (int)$_SESSION['id_account'];
        $checkin = trim($_POST['checkin'] ?? '');
        $checkout = trim($_POST['checkout'] ?? '');
        $guests = trim($_POST['guests'] ?? '');
        $room_id = isset($_POST['room']) ? (int)$_POST['room'] : 0;

        // ตรวจสอบว่ากรอกข้อมูลครบหรือไม่
        if (empty($checkin)) {
            $_SESSION['error'] = 'กรุณากรอกวันที่เช็คอิน';
            header("location: user.php");
            exit();
        } else if (empty($checkout)) {
            $_SESSION['error'] = 'กรุณากรอกวันที่เช็คเอ้าท์';
            header("location: user.php");
            exit();
        } else if (empty($guests)) {
            $_SESSION['error'] = 'กรุณาเลือกจำนวนผู้เข้าพัก';
            header("location: user.php");
            exit();
        } else if ($room_id <= 0) {
            $_SESSION['error'] = 'กรุณาเลือกห้องพัก';
            header("location: user.php");
            exit();
        } else {
            try {
                // ตรวจสอบว่าวันที่เช็คอิน ต้องน้อยกว่าเช็คเอ้าท์
                if (strtotime($checkin) >= strtotime($checkout)) {
                    $_SESSION['error'] = 'ไม่สามารถเลือกวันที่เช็คอินเท่ากับวันที่เช็คเอ้าท์ได้';
                    header("location: user.php");
                    exit();
                }

                $conn->beginTransaction();

                $room_stmt = $conn->prepare("SELECT room_id, room_number FROM room WHERE room_id = :room_id");
                $room_stmt->bindParam(":room_id", $room_id, PDO::PARAM_INT);
                $room_stmt->execute();
                $room_row = $room_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$room_row) {
                    throw new Exception('ไม่พบข้อมูลห้องพัก');
                }

                $availability_stmt = $conn->prepare("SELECT COUNT(*) AS booking_count FROM booking_info WHERE room_count = :room_id AND check_in < :checkout AND check_out > :checkin");
                $availability_stmt->bindParam(":room_id", $room_id, PDO::PARAM_INT);
                $availability_stmt->bindParam(":checkout", $checkout);
                $availability_stmt->bindParam(":checkin", $checkin);
                $availability_stmt->execute();
                $availability_row = $availability_stmt->fetch(PDO::FETCH_ASSOC);

                if ((int)($availability_row['booking_count'] ?? 0) > 0) {
                    throw new Exception('ห้องนี้ไม่ว่างในช่วงวันที่ที่เลือก');
                }
                
                // ดึงข้อมูล firstname จาก account
                $account_stmt = $conn->prepare("SELECT firstname FROM account WHERE id_account = :user_id");
                $account_stmt->bindParam(":user_id", $user_id);
                $account_stmt->execute();
                $account_row = $account_stmt->fetch(PDO::FETCH_ASSOC);
                $firstname = $account_row['firstname'];
                
                // บันทึกข้อมูลการจอง
                $insert = $conn->prepare("INSERT INTO booking_info (user_id, name_id, room_count, member, check_in, check_out) VALUES (:user_id, :name_id, :room_count, :member, :check_in, :check_out)");
                $insert->bindParam(":user_id", $user_id);
                $insert->bindParam(":name_id", $firstname);
                $insert->bindParam(":room_count", $room_id);
                $insert->bindParam(":member", $guests);
                $insert->bindParam(":check_in", $checkin);
                $insert->bindParam(":check_out", $checkout);
                
                if ($insert->execute()) {
                    $booking_id = (int)$conn->lastInsertId();

                    // อัพเดต room_status เป็น 1 (ห้องเต็ม) เมื่อมีการจองสำเร็จ
                    $update_room = $conn->prepare("UPDATE room SET room_status = 1 WHERE room_id = :room_id");
                    $update_room->bindParam(":room_id", $room_id);
                    $update_room->execute();

                    $conn->commit();
                    
                    header("location: payment.php?booking_id=" . $booking_id);
                    exit();
                }

            } catch(PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                header("location: user.php");
                exit();
            } catch(Throwable $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                header("location: user.php");
                exit();
            }
        }
    }

?>
