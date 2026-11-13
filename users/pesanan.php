<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Proses klik tombol Selesai
if (isset($_GET['selesai'])) {
    $id_selesai = $_GET['selesai'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE id = ? AND customer_id = ?");
    $stmt->execute([$id_selesai, $user_id]);
    header('Location: pesanan.php');
    exit();
}

// Ambil pesanan user
$query = "SELECT r.*, f.name as nama_lapangan 
          FROM reservations r 
          JOIN fields f ON r.field_id = f.id 
          WHERE r.customer_id = $user_id 
          ORDER BY r.reservation_date DESC, r.start_time DESC";
$pesanan = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - SportArena</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .navbar {
            background: #1a2332;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            text-decoration: none;
        }

        .logo span {
            color: #4fc3f7;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 5px;
        }

        .nav-links a {
            color: #aab8c5;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 15px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(79, 195, 247, 0.15);
            color: #4fc3f7;
        }

        .main {
            padding: 30px 5%;
            max-width: 1200px;
            margin: auto;
        }

        .header-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #1a2332;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            border-left: 6px solid #4fc3f7;
            position: relative;
        }

        .card.done {
            border-left-color: #4caf50;
            opacity: 0.7;
        }

        .card.waiting {
            border-left-color: #ff9800;
        }

        /* Warna Oranye untuk belum bayar */

        .card h3 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #1a2332;
        }

        .card .date {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
            display: block;
        }

        .time-box {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .time-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            flex: 1;
            text-align: center;
        }

        .time-item span {
            display: block;
            font-size: 11px;
            color: #777;
            text-transform: uppercase;
        }

        .time-item strong {
            font-size: 16px;
            color: #333;
        }

        .countdown {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }

        .countdown.safe {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .countdown.waiting {
            background: #fff3e0;
            color: #e65100;
        }

        .btn-selesai {
            display: block;
            width: 100%;
            padding: 12px;
            background: #4caf50;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }

        .btn-selesai:hover {
            background: #43a047;
            transform: translateY(-2px);
        }

        .empty {
            text-align: center;
            padding: 50px;
            background: #fff;
            border-radius: 15px;
            color: #999;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="dashboard_users.php" class="logo">🏟️ Sport<span>Arena</span></a>
        <div class="nav-links">
            <a href="dashboard_users.php">Dashboard</a>
            <a href="pesanan.php" class="active">Pesanan</a>
            <a href="pembayaran.php">Pembayaran</a>
            <a href="riwayat.php">Riwayat</a>
            <a href="profil.php">Profil</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2 class="header-title">⏰ Pesanan Aktif & Alarm Selesai</h2>

        <?php if (count($pesanan) > 0): ?>
            <div class="grid">
                <?php foreach ($pesanan as $p):
                    $status = strtolower($p['status']);
                    $waktu_selesai = $p['reservation_date'] . 'T' . $p['end_time'];

                    // Logika Tampilan
                    $is_waiting = ($status == 'booked');
                    $is_active = ($status == 'confirmed');
                    $is_done = ($status == 'completed' || $status == 'cancelled');
                    ?>
                    <div class="card <?= $is_done ? 'done' : ($is_waiting ? 'waiting' : '') ?>">
                        <h3><?= htmlspecialchars($p['nama_lapangan']) ?></h3>
                        <span class="date">Kode: <?= htmlspecialchars($p['reservation_code']) ?> | Status:
                            <?= strtoupper($status) ?></span>

                        <div class="time-box">
                            <div class="time-item">
                                <span>Mulai</span>
                                <strong><?= $p['start_time'] ?></strong>
                            </div>
                            <div class="time-item">
                                <span>Selesai</span>
                                <strong><?= $p['end_time'] ?></strong>
                            </div>
                        </div>

                        <?php if ($is_waiting): ?>
                            <!-- BELUM BAYAR -->
                            <div class="countdown waiting">
                                ⏳ Menunggu Pembayaran & Konfirmasi Admin
                            </div>
                        <?php elseif ($is_active): ?>
                            <!-- SUDAH BAYAR (ALARM JALAN) -->
                            <div class="countdown" id="countdown-<?= $p['id'] ?>" data-end="<?= $waktu_selesai ?>"
                                data-status="<?= $status ?>">
                                Menghitung waktu...
                            </div>

                            <div id="btn-selesai-<?= $p['id'] ?>" style="display: none;">
                                <a href="pesanan.php?selesai=<?= $p['id'] ?>" class="btn-selesai"
                                    onclick="return confirm('Apakah kamu sudah meninggalkan lapangan?')">✅ Selesai & Tinggalkan
                                    Lapangan</a>
                            </div>
                        <?php else: ?>
                            <!-- SUDAH SELESAI -->
                            <div class="countdown safe">✅ Pesanan Selesai</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <h2 style="font-size: 50px; margin-bottom: 10px;">📭</h2>
                <p>Belum ada pesanan aktif.</p>
                <br>
                <a href="dashboard_users.php" style="color: #4fc3f7; text-decoration: none;">&larr; Pesan Lapangan
                    Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        if ("Notification" in window) {
            if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                Notification.requestPermission();
            }
        }

        function updateCountdowns() {
            const countdowns = document.querySelectorAll('.countdown[data-end]');

            countdowns.forEach(el => {
                // AMBIL STATUS DARI ATRIBUT DATA-STATUS
                const status = el.getAttribute('data-status');

                // JIKA BUKAN CONFIRMED, JANGAN HITUNG MUNDUR
                if (status !== 'confirmed') return;

                const endTime = new Date(el.getAttribute('data-end')).getTime();
                const now = new Date().getTime();
                const distance = endTime - now;
                const id = el.id.replace('countdown-', '');

                if (distance < 0) {
                    el.innerHTML = "🔔 WAKTU HABIS! SEGERA TINGGALKAN LAPANGAN!";
                    el.classList.remove('safe');
                    el.style.backgroundColor = "#c62828";
                    el.style.color = "#fff";
                    el.style.animation = "blink 1s infinite";

                    const btnSelesai = document.getElementById('btn-selesai-' + id);
                    if (btnSelesai) btnSelesai.style.display = "block";

                    playAlarm();
                    sendNotification();
                } else {
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    el.innerHTML = `⏳ Sisa Waktu: ${hours}j ${minutes}m ${seconds}s`;
                    el.classList.add('safe');
                }
            });
        }

        function playAlarm() {
            let audio = new Audio('https://actions.google.com/sounds/v1/alarms/digital_watch_alarm_long.ogg');
            audio.play().catch(e => console.log("Audio diblokir browser"));
        }

        function sendNotification() {
            if ("Notification" in window && Notification.permission === "granted") {
                new Notification("⏰ Waktu Habis!", {
                    body: "Waktu sewa lapanganmu sudah habis. Segera tinggalkan lapangan!",
                    icon: "https://cdn-icons-png.flaticon.com/512/2921/2921822.png"
                });
            }
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns();
    </script>

    <style>
        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</body>

</html>