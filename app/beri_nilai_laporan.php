<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "asisten"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$laporan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$laporan_data = null;
$nilai = $feedback = "";
$nilai_err = "";
$flash_message = "";

if ($laporan_id > 0) {
    $sql_select_laporan = "
        SELECT
            lp.id AS laporan_id,
            u.nama AS nama_mahasiswa,
            mpk.nama_praktikum,
            md.judul_modul,
            lp.nama_file_laporan,
            lp.tanggal_upload,
            lp.nilai,
            lp.feedback,
            lp.status
        FROM
            laporan_praktikum lp
        JOIN
            pendaftaran_praktikum pp ON lp.pendaftaran_id = pp.id
        JOIN
            users u ON pp.user_id = u.id
        JOIN
            modul_praktikum md ON lp.modul_id = md.id
        JOIN
            mata_praktikum mpk ON md.mata_praktikum_id = mpk.id
        WHERE
            lp.id = ?
    ";
    if($stmt_select_laporan = mysqli_prepare($link, $sql_select_laporan)){
        mysqli_stmt_bind_param($stmt_select_laporan, "i", $laporan_id);
        if(mysqli_stmt_execute($stmt_select_laporan)){
            $result_laporan = mysqli_stmt_get_result($stmt_select_laporan);
            $laporan_data = mysqli_fetch_assoc($result_laporan);
            if ($laporan_data) {
                $nilai = $laporan_data['nilai'];
                $feedback = $laporan_data['feedback'];
            }
            mysqli_free_result($result_laporan);
        } else {
            $flash_message = "ERROR: Tidak dapat mengambil detail laporan.";
        }
        mysqli_stmt_close($stmt_select_laporan);
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_nilai'])){
    $nilai = trim($_POST["nilai"]);
    $feedback = trim($_POST["feedback"]);

    if(empty($nilai)){
        $nilai_err = "Mohon masukkan nilai.";
    } elseif (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $nilai_err = "Nilai harus angka antara 0-100.";
    }

    if (empty($nilai_err) && $laporan_id > 0) {
        $sql_update_nilai = "UPDATE laporan_praktikum SET nilai = ?, feedback = ?, status = 'dinilai' WHERE id = ?";
        if($stmt_update_nilai = mysqli_prepare($link, $sql_update_nilai)){
            mysqli_stmt_bind_param($stmt_update_nilai, "isi", $param_nilai, $param_feedback, $param_laporan_id);
            $param_nilai = $nilai;
            $param_feedback = $feedback;
            $param_laporan_id = $laporan_id;

            if(mysqli_stmt_execute($stmt_update_nilai)){
                $_SESSION['flash_message_laporan_masuk'] = "Nilai dan feedback berhasil disimpan!";
                header("location: laporan_masuk.php");
                exit;
            } else {
                $flash_message = "Error saat menyimpan nilai: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt_update_nilai);
        }
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Beri Nilai Laporan</title>
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
           class="flex items-center px-4 py-2 text-gray-700 hover:text-green-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 5h18M3 12h18M3 19h18"/>
          </svg>
          Kelola Modul
        </a>
        <a href="laporan_masuk.php"
           class="flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-lg transition hover:bg-blue-200">
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
        Beri Nilai Laporan
      </h1>
      <div class="mt-4">
        <a href="laporan_masuk.php" class="inline-flex items-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Kembali ke Laporan Masuk
        </a>
      </div>
    </header>

    <?php if (!empty($flash_message)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
        <?php echo htmlspecialchars($flash_message); ?>
      </div>
    <?php endif; ?>

    <?php if ($laporan_data): ?>
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 animate-fade-in">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-blue-50 p-4 rounded-lg">
          <h3 class="text-lg font-bold text-blue-800 mb-2">Detail Mahasiswa</h3>
          <p class="mb-1"><strong>Nama:</strong> <?php echo htmlspecialchars($laporan_data['nama_mahasiswa']); ?></p>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg">
          <h3 class="text-lg font-bold text-green-800 mb-2">Detail Praktikum</h3>
          <p class="mb-1"><strong>Praktikum:</strong> <?php echo htmlspecialchars($laporan_data['nama_praktikum']); ?></p>
          <p><strong>Modul:</strong> <?php echo htmlspecialchars($laporan_data['judul_modul']); ?></p>
        </div>
        
        <div class="md:col-span-2 bg-purple-50 p-4 rounded-lg">
          <h3 class="text-lg font-bold text-purple-800 mb-2">Detail Laporan</h3>
          <p class="mb-1"><strong>Tanggal Upload:</strong> <?php echo htmlspecialchars($laporan_data['tanggal_upload']); ?></p>
          <p>
            <strong>File Laporan:</strong> 
            <a href="../public/laporan_praktikum/<?php echo htmlspecialchars($laporan_data['nama_file_laporan']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Unduh Laporan
            </a>
          </p>
        </div>
      </div>

      <form action="beri_nilai_laporan.php?id=<?php echo $laporan_id; ?>" method="post">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="nilai" class="block text-gray-700 text-lg font-bold mb-3">Nilai (0-100):</label>
            <input type="number" name="nilai" id="nilai" min="0" max="100" 
                   class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nilai_err)) ? 'border-red-500' : ''; ?>" 
                   value="<?php echo htmlspecialchars($nilai); ?>">
            <span class="text-red-500 text-sm"><?php echo $nilai_err; ?></span>
          </div>
          
          <div>
            <label for="feedback" class="block text-gray-700 text-lg font-bold mb-3">Feedback:</label>
            <textarea name="feedback" id="feedback" rows="4" 
                      class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($feedback); ?></textarea>
          </div>
        </div>
        
        <div class="flex gap-4 mt-8">
          <button type="submit" name="submit_nilai" 
                  class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:-translate-y-1 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan Nilai
          </button>
          
          <a href="laporan_masuk.php" 
             class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-lg focus:outline-none focus:shadow-outline transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Batal
          </a>
        </div>
      </form>
    </div>
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-md p-8 text-center animate-fade-in">
        <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <h3 class="text-2xl font-bold text-gray-700 mb-2">Laporan Tidak Ditemukan</h3>
        <p class="text-gray-600 mb-6">Laporan yang Anda cari tidak ditemukan atau tidak valid</p>
        <a href="laporan_masuk.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Kembali ke Laporan Masuk
        </a>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>