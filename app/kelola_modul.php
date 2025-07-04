<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../public/login.php");
    exit;
}

if($_SESSION["role"] !== "asisten"){
    header("location: dashboard_mahasiswa.php");
    exit;
}

require_once '../includes/config.php';

$judul_modul = $deskripsi_modul = "";
$judul_modul_err = $deskripsi_modul_err = $file_materi_err = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id_edit = isset($_GET['id']) ? $_GET['id'] : null;
$mata_praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : null;

// Fetch all praktikum for dropdown
$praktikum_list = [];
$sql_praktikum_all = "SELECT id, nama_praktikum FROM mata_praktikum";
$result_praktikum_all = mysqli_query($link, $sql_praktikum_all);
if ($result_praktikum_all) {
    while ($row = mysqli_fetch_assoc($result_praktikum_all)) {
        $praktikum_list[] = $row;
    }
    mysqli_free_result($result_praktikum_all);
}

if (($action == 'add' || $action == 'edit') && !$mata_praktikum_id) {
    header("location: kelola_modul.php");
    exit;
}

$nama_mata_praktikum = "Pilih Mata Praktikum";
if ($mata_praktikum_id) {
    $sql_praktikum = "SELECT nama_praktikum FROM mata_praktikum WHERE id = ?";
    if ($stmt_praktikum = mysqli_prepare($link, $sql_praktikum)) {
        mysqli_stmt_bind_param($stmt_praktikum, "i", $param_praktikum_id);
        $param_praktikum_id = $mata_praktikum_id;
        if (mysqli_stmt_execute($stmt_praktikum)) {
            mysqli_stmt_bind_result($stmt_praktikum, $fetched_nama_praktikum);
            mysqli_stmt_fetch($stmt_praktikum);
            $nama_mata_praktikum = htmlspecialchars($fetched_nama_praktikum);
        }
        mysqli_stmt_close($stmt_praktikum);
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $mata_praktikum_id_post = isset($_POST['mata_praktikum_id']) ? (int)$_POST['mata_praktikum_id'] : $mata_praktikum_id;

    if(empty(trim($_POST["judul_modul"]))){
        $judul_modul_err = "Mohon masukkan judul modul.";
    } else {
        $judul_modul = trim($_POST["judul_modul"]);
    }

    $deskripsi_modul = trim($_POST["deskripsi_modul"]);

    $target_dir = "../public/materi_modul/";
    $uploadOk = 1;
    $file_name_for_db = null;
    $file_type_for_db = null;
    $file_size_for_db = null;

    if(isset($_FILES["file_materi"]) && $_FILES["file_materi"]["error"] == 0){
        $original_file_name = basename($_FILES["file_materi"]["name"]);
        $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
        $allowed_types = array("pdf", "doc", "docx");

        $file_name_for_db = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $file_name_for_db;

        if(!in_array($file_extension, $allowed_types)){
            $file_materi_err = "Maaf, hanya file PDF, DOC, dan DOCX yang diizinkan.";
            $uploadOk = 0;
        }

        if ($_FILES["file_materi"]["size"] > 5 * 1024 * 1024) {
            $file_materi_err = "Maaf, ukuran file terlalu besar (maks 5MB).";
            $uploadOk = 0;
        }

        if($uploadOk == 0){
        } else {
            if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
                $file_type_for_db = $_FILES["file_materi"]["type"];
                $file_size_for_db = $_FILES["file_materi"]["size"];
            } else {
                $file_materi_err = "Maaf, ada error saat mengunggah file Anda.";
            }
        }
    } elseif ($action == 'edit' && $id_edit) {
        $sql_old_file = "SELECT nama_file_materi, tipe_file_materi, ukuran_file_materi FROM modul_praktikum WHERE id = ?";
        if($stmt_old_file = mysqli_prepare($link, $sql_old_file)){
            mysqli_stmt_bind_param($stmt_old_file, "i", $param_id_edit);
            $param_id_edit = $id_edit;
            if(mysqli_stmt_execute($stmt_old_file)){
                mysqli_stmt_bind_result($stmt_old_file, $file_name_for_db, $file_type_for_db, $file_size_for_db);
                mysqli_stmt_fetch($stmt_old_file);
            }
            mysqli_stmt_close($stmt_old_file);
        }
    }

    if(empty($judul_modul_err) && empty($file_materi_err)){
        if($action == 'add'){
            $sql = "INSERT INTO modul_praktikum (mata_praktikum_id, judul_modul, deskripsi_modul, nama_file_materi, tipe_file_materi, ukuran_file_materi) VALUES (?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "issssi", $mata_praktikum_id_post, $param_judul, $param_deskripsi, $param_file_name, $param_file_type, $param_file_size);
                $param_judul = $judul_modul;
                $param_deskripsi = $deskripsi_modul;
                $param_file_name = $file_name_for_db;
                $param_file_type = $file_type_for_db;
                $param_file_size = $file_size_for_db;

                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_modul.php?praktikum_id=" . $mata_praktikum_id_post);
                    exit();
                } else{
                    echo "Terjadi kesalahan saat menambahkan modul. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit' && $id_edit) {
            $sql = "UPDATE modul_praktikum SET judul_modul = ?, deskripsi_modul = ?, nama_file_materi = ?, tipe_file_materi = ?, ukuran_file_materi = ? WHERE id = ? AND mata_praktikum_id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssisii", $param_judul, $param_deskripsi, $param_file_name, $param_file_type, $param_file_size, $param_id, $param_mata_praktikum_id);
                $param_judul = $judul_modul;
                $param_deskripsi = $deskripsi_modul;
                $param_file_name = $file_name_for_db;
                $param_file_type = $file_type_for_db;
                $param_file_size = $file_size_for_db;
                $param_id = $id_edit;
                $param_mata_praktikum_id = $mata_praktikum_id_post;

                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_modul.php?praktikum_id=" . $mata_praktikum_id_post);
                    exit();
                } else{
                    echo "Terjadi kesalahan saat mengubah modul. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if($action == 'edit' && $id_edit && $mata_praktikum_id){
    $sql = "SELECT judul_modul, deskripsi_modul, nama_file_materi FROM modul_praktikum WHERE id = ? AND mata_praktikum_id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $param_id, $param_praktikum_id);
        $param_id = $id_edit;
        $param_praktikum_id = $mata_praktikum_id;
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $judul_modul, $deskripsi_modul, $nama_file_materi_old);
                mysqli_stmt_fetch($stmt);
            } else {
                echo "Data modul tidak ditemukan.";
                $action = 'list';
            }
        } else {
            echo "Ada yang salah. Mohon coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
}

