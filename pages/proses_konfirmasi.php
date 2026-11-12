<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

// Cek login admin
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$payment_id = $_POST['payment_id'] ?? 0;
$status = $_POST['status'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$payment_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

$allowed = ['paid', 'failed'];
if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    // 🔥 PERBAIKAN: Hapus confirmed_at dan confirmed_by
    $query = "UPDATE payments SET 
              status = '$status',
              admin_notes = '$notes'
              WHERE id = $payment_id";
    
    if (!mysqli_query($conn, $query)) {
        throw new Exception('Gagal update pembayaran');
    }
    
    if ($status == 'paid') {
        $get = "SELECT reservation_id, user_id FROM payments WHERE id = $payment_id";
        $res = mysqli_query($conn, $get);
        $data = mysqli_fetch_assoc($res);
        
        if ($data && $data['reservation_id']) {
            $rid = $data['reservation_id'];
            $uid = $data['user_id'];
            
            // Update status reservasi
            $update = "UPDATE reservations SET status = 'confirmed' WHERE id = $rid";
            mysqli_query($conn, $update);
            
            // Kirim notifikasi
            if ($uid) {
                $msg = "✅ Pembayaran dikonfirmasi! Reservasi #$rid sudah aktif.";
                $notif = "INSERT INTO notifications (user_id, message, is_read, created_at) 
                          VALUES ($uid, '$msg', 0, NOW())";
                mysqli_query($conn, $notif);
            }
        }
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Berhasil dikonfirmasi']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>