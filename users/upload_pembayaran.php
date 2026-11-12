<?php
// Ambil data pembayaran user
$user_id = $_SESSION['user_id'];
$query = "SELECT p.*, 
          r.reservation_code,
          f.nama_lapangan,
          p.status as payment_status
          FROM payments p
          LEFT JOIN reservations r ON p.reservation_id = r.id
          LEFT JOIN fields f ON r.field_id = f.id
          WHERE p.user_id = $user_id
          ORDER BY p.payment_date DESC";
$result = mysqli_query($conn, $query);
?>

<!-- Tampilkan Status Pembayaran -->
<table class="table">
    <thead>
        <tr>
            <th>Kode Reservasi</th>
            <th>Lapangan</th>
            <th>Jumlah</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['reservation_code'] ?></td>
            <td><?= $row['nama_lapangan'] ?></td>
            <td>Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
            <td><?= strtoupper($row['payment_method']) ?></td>
            <td>
                <?php
                $status_badge = [
                    'pending' => '<span class="badge bg-warning">⏳ Pending</span>',
                    'paid' => '<span class="badge bg-success">✅ Lunas</span>',
                    'failed' => '<span class="badge bg-danger">❌ Gagal</span>'
                ];
                echo $status_badge[$row['payment_status']] ?? $row['payment_status'];
                ?>
            </td>
            <td><?= date('d/m/Y', strtotime($row['payment_date'])) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>