if($action == 'delete' && isset($_GET['id']) && isset($_GET['praktikum_id'])){
    $id_delete = $_GET['id'];
    $mata_praktikum_id_delete = $_GET['praktikum_id'];

    $sql_get_file = "SELECT nama_file_materi FROM modul_praktikum WHERE id = ?";
    if($stmt_get_file = mysqli_prepare($link, $sql_get_file)){
        mysqli_stmt_bind_param($stmt_get_file, "i", $param_id_delete);
        $param_id_delete = $id_delete;
        if(mysqli_stmt_execute($stmt_get_file)){
            mysqli_stmt_bind_result($stmt_get_file, $file_to_delete);
            mysqli_stmt_fetch($stmt_get_file);
            if($file_to_delete && file_exists("../public/materi_modul/" . $file_to_delete)){
                unlink("../public/materi_modul/" . $file_to_delete);
            }
        }
        mysqli_stmt_close($stmt_get_file);
    }

    $sql = "DELETE FROM modul_praktikum WHERE id = ? AND mata_praktikum_id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $param_id, $param_praktikum_id);
        $param_id = $id_delete;
        $param_praktikum_id = $mata_praktikum_id_delete;
        if(mysqli_stmt_execute($stmt)){
            header("location: kelola_modul.php?praktikum_id=" . $mata_praktikum_id_delete);
            exit();
        } else{
            echo "Terjadi kesalahan saat menghapus modul. Mohon coba lagi.";
        }
        mysqli_stmt_close($stmt);
    }
}

