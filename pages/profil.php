<?php
session_start();
include '../config/database.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Ambil data admin
$query = "SELECT * FROM customers WHERE id = $admin_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// ===== INI PERBAIKAN =====
// Jika user tidak ditemukan, buat data default
if (!$user) {
    $user = [
        'id' => $admin_id,
        'name' => 'Administrator',
        'username' => 'admin',
        'email' => 'admin@email.com',
        'phone' => '08123456789',
        'address' => 'Jl. Sport Arena No. 1',
        'role' => 'admin',
        'profile_photo' => null
    ];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update Profil
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);

        // Cek username duplicate
        $check = "SELECT id FROM customers WHERE username = '$username' AND id != $admin_id";
        $check_result = mysqli_query($conn, $check);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "❌ Username sudah digunakan!";
        } else {
            $update = "UPDATE customers SET 
                       name = '$name',
                       username = '$username',
                       email = '$email',
                       phone = '$phone',
                       address = '$address'
                       WHERE id = $admin_id";

            if (mysqli_query($conn, $update)) {
                $message = "✅ Profil berhasil diperbarui!";
                $_SESSION['username'] = $username;
                $query = "SELECT * FROM customers WHERE id = $admin_id";
                $result = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = "❌ Gagal update!";
            }
        }
    }

    // Ganti Password
    if (isset($_POST['change_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $pass_query = "SELECT password FROM customers WHERE id = $admin_id";
        $pass_result = mysqli_query($conn, $pass_query);
        $pass_data = mysqli_fetch_assoc($pass_result);

        if ($pass_data['password'] != $old) {
            $error = "❌ Password lama salah!";
        } elseif (strlen($new) < 6) {
            $error = "❌ Password minimal 6 karakter!";
        } elseif ($new != $confirm) {
            $error = "❌ Konfirmasi password tidak cocok!";
        } else {
            $update = "UPDATE customers SET password = '$new' WHERE id = $admin_id";
            if (mysqli_query($conn, $update)) {
                $message = "✅ Password berhasil diubah!";
            } else {
                $error = "❌ Gagal ubah password!";
            }
        }
    }

    // Upload Foto
    if (isset($_POST['upload_photo'])) {
        $target_dir = "../../uploads/profil/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if ($_FILES['profile_photo']['error'] == 0) {
            $file_name = time() . '_' . basename($_FILES['profile_photo']['name']);
            $target_file = $target_dir . $file_name;
            $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if ($_FILES['profile_photo']['size'] <= 5000000 && in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
                    if (!empty($user['profile_photo']) && file_exists($target_dir . $user['profile_photo'])) {
                        unlink($target_dir . $user['profile_photo']);
                    }
                    $update = "UPDATE customers SET profile_photo = '$file_name' WHERE id = $admin_id";
                    if (mysqli_query($conn, $update)) {
                        $message = "✅ Foto berhasil diupload!";
                        $user['profile_photo'] = $file_name;
                    }
                } else {
                    $error = "❌ Gagal upload!";
                }
            } else {
                $error = "❌ Format/ukuran tidak sesuai! Max 5MB (JPG, PNG, GIF)";
            }
        } else {
            $error = "❌ Pilih file foto!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - SportArena</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar Custom */
        .navbar-custom {
            background: linear-gradient(135deg, #1a2332 0%, #2c3e50 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .navbar-custom .navbar-brand {
            color: #fff;
            font-weight: 700;
            font-size: 22px;
        }

        .navbar-custom .navbar-brand span {
            color: #4fc3f7;
        }

        .navbar-custom .nav-link {
            color: #aab8c5 !important;
            border-radius: 8px;
            padding: 8px 18px !important;
            margin: 0 2px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 14px;
        }

        .navbar-custom .nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff !important;
        }

        .navbar-custom .nav-link.active {
            background: rgba(79, 195, 247, 0.15);
            color: #4fc3f7 !important;
        }

        .navbar-custom .dropdown-menu {
            background: #2c3e50;
            border: none;
        }

        .navbar-custom .dropdown-item {
            color: #aab8c5;
        }

        .navbar-custom .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .navbar-custom .date-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logout-btn {
            color: #aab8c5 !important;
            padding: 6px 16px !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545 !important;
            border-color: rgba(220, 53, 69, 0.2);
        }

        /* Profile Card */
        .profile-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .profile-card-header {
            background: linear-gradient(135deg, #1a2332 0%, #2c3e50 100%);
            padding: 40px 30px 30px;
            text-align: center;
        }

        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 4px solid #4fc3f7;
            box-shadow: 0 4px 20px rgba(79, 195, 247, 0.3);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: #fff;
            font-size: 50px;
            font-weight: 700;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-card-header h2 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 16px;
            background: rgba(79, 195, 247, 0.2);
            color: #4fc3f7;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-file {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .btn-file input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .btn-outline-light-custom {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            transition: all 0.3s ease;
        }

        .btn-outline-light-custom:hover {
            border-color: #4fc3f7;
            color: #4fc3f7;
            background: rgba(79, 195, 247, 0.1);
        }

        .divider-custom {
            border: none;
            border-top: 2px dashed #e8ecf1;
            margin: 25px 0;
        }

        .form-label-custom {
            font-weight: 600;
            font-size: 13px;
            color: #555;
        }

        .form-label-custom i {
            color: #4fc3f7;
            margin-right: 6px;
        }

        .form-control-custom {
            border: 2px solid #e8ecf1;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            background: #fafbfc;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            border-color: #4fc3f7;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.1);
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #4fc3f7, #29b6f6);
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-gradient-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(41, 182, 246, 0.4);
            color: #fff;
        }

        .btn-custom-success {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            color: #fff;
        }

        .btn-custom-warning {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            color: #333;
        }

        .upload-section {
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .navbar-custom .navbar-nav {
                flex-wrap: wrap;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 36px;
            }

            .profile-card-header {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- ========== NAVBAR ========== -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand" href="dashboard.php">🏟️ Sport<span>Arena</span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-chart-simple me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_pembayaran.php"><i class="fas fa-credit-card me-1"></i> Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reservasi.php"><i class="fas fa-calendar-check me-1"></i> Reservasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lapangan.php"><i class="fas fa-futbol me-1"></i> Lapangan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pelanggan.php"><i class="fas fa-users me-1"></i> Pelanggan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profil.php"><i class="fas fa-user me-1"></i> Profil</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="date-text d-none d-sm-inline">
                        <i class="far fa-calendar-alt me-1"></i> <?= date('d/m/Y') ?>
                    </span>
                    <div class="avatar-circle">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <img src="../../uploads/profil/<?= $user['profile_photo'] ?>" alt="Foto">
                        <?php else: ?>
                            <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ========== MAIN ========== -->
    <main class="container py-4">

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="profile-card">

            <!-- HEADER -->
            <div class="profile-card-header">
                <div class="profile-avatar">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="../../uploads/profil/<?= $user['profile_photo'] ?>" alt="Foto Profil">
                    <?php else: ?>
                        <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h2><?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
                <span class="role-badge"><?= ucfirst($user['role'] ?? 'User') ?></span>

                <div class="upload-section">
                    <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center">
                        <div class="btn-file">
                            <button type="button" class="btn btn-outline-light-custom btn-sm" onclick="document.getElementById('fileInput').click();">
                                <i class="fas fa-camera"></i> Pilih Foto
                            </button>
                            <input type="file" id="fileInput" name="profile_photo" accept="image/*" style="display:none;" onchange="this.form.submit();">
                        </div>
                        <button type="submit" name="upload_photo" class="btn btn-success btn-sm">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                        <span class="text-white-50" style="font-size: 12px;">Max 5MB (JPG, PNG, GIF)</span>
                    </form>
                </div>
            </div>

            <!-- BODY -->
            <div class="profile-card-body p-4 p-md-5">

                <!-- Form Update Profil -->
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-user"></i> Nama Lengkap</label>
                                <input type="text" name="name" class="form-control form-control-custom"
                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-user-circle"></i> Username</label>
                                <input type="text" name="username" class="form-control form-control-custom"
                                       value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                                <?php if ($user['role'] == 'admin'): ?>
                                    <small class="text-muted"><i class="fas fa-info-circle"></i> Username tidak boleh "user"</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" class="form-control form-control-custom"
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-phone"></i> Telepon</label>
                                <input type="text" name="phone" class="form-control form-control-custom"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-map-marker-alt"></i> Alamat</label>
                                <textarea name="address" class="form-control form-control-custom" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="update_profile" class="btn btn-gradient-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>

                <hr class="divider-custom">

                <!-- Form Ganti Password -->
                <form method="POST">
                    <h4 class="mb-3" style="color: #1a2332;">
                        <i class="fas fa-key" style="color: #4fc3f7;"></i> Ganti Password
                    </h4>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-lock"></i> Password Lama</label>
                                <input type="password" name="old_password" class="form-control form-control-custom" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-lock"></i> Password Baru</label>
                                <input type="password" name="new_password" class="form-control form-control-custom" required minlength="6">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-custom"><i class="fas fa-check-circle"></i> Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" class="form-control form-control-custom" required>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="change_password" class="btn btn-custom-warning">
                            <i class="fas fa-key"></i> Ubah Password
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>