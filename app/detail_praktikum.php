<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "mahasiswa") {
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : 0;
$user_id = $_SESSION["id"];
$nama_praktikum = "Praktikum Tidak Ditemukan";
$modul_list = [];
$pendaftaran_id = null;

if ($praktikum_id > 0) {
    $sql_get_pendaftaran_id = "SELECT id FROM pendaftaran_praktikum WHERE user_id = ? AND mata_praktikum_id = ?";
    if ($stmt_pendaftaran = mysqli_prepare($link, $sql_get_pendaftaran_id)) {
        mysqli_stmt_bind_param($stmt_pendaftaran, "ii", $user_id, $praktikum_id);
        mysqli_stmt_execute($stmt_pendaftaran);
        mysqli_stmt_store_result($stmt_pendaftaran);
        if (mysqli_stmt_num_rows($stmt_pendaftaran)) {
            mysqli_stmt_bind_result($stmt_pendaftaran, $fetched_pendaftaran_id);
            mysqli_stmt_fetch($stmt_pendaftaran);
            $pendaftaran_id = $fetched_pendaftaran_id;
        }
        mysqli_stmt_close($stmt_pendaftaran);
    }

    if ($pendaftaran_id) {
        $sql_praktikum = "SELECT nama_praktikum, deskripsi FROM mata_praktikum WHERE id = ?";
        $stmt_praktikum = mysqli_prepare($link, $sql_praktikum);
        if ($stmt_praktikum) {
            mysqli_stmt_bind_param($stmt_praktikum, "i", $praktikum_id);
            mysqli_stmt_execute($stmt_praktikum);
            mysqli_stmt_bind_result($stmt_praktikum, $nama_praktikum, $deskripsi_praktikum);
            mysqli_stmt_fetch($stmt_praktikum);
            mysqli_stmt_close($stmt_praktikum);
        }

        $sql_modul = "SELECT mp.id AS modul_id, mp.judul_modul, mp.deskripsi_modul, mp.nama_file_materi,
                      lp.id AS laporan_id, lp.nama_file_laporan, lp.nilai, lp.feedback, lp.status AS laporan_status
                      FROM modul_praktikum mp
                      LEFT JOIN laporan_praktikum lp ON mp.id = lp.modul_id AND lp.pendaftaran_id = ?
                      WHERE mp.mata_praktikum_id = ?
                      ORDER BY mp.judul_modul ASC";
        
        $stmt_modul = mysqli_prepare($link, $sql_modul);
        if ($stmt_modul) {
            mysqli_stmt_bind_param($stmt_modul, "ii", $pendaftaran_id, $praktikum_id);
            mysqli_stmt_execute($stmt_modul);
            $result_modul = mysqli_stmt_get_result($stmt_modul);
            while ($row = mysqli_fetch_assoc($result_modul)) {
                $modul_list[] = $row;
            }
            mysqli_stmt_close($stmt_modul);
        }
    } else {
        header("location: praktikum_saya.php");
        exit;
    }
} else {
    header("location: praktikum_saya.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_laporan'])) {
    $modul_id_laporan = (int)($_POST['modul_id'] ?? 0);
    $upload_error = "";
    $upload_success = "";

    if ($modul_id_laporan > 0 && $pendaftaran_id) {
        $target_dir = "../public/laporan_praktikum/";
        $allowed_types = ["pdf", "doc", "docx"];
        $max_size = 10 * 1024 * 1024;

        if ($_FILES["file_laporan"]["error"] == UPLOAD_ERR_OK) {
            $original_name = basename($_FILES["file_laporan"]["name"]);
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $file_name = uniqid('laporan_') . ".$file_extension";
            $target_file = $target_dir . $file_name;

            if (!in_array($file_extension, $allowed_types)) {
                $upload_error = "Hanya file PDF, DOC, DOCX yang diizinkan";
            } elseif ($_FILES["file_laporan"]["size"] > $max_size) {
                $upload_error = "Ukuran file maksimal 10MB";
            } elseif (move_uploaded_file($_FILES["file_laporan"]["tmp_name"], $target_file)) {
                $file_type = $_FILES["file_laporan"]["type"];
                $file_size = $_FILES["file_laporan"]["size"];
                
                $sql_check = "SELECT id, nama_file_laporan FROM laporan_praktikum 
                              WHERE pendaftaran_id = ? AND modul_id = ?";
                $stmt_check = mysqli_prepare($link, $sql_check);
                if ($stmt_check) {
                    mysqli_stmt_bind_param($stmt_check, "ii", $pendaftaran_id, $modul_id_laporan);
                    mysqli_stmt_execute($stmt_check);
                    mysqli_stmt_store_result($stmt_check);
                    
                    if (mysqli_stmt_num_rows($stmt_check)) {
                        mysqli_stmt_bind_result($stmt_check, $laporan_id, $old_file);
                        mysqli_stmt_fetch($stmt_check);
                        
                        if ($old_file && file_exists($target_dir . $old_file)) {
                            unlink($target_dir . $old_file);
                        }
                        
                        $sql_update = "UPDATE laporan_praktikum 
                                       SET nama_file_laporan = ?, tipe_file_laporan = ?, 
                                           ukuran_file_laporan = ?, tanggal_upload = NOW(),
                                           nilai = NULL, feedback = NULL, status = 'pending'
                                       WHERE id = ?";
                        $stmt_update = mysqli_prepare($link, $sql_update);
                        if ($stmt_update) {
                            mysqli_stmt_bind_param($stmt_update, "sssi", $file_name, $file_type, $file_size, $laporan_id);
                            mysqli_stmt_execute($stmt_update);
                            mysqli_stmt_close($stmt_update);
                            $upload_success = "Laporan berhasil diperbarui";
                        }
                    } else {
                        $sql_insert = "INSERT INTO laporan_praktikum 
                                      (pendaftaran_id, modul_id, nama_file_laporan, tipe_file_laporan, ukuran_file_laporan)
                                      VALUES (?, ?, ?, ?, ?)";
                        $stmt_insert = mysqli_prepare($link, $sql_insert);
                        if ($stmt_insert) {
                            mysqli_stmt_bind_param($stmt_insert, "iissi", $pendaftaran_id, $modul_id_laporan, $file_name, $file_type, $file_size);
                            mysqli_stmt_execute($stmt_insert);
                            mysqli_stmt_close($stmt_insert);
                            $upload_success = "Laporan berhasil diunggah";
                        }
                    }
                    mysqli_stmt_close($stmt_check);
                }
            } else {
                $upload_error = "Gagal mengunggah file";
            }
        } else {
            $upload_error = "Pilih file laporan";
        }
    } else {
        $upload_error = "Modul tidak valid";
    }

    $_SESSION['flash_message'] = $upload_success ?: $upload_error;
    header("location: detail_praktikum.php?praktikum_id=$praktikum_id");
    exit;
}

$flash_message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Praktikum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out forwards; }
        .card-hover { transition: transform 0.3s, box-shadow 0.3s; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-dinilai {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background-color: #fef9c3;
            color: #854d0e;
        }
    </style>
</head>
<body class="flex h-screen bg-gray-100 overflow-hidden">
    <aside class="w-64 bg-white shadow-lg flex-shrink-0">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-blue-600 mb-6">E - Learning</h2>
            <nav class="space-y-4">
                <a href="dashboard_mahasiswa.php"
                   class="flex items-center px-4 py-2 text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M4 10v10h16V10"/>
                    </svg>
                    Dashboard
                </a>
                <a href="praktikum_saya.php"
                   class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6a2 2 0 012 2v0a2 2 0 01-2 2H9a2 2 0 01-2-2v0a2 2 0 012-2zM9 12h6M9 16h6"/>
                    </svg>
                    Praktikum Saya
                </a>
                <a href="../public/katalog_praktikum.php"
                   class="flex items-center px-4 py-2 text-gray-700 hover:text-green-600 hover:bg-gray-50 rounded-lg transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M3 12h18M3 19h18"/>
                    </svg>
                    Katalog Praktikum
                </a>
                <a href="../public/logout.php"
                   class="flex items-center px-4 py-2 text-red-600 hover:text-white hover:bg-red-600 border border-red-600 rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5"/>
                    </svg>
                    Logout
                </a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($nama_praktikum) ?></h1>
                <p class="text-gray-600 mt-2"><?= htmlspecialchars($deskripsi_praktikum) ?></p>
            </div>
            <a href="praktikum_saya.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>

        <?php if ($flash_message): ?>
            <div class="mb-6 px-4 py-3 rounded bg-yellow-100 text-yellow-700 border border-yellow-400 animate-fade-in">
                <?= htmlspecialchars($flash_message) ?>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php if ($modul_list): ?>
                <?php foreach ($modul_list as $modul): ?>
                    <div class="bg-white rounded-2xl shadow-md overflow-hidden card-hover animate-fade-in">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($modul['judul_modul']) ?></h3>
                                    <p class="text-gray-600 mt-2"><?= htmlspecialchars($modul['deskripsi_modul']) ?></p>
                                </div>
                                <div class="bg-gray-200 border-2 border-dashed rounded-xl w-16 h-16"></div>
                            </div>

                            <div class="mb-6">
                                <h4 class="text-lg font-semibold mb-2 flex items-center">
                                    <i class="fas fa-book mr-2 text-blue-500"></i> Materi Praktikum
                                </h4>
                                <?php if ($modul['nama_file_materi']): ?>
                                    <a href="../public/materi_modul/<?= htmlspecialchars($modul['nama_file_materi']) ?>" 
                                       target="_blank"
                                       class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                                        <i class="fas fa-file-download mr-2"></i> Unduh Materi
                                    </a>
                                <?php else: ?>
                                    <p class="text-gray-500">Materi belum tersedia</p>
                                <?php endif; ?>
                            </div>

                            <div class="pt-4 border-t">
                                <h4 class="text-lg font-semibold mb-2 flex items-center">
                                    <i class="fas fa-file-alt mr-2 text-blue-500"></i> Laporan Praktikum
                                </h4>
                                
                                <?php if ($modul['laporan_id']): ?>
                                    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <h5 class="font-medium text-gray-700 mb-1">File Laporan</h5>
                                            <div class="flex items-center">
                                                <i class="fas fa-file-pdf text-red-500 text-xl mr-2"></i>
                                                <a href="../public/laporan_praktikum/<?= htmlspecialchars($modul['nama_file_laporan']) ?>" 
                                                   target="_blank"
                                                   class="text-blue-600 hover:text-blue-800 truncate">
                                                   <?= htmlspecialchars($modul['nama_file_laporan']) ?>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <h5 class="font-medium text-gray-700 mb-1">Status</h5>
                                            <span class="status-badge <?= $modul['laporan_status'] === 'dinilai' ? 'status-dinilai' : 'status-pending' ?>">
                                                <i class="fas <?= $modul['laporan_status'] === 'dinilai' ? 'fa-check-circle' : 'fa-clock' ?> mr-1"></i>
                                                <?= ucfirst(htmlspecialchars($modul['laporan_status'])) ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($modul['laporan_status'] === 'dinilai'): ?>
                                            <div>
                                                <h5 class="font-medium text-gray-700 mb-1">Nilai</h5>
                                                <div class="text-3xl font-bold text-green-600"><?= htmlspecialchars($modul['nilai']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($modul['laporan_status'] === 'dinilai'): ?>
                                        <div class="mb-6">
                                            <h5 class="font-medium text-gray-700 mb-1">Feedback</h5>
                                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                <p class="text-gray-800"><?= htmlspecialchars($modul['feedback']) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 mb-6">Belum ada laporan diunggah</p>
                                <?php endif; ?>
                                
                                <form method="post" enctype="multipart/form-data" action="detail_praktikum.php?praktikum_id=<?= $praktikum_id ?>">
                                    <input type="hidden" name="modul_id" value="<?= $modul['modul_id'] ?>">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Unggah Laporan:</label>
                                        <div class="flex items-center">
                                            <label class="block w-full">
                                                <input type="file" name="file_laporan" 
                                                       class="block w-full text-sm text-gray-500
                                                              file:mr-4 file:py-2 file:px-4
                                                              file:rounded file:border-0
                                                              file:text-sm file:font-semibold
                                                              file:bg-blue-50 file:text-blue-700
                                                              hover:file:bg-blue-100"
                                                       accept=".pdf,.doc,.docx" 
                                                       required>
                                            </label>
                                        </div>
                                        <p class="text-gray-500 text-xs mt-1">Format: PDF/DOC/DOCX (Maks. 10MB)</p>
                                    </div>
                                    <button type="submit" name="submit_laporan" 
                                            class="w-full md:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center justify-center">
                                        <i class="fas fa-cloud-upload-alt mr-2"></i> 
                                        <?= $modul['laporan_id'] ? 'Perbarui Laporan' : 'Unggah Laporan' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-md p-8 text-center animate-fade-in">
                    <div class="mx-auto w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-book text-gray-500 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Belum ada modul</h3>
                    <p class="text-gray-500">Tidak ada modul yang tersedia untuk praktikum ini</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>