$modul_list = [];
if ($mata_praktikum_id) {
    $sql_select_modul = "SELECT id, judul_modul, deskripsi_modul, nama_file_materi FROM modul_praktikum WHERE mata_praktikum_id = ? ORDER BY judul_modul ASC";
    if($stmt_select_modul = mysqli_prepare($link, $sql_select_modul)){
        mysqli_stmt_bind_param($stmt_select_modul, "i", $param_praktikum_id);
        $param_praktikum_id = $mata_praktikum_id;
        if(mysqli_stmt_execute($stmt_select_modul)){
            $result_modul = mysqli_stmt_get_result($stmt_select_modul);
            if(mysqli_num_rows($result_modul) > 0){
                while($row = mysqli_fetch_array($result_modul)){
                    $modul_list[] = $row;
                }
                mysqli_free_result($result_modul);
            }
        } else{
            echo "ERROR: Tidak dapat mengambil data modul. " . mysqli_error($link);
        }
        mysqli_stmt_close($stmt_select_modul);
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kelola Modul</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
      animation: fadeIn 0.6s ease-out forwards;
    }
  </style>
</head>
<body class="flex h-screen bg-gray-100 overflow-hidden">

  <aside class="w-64 bg-white shadow-lg flex-shrink-0">
    <div class="p-6">
      <h2 class="text-2xl font-bold text-blue-600 mb-6">E - Learning</h2>
      <nav class="space-y-4">
        <a href="dashboard_asisten.php"
           class="flex items-center px-4 py-2 text-gray-700 hover:text-blue-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l9-9 9 9M4 10v10h16V10"/>
          </svg>
          Dashboard
        </a>
        <a href="kelola_mata_praktikum.php"
           class="flex items-center px-4 py-2 text-gray-700 hover:text-blue-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v16m8-8H4"/>
          </svg>
          Kelola Praktikum
        </a>
        <a href="kelola_modul.php"
           class="flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-lg transition hover:bg-blue-200">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 5h18M3 12h18M3 19h18"/>
          </svg>
          Kelola Modul
        </a>
        <a href="laporan_masuk.php"
           class="flex items-center px-4 py-2 text-gray-700 hover:text-purple-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 13l4 4L19 7"/>
          </svg>
          Laporan Masuk
        </a>
        <a href="kelola_akun.php"
           class="flex items-center px-4 py-2 text-gray-700 hover:text-red-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20 21v-2a4 4 0 00-3-3.87M4 15v2a4 4 0 003 3.87M12 11a4 4 0 100-8 4 4 0 000 8z"/>
          </svg>
          Kelola Akun
        </a>
        <a href="../public/logout.php"
           class="flex items-center px-4 py-2 text-red-600 hover:text-white hover:bg-red-600 border border-red-600 rounded-lg transition">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5"/>
          </svg>
          Logout
        </a>
      </nav>
    </div>
  </aside>

  <main class="flex-1 overflow-y-auto p-8">
    <header class="mb-8">
      <h1 class="text-3xl font-extrabold text-gray-800 animate-fade-in">
        Kelola Modul
      </h1>
      <div class="mt-4">
        <a href="kelola_mata_praktikum.php" class="inline-flex items-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Kembali ke Kelola Praktikum
        </a>
      </div>
    </header>

    <!-- Praktikum Selection Dropdown -->
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 animate-fade-in">
      <h3 class="text-2xl font-bold mb-6">Pilih Praktikum</h3>
      <form action="kelola_modul.php" method="get" class="flex items-end gap-4">
        <div class="flex-1">
          <label for="praktikum_id" class="block text-gray-700 text-lg font-bold mb-3">Praktikum:</label>
          <select name="praktikum_id" id="praktikum_id" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="">-- Pilih Praktikum --</option>
            <?php foreach($praktikum_list as $praktikum): ?>
              <option value="<?php echo $praktikum['id']; ?>" <?php echo ($mata_praktikum_id == $praktikum['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:-translate-y-1">
          Pilih
        </button>
      </form>
    </div>

    <?php if($mata_praktikum_id): ?>
    <section class="grid grid-cols-1 gap-8">
      <div class="bg-white rounded-2xl shadow-md p-6 animate-fade-in">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-2xl font-bold"><?php echo ($action == 'edit' ? 'Edit' : 'Tambah') . ' Modul'; ?></h3>
          <span class="bg-blue-100 text-blue-800 font-bold px-3 py-1 rounded-full">
            Praktikum: <?php echo $nama_mata_praktikum; ?>
          </span>
        </div>
        <form action="kelola_modul.php?action=<?php echo ($action == 'edit' && $id_edit ? 'edit&id=' . $id_edit : 'add'); ?>&praktikum_id=<?php echo $mata_praktikum_id; ?>" method="post" enctype="multipart/form-data">
          <input type="hidden" name="mata_praktikum_id" value="<?php echo $mata_praktikum_id; ?>">
          <div class="mb-6">
            <label for="judul_modul" class="block text-gray-700 text-lg font-bold mb-3">Judul Modul/Pertemuan:</label>
            <input type="text" name="judul_modul" id="judul_modul" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($judul_modul_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($judul_modul); ?>">
            <span class="text-red-500 text-sm"><?php echo $judul_modul_err; ?></span>
          </div>
          
          <div class="mb-6">
            <label for="deskripsi_modul" class="block text-gray-700 text-lg font-bold mb-3">Deskripsi Modul:</label>
            <textarea name="deskripsi_modul" id="deskripsi_modul" rows="4" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi_modul); ?></textarea>
            <span class="text-red-500 text-sm"><?php echo $deskripsi_modul_err; ?></span>
          </div>
          
          <div class="mb-8">
            <label for="file_materi" class="block text-gray-700 text-lg font-bold mb-3">File Materi (PDF/DOCX):</label>
            <input type="file" name="file_materi" id="file_materi" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($file_materi_err)) ? 'border-red-500' : ''; ?>">
            <span class="text-red-500 text-sm"><?php echo $file_materi_err; ?></span>
            <?php if($action == 'edit' && !empty($nama_file_materi_old)): ?>
              <p class="text-gray-600 mt-3">File materi saat ini: <a href="../public/materi_modul/<?php echo htmlspecialchars($nama_file_materi_old); ?>" target="_blank" class="text-blue-500 hover:underline font-medium">Lihat File</a> (Kosongkan untuk tidak mengubah)</p>
            <?php endif; ?>
          </div>
          
          <div class="flex items-center gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:-translate-y-1">
              <?php echo ($action == 'edit' ? 'Update Modul' : 'Tambah Modul'); ?>
            </button>
            <?php if($action == 'edit'): ?>
              <a href="kelola_modul.php?praktikum_id=<?php echo $mata_praktikum_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition">
                Batal Edit
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="bg-white rounded-2xl shadow-md p-6 animate-fade-in">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-2xl font-bold">Daftar Modul</h3>
          <span class="bg-blue-100 text-blue-800 text-sm font-bold px-3 py-1 rounded-full">
            Total: <?php echo count($modul_list); ?>
          </span>
        </div>
        <?php if(!empty($modul_list)): ?>
        <div class="overflow-x-auto rounded-lg">
          <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
              <tr>
                <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">ID</th>
                <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Judul Modul</th>
                <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Deskripsi</th>
                <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">File Materi</th>
                <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($modul_list as $modul): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="py-4 px-6"><?php echo htmlspecialchars($modul['id']); ?></td>
                <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($modul['judul_modul']); ?></td>
                <td class="py-4 px-6"><?php echo htmlspecialchars($modul['deskripsi_modul']); ?></td>
                <td class="py-4 px-6">
                  <?php if(!empty($modul['nama_file_materi'])): ?>
                    <a href="../public/materi_modul/<?php echo htmlspecialchars($modul['nama_file_materi']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                      </svg>
                      Unduh
                    </a>
                  <?php else: ?>
                    <span class="text-gray-500">Tidak ada file</span>
                  <?php endif; ?>
                </td>
                <td class="py-4 px-6 flex gap-2">
                  <a href="kelola_modul.php?action=edit&id=<?php echo $modul['id']; ?>&praktikum_id=<?php echo $mata_praktikum_id; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                  </a>
                  <a href="kelola_modul.php?action=delete&id=<?php echo $modul['id']; ?>&praktikum_id=<?php echo $mata_praktikum_id; ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition flex items-center" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini?');">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Hapus
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
            <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h4 class="text-xl font-bold text-gray-700 mb-2">Belum ada modul untuk praktikum ini</h4>
            <p class="text-gray-600">Tambahkan modul pertama menggunakan form di atas</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-md p-8 text-center animate-fade-in">
        <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="text-2xl font-bold text-gray-700 mb-2">Pilih Praktikum</h3>
        <p class="text-gray-600 mb-6">Silakan pilih praktikum dari dropdown di atas untuk mulai mengelola modul</p>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>