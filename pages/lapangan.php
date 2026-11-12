<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// ==========================================
// PROSES TAMBAH LAPANGAN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $name = $_POST['name'];
    $sport_type = $_POST['sport_type'];
    $price_per_hour = $_POST['price_per_hour'];
    $status = $_POST['status'];
    $description = $_POST['description'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO fields (name, sport_type, price_per_hour, status, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $sport_type, $price_per_hour, $status, $description]);
    
    header('Location: lapangan.php?success=tambah');
    exit();
}

// ==========================================
// PROSES EDIT LAPANGAN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $sport_type = $_POST['sport_type'];
    $price_per_hour = $_POST['price_per_hour'];
    $status = $_POST['status'];
    $description = $_POST['description'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE fields SET name = ?, sport_type = ?, price_per_hour = ?, status = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $sport_type, $price_per_hour, $status, $description, $id]);
    
    header('Location: lapangan.php?success=edit');
    exit();
}

// ==========================================
// PROSES HAPUS LAPANGAN
// ==========================================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM fields WHERE id = ?");
    $stmt->execute([$id]);
    
    header('Location: lapangan.php?success=hapus');
    exit();
}

// ==========================================
// AMBIL DATA LAPANGAN
// ==========================================
$fields = $pdo->query("SELECT * FROM fields ORDER BY id DESC")->fetchAll();

// Data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM fields WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}

// Statistik
$total = count($fields);
$available = $pdo->query("SELECT COUNT(*) FROM fields WHERE status = 'available'")->fetchColumn();
$maintenance = $pdo->query("SELECT COUNT(*) FROM fields WHERE status = 'maintenance'")->fetchColumn();

