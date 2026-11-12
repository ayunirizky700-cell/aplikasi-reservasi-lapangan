<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$pesan = '';

// ==========================================
// PROSES TAMBAH LAPANGAN (Dengan Upload Foto)
// ==========================================
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $jenis = $_POST['jenis'];
    $harga = $_POST['harga'];
    $status = 'available';
    $gambar = '';

    // Proses upload gambar
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $gambar = time() . '_' . $_FILES['gambar']['name']; // Beri timestamp biar nama unik
        $target = "../uploads/lapangan/" . basename($gambar);
        move_uploaded_file($_FILES['gambar']['tmp_name'], $target);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO fields (name, sport_type, price_per_hour, status, gambar) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $jenis, $harga, $status, $gambar]);
        $pesan = '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">✅ Lapangan & Foto berhasil ditambahkan!</div>';
    } catch (Exception $e) {
        $pesan = '<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// ==========================================
// PROSES HAPUS LAPANGAN
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];

    // Hapus file gambarnya juga dari folder
    $stmt_gambar = $pdo->prepare("SELECT gambar FROM fields WHERE id = ?");
    $stmt_gambar->execute([$id_hapus]);
    $gambar_lama = $stmt_gambar->fetchColumn();
    if ($gambar_lama && file_exists("../uploads/lapangan/" . $gambar_lama)) {
        unlink("../uploads/lapangan/" . $gambar_lama);
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM fields WHERE id = ?");
        $stmt->execute([$id_hapus]);
        $pesan = '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">✅ Lapangan berhasil dihapus!</div>';
    } catch (Exception $e) {
        $pesan = '<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">❌ Gagal hapus (mungkin lapangan sedang dipesan).</div>';
    }
}

// ==========================================
// PROSES EDIT LAPANGAN (Dengan Ganti Foto)
// ==========================================
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $jenis = $_POST['jenis'];
    $harga = $_POST['harga'];
    $gambar_lama = $_POST['gambar_lama'];
    $gambar_baru = $gambar_lama; // Default pakai gambar lama

    // Jika user upload gambar baru
    if (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == 0) {
        $gambar_baru = time() . '_' . $_FILES['gambar_baru']['name'];
        $target = "../uploads/lapangan/" . basename($gambar_baru);

        if (move_uploaded_file($_FILES['gambar_baru']['tmp_name'], $target)) {
            // Hapus gambar lama dari folder jika ada
            if (!empty($gambar_lama) && file_exists("../uploads/lapangan/" . $gambar_lama)) {
                unlink("../uploads/lapangan/" . $gambar_lama);
            }
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE fields SET name = ?, sport_type = ?, price_per_hour = ?, gambar = ? WHERE id = ?");
        $stmt->execute([$nama, $jenis, $harga, $gambar_baru, $id]);
        $pesan = '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px;">✅ Lapangan & Foto berhasil diupdate!</div>';
    } catch (Exception $e) {
        $pesan = '<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px;">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// Ambil data lapangan
$lapangan = $pdo->query("SELECT * FROM fields ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - Admin</title>
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
        }

        .nav-links a {
            color: #aab8c5;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 15px;
            border-radius: 8px;
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h2 {
            font-size: 24px;
            color: #1a2332;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            padding: 20px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            border: none;
            transition: 0.3s;
        }

        .btn-primary {
            background: #4fc3f7;
            color: white;
        }

        .btn-primary:hover {
            background: #29b6f6;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
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
            border-radius: 15px;
            width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #1a2332;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: #4fc3f7;
            outline: none;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="dashboard.php" class="logo">🏟️ Sport<span>Arena</span> (Admin)</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="kelola_lapangan.php" class="active">Lapangan</a>
            <a href="kelola_reservasi.php">Reservasi</a>
            <a href="kelola_pembayaran.php">Pembayaran</a>
            <a href="../logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="header">
            <h2>🏟️ Kelola Lapangan</h2>
            <button class="btn btn-primary" onclick="openModal('tambah')">➕ Tambah Lapangan</button>
        </div>

        <?= $pesan ?>

        <div class="card">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Lapangan</th>
                            <th>Jenis</th>
                            <th>Harga / Jam</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lapangan as $lap): ?>
                            <tr>
                                <td>#<?= $lap['id'] ?></td>
                                <td><strong><?= htmlspecialchars($lap['name']) ?></strong></td>
                                <td><?= htmlspecialchars($lap['sport_type'] ?? '-') ?></td>
                                <td>Rp <?= number_format($lap['price_per_hour'], 0, ',', '.') ?></td>
                                <td>
                                    <button class="btn btn-edit"
                                        onclick="editData(<?= $lap['id'] ?>, '<?= addslashes($lap['name']) ?>', '<?= addslashes($lap['sport_type'] ?? '') ?>', <?= $lap['price_per_hour'] ?>, '<?= addslashes($lap['gambar'] ?? '') ?>')">✏️
                                        Edit</button>
                                    <a href="kelola_lapangan.php?hapus=<?= $lap['id'] ?>" class="btn btn-danger"
                                        onclick="return confirm('Yakin ingin menghapus lapangan ini?')">🗑️ Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <h3>Tambah Lapangan Baru</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama Lapangan</label>
                    <input type="text" name="nama" required placeholder="Contoh: Lapangan Futsal D">
                </div>
                <div class="form-group">
                    <label>Jenis Olahraga</label>
                    <input type="text" name="jenis" required placeholder="Contoh: Futsal, Voli, Basket">
                </div>
                <div class="form-group">
                    <label>Harga per Jam (Rp)</label>
                    <input type="number" name="harga" required placeholder="Contoh: 75000">
                </div>
                <div class="form-group">
                    <label>Foto Lapangan</label>
                    <input type="file" name="gambar" accept="image/*" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('tambah')">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <h3>Edit Lapangan</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="gambar_lama" id="edit_gambar_lama">

                <div class="form-group">
                    <label>Nama Lapangan</label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Jenis Olahraga</label>
                    <input type="text" name="jenis" id="edit_jenis" required>
                </div>
                <div class="form-group">
                    <label>Harga per Jam (Rp)</label>
                    <input type="number" name="harga" id="edit_harga" required>
                </div>
                <div class="form-group">
                    <label>Ganti Foto (Opsional)</label>
                    <input type="file" name="gambar_baru" accept="image/*">
                    <small style="color: #999; font-size: 11px;">Biarkan kosong jika tidak ingin mengubah foto.</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('edit')">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById('modal' + id.charAt(0).toUpperCase() + id.slice(1)).classList.add('active'); }
        function closeModal(id) { document.getElementById('modal' + id.charAt(0).toUpperCase() + id.slice(1)).classList.remove('active'); }

        function editData(id, nama, jenis, harga, gambar) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_jenis').value = jenis;
            document.getElementById('edit_harga').value = harga;
            document.getElementById('edit_gambar_lama').value = gambar;
            openModal('edit');
        }
    </script>
</body>

</html>