// Pesan sukses
$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'tambah') $success = '✅ Lapangan berhasil ditambahkan!';
    if ($_GET['success'] == 'edit') $success = '✅ Lapangan berhasil diupdate!';
    if ($_GET['success'] == 'hapus') $success = '✅ Lapangan berhasil dihapus!';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
        }

        /* ==================== NAVBAR ==================== */
        .navbar {
            background: #1a2332;
            color: #fff;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 65px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar .logo {
            font-size: 20px;
            font-weight: 700;
        }
        .navbar .logo span {
            color: #4fc3f7;
        }
        .navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .navbar .nav-links a {
            color: #aab8c5;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: 0.3s;
            font-size: 14px;
        }
        .navbar .nav-links a:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        .navbar .nav-links a.active {
            background: rgba(79, 195, 247, 0.15);
            color: #4fc3f7;
        }
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .navbar .user-info .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #4fc3f7;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .navbar .user-info .logout-btn {
            color: #aab8c5;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            transition: 0.3s;
            font-size: 14px;
        }
        .navbar .user-info .logout-btn:hover {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        /* ==================== MAIN ==================== */
        .main {
            padding: 25px 35px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 22px;
            font-weight: 600;
            color: #1a2332;
        }
        .header h1 small {
            font-size: 14px;
            font-weight: 400;
            color: #6a7a8a;
            display: block;
            margin-top: 2px;
        }
        .btn-primary {
            background: #4fc3f7;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-primary:hover {
            background: #29b6f6;
            transform: translateY(-2px);
        }

        /* STATS MINI */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }
        .stat-mini {
            background: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            border-left: 4px solid #4fc3f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-mini .info h3 {
            font-size: 12px;
            color: #6a7a8a;
            font-weight: 400;
            margin-bottom: 3px;
        }
        .stat-mini .info .number {
            font-size: 24px;
            font-weight: 700;
            color: #1a2332;
        }
        .stat-mini .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .stat-mini.border-blue { border-left-color: #2196f3; }
        .stat-mini.border-green { border-left-color: #4caf50; }
        .stat-mini.border-orange { border-left-color: #ff9800; }
        .stat-mini .icon-box.blue { background: #e3f2fd; }
        .stat-mini .icon-box.green { background: #e8f5e9; }
        .stat-mini .icon-box.orange { background: #fff3e0; }

        /* CARD */
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1a2332;
        }
        .card-body {
            padding: 15px 20px 20px;
            overflow-x: auto;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #f5f5f5;
        }
        th {
            color: #6a7a8a;
            font-weight: 500;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:hover {
            background: #f8f9fa;
        }

        /* STATUS */
        .status {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-available {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-maintenance {
            background: #fff3e0;
            color: #e65100;
        }

        .btn-sm {
            padding: 5px 12px;
            border: none;
            border-radius: 5px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        .btn-edit {
            background: #2196f3;
            color: #fff;
        }
        .btn-edit:hover {
            background: #1976d2;
        }
        .btn-danger {
            background: #f44336;
            color: #fff;
        }
        .btn-danger:hover {
            background: #d32f2f;
        }
        .btn-success {
            background: #4caf50;
            color: #fff;
        }
        .btn-success:hover {
            background: #388e3c;
        }

        /* ALERT */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-content h2 {
            margin-bottom: 20px;
            color: #1a2332;
        }
        .modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 13px;
            color: #333;
        }
        .modal-content input,
        .modal-content select,
        .modal-content textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .modal-content input:focus,
        .modal-content select:focus,
        .modal-content textarea:focus {
            outline: none;
            border-color: #4fc3f7;
        }
        .modal-content textarea {
            min-height: 60px;
            resize: vertical;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .modal-actions button {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        .modal-actions .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }
        .modal-actions .btn-cancel:hover {
            background: #bdbdbd;
        }
        .modal-actions .btn-submit {
            background: #4fc3f7;
            color: #fff;
        }
        .modal-actions .btn-submit:hover {
            background: #29b6f6;
        }

        .text-muted {
            color: #999;
        }
        .text-center {
            text-align: center;
        }

        @media (max-width: 768px) {
            .navbar { flex-wrap: wrap; height: auto; padding: 10px 15px; gap: 10px; }
            .navbar .nav-links { flex-wrap: wrap; justify-content: center; }
            .main { padding: 15px; }
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .stats-mini { grid-template-columns: 1fr; }
            table { font-size: 12px; }
            th, td { padding: 6px 4px; }
        }
    </style>
</head>
<body>

<!-- ==================== NAVBAR ==================== -->
<div class="navbar">
    <div class="logo">🏟️ Sport<span>Arena</span></div>
    <div class="nav-links">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="lapangan.php"class="active">🏟️ Lapangan</a>
        <a href="reservasi.php">📅 Reservasi</a>
        <a href="../pages/kelola_pembayaran.php">💰 Pembayaran</a>
        <a href="pelanggan.php" >👥 Pelanggan</a>
        <a href="profil.php">👤 Profil</a>
        
    </div>
    <div class="user-info">
        <span style="color: #aab8c5; font-size: 13px;"><?= date('d/m/Y') ?></span>
        <a href="../logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<!-- ==================== MAIN ==================== -->
<div class="main">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>
                🏟️ Kelola Lapangan
                <small>Kelola semua lapangan olahraga di sini</small>
            </h1>
        </div>
        <button class="btn-primary" onclick="openModal('tambah')">➕ Tambah Lapangan</button>
    </div>

    <!-- STATISTIK -->
    <div class="stats-mini">
        <div class="stat-mini border-blue">
            <div class="info">
                <h3>Total Lapangan</h3>
                <div class="number"><?= $total ?></div>
            </div>
            <div class="icon-box blue">🏟️</div>
        </div>
        <div class="stat-mini border-green">
            <div class="info">
                <h3>Tersedia</h3>
                <div class="number"><?= $available ?></div>
            </div>
            <div class="icon-box green">✅</div>
        </div>
        <div class="stat-mini border-orange">
            <div class="info">
                <h3>Maintenance</h3>
                <div class="number"><?= $maintenance ?></div>
            </div>
            <div class="icon-box orange">🔧</div>
        </div>
    </div>

    <!-- ALERT -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="card">
        <div class="card-header">
            <h3>📋 Daftar Lapangan</h3>
            <span class="text-muted">Total: <?= $total ?> lapangan</span>
        </div>
        <div class="card-body">
            <?php if (count($fields) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lapangan</th>
                        <th>Jenis Olahraga</th>
                        <th>Harga/Jam</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $f): ?>
                    <tr>
                        <td>#<?= $f['id'] ?></td>
                        <td><?= htmlspecialchars($f['name']) ?></td>
                        <td><?= htmlspecialchars($f['sport_type'] ?? '-') ?></td>
                        <td>Rp <?= number_format($f['price_per_hour'], 0, ',', '.') ?></td>
                        <td>
                            <span class="status status-<?= $f['status'] ?>">
                                <?= strtoupper($f['status'] ?? 'available') ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-sm btn-edit" onclick="editLapangan(<?= $f['id'] ?>)">✏️ Edit</button>
                            <a href="?hapus=<?= $f['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Yakin hapus lapangan ini?')">🗑️ Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-center text-muted" style="padding: 30px 0;">
                <span style="font-size: 40px; display: block;">🏟️</span>
                Belum ada lapangan. Klik "Tambah Lapangan" untuk menambahkan.
            </p>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ==================== MODAL TAMBAH ==================== -->
<div id="modalTambah" class="modal">
    <div class="modal-content">
        <h2>➕ Tambah Lapangan</h2>
        <form method="POST">
            <input type="hidden" name="tambah" value="1">
            
            <label>Nama Lapangan</label>
            <input type="text" name="name" placeholder="Contoh: Lapangan A" required>
            
            <label>Jenis Olahraga</label>
            <input type="text" name="sport_type" placeholder="Contoh: Futsal, Basket, Badminton">
            
            <label>Harga per Jam (Rp)</label>
            <input type="number" name="price_per_hour" placeholder="Contoh: 100000" required>
            
            <label>Status</label>
            <select name="status">
                <option value="available">Available</option>
                <option value="maintenance">Maintenance</option>
            </select>
            
            <label>Deskripsi (Opsional)</label>
            <textarea name="description" placeholder="Deskripsi lapangan..."></textarea>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('tambah')">Batal</button>
                <button type="submit" class="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL EDIT ==================== -->
<div id="modalEdit" class="modal">
    <div class="modal-content">
        <h2>✏️ Edit Lapangan</h2>
        <form method="POST">
            <input type="hidden" name="edit" value="1">
            <input type="hidden" name="id" id="edit_id">
            
            <label>Nama Lapangan</label>
            <input type="text" name="name" id="edit_name" required>
            
            <label>Jenis Olahraga</label>
            <input type="text" name="sport_type" id="edit_sport_type">
            
            <label>Harga per Jam (Rp)</label>
            <input type="number" name="price_per_hour" id="edit_price" required>
            
            <label>Status</label>
            <select name="status" id="edit_status">
                <option value="available">Available</option>
                <option value="maintenance">Maintenance</option>
            </select>
            
            <label>Deskripsi (Opsional)</label>
            <textarea name="description" id="edit_description" placeholder="Deskripsi lapangan..."></textarea>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('edit')">Batal</button>
                <button type="submit" class="btn-submit">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ==========================================
    // MODAL FUNCTIONS
    // ==========================================
    function openModal(type) {
        if (type === 'tambah') {
            document.getElementById('modalTambah').classList.add('active');
        }
    }

    function closeModal(type) {
        if (type === 'tambah') {
            document.getElementById('modalTambah').classList.remove('active');
        } else if (type === 'edit') {
            document.getElementById('modalEdit').classList.remove('active');
        }
    }

    function editLapangan(id) {
        // Ambil data dari server via fetch
        fetch('get_lapangan.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_name').value = data.name;
                document.getElementById('edit_sport_type').value = data.sport_type || '';
                document.getElementById('edit_price').value = data.price_per_hour;
                document.getElementById('edit_status').value = data.status || 'available';
                document.getElementById('edit_description').value = data.description || '';
                document.getElementById('modalEdit').classList.add('active');
            })
            .catch(error => {
                alert('Gagal mengambil data lapangan!');
                console.error(error);
            });
    }

    // Close modal dengan klik di luar
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
</script>

</body>
</html>