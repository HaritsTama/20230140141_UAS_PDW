<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "asisten"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$laporan_list = [];
$filter_modul_id = isset($_GET['filter_modul']) ? (int)$_GET['filter_modul'] : 0;
$filter_user_id = isset($_GET['filter_mahasiswa']) ? (int)$_GET['filter_mahasiswa'] : 0;
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

$where_clauses = [];
$param_types = "";
$param_values = [];

if ($filter_modul_id > 0) {
    $where_clauses[] = "lp.modul_id = ?";
    $param_types .= "i";
    $param_values[] = $filter_modul_id;
}
if ($filter_user_id > 0) {
    $where_clauses[] = "pp.user_id = ?";
    $param_types .= "i";
    $param_values[] = $filter_user_id;
}
if (!empty($filter_status) && in_array($filter_status, ['pending', 'dinilai'])) {
    $where_clauses[] = "lp.status = ?";
    $param_types .= "s";
    $param_values[] = $filter_status;
}

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
";

if (!empty($where_clauses)) {
    $sql_select_laporan .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_select_laporan .= " ORDER BY lp.tanggal_upload DESC";

if ($stmt_select_laporan = mysqli_prepare($link, $sql_select_laporan)) {
    if (!empty($param_values)) {
        mysqli_stmt_bind_param($stmt_select_laporan, $param_types, ...$param_values);
    }
    if (mysqli_stmt_execute($stmt_select_laporan)) {
        $result_laporan = mysqli_stmt_get_result($stmt_select_laporan);
        if (mysqli_num_rows($result_laporan) > 0) {
            while ($row = mysqli_fetch_array($result_laporan, MYSQLI_ASSOC)) {
                $laporan_list[] = $row;
            }
        }
        mysqli_free_result($result_laporan);
    } else {
        echo "ERROR: Tidak dapat mengambil data laporan. " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt_select_laporan);
}

$modul_filter_list = [];
$sql_modul_filter = "SELECT id, judul_modul FROM modul_praktikum ORDER BY judul_modul ASC";
if ($result_modul_filter = mysqli_query($link, $sql_modul_filter)) {
    while ($row = mysqli_fetch_assoc($result_modul_filter)) {
        $modul_filter_list[] = $row;
    }
    mysqli_free_result($result_modul_filter);
}

$mahasiswa_filter_list = [];
$sql_mahasiswa_filter = "SELECT id, nama, username FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
if ($result_mahasiswa_filter = mysqli_query($link, $sql_mahasiswa_filter)) {
    while ($row = mysqli_fetch_assoc($result_mahasiswa_filter)) {
        $mahasiswa_filter_list[] = $row;
    }
    mysqli_free_result($result_mahasiswa_filter);
}

$flash_message_laporan_masuk = '';
if (isset($_SESSION['flash_message_laporan_masuk'])) {
    $flash_message_laporan_masuk = $_SESSION['flash_message_laporan_masuk'];
    unset($_SESSION['flash_message_laporan_masuk']);
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Laporan Masuk</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
      animation: fadeIn 0.6s ease-out forwards;
    }
    .status-badge {
      @apply px-3 py-1 rounded-full text-xs font-bold;
    }
    .status-pending {
      @apply bg-yellow-100 text-yellow-800;
    }
    .status-dinilai {
      @apply bg-green-100 text-green-800;
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
        Laporan Masuk
      </h1>
      <div class="mt-4">
        <a href="dashboard_asisten.php" class="inline-flex items-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Kembali ke Dashboard
        </a>
      </div>
    </header>

    <?php if (!empty($flash_message_laporan_masuk)): ?>
      <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
        <?php echo htmlspecialchars($flash_message_laporan_masuk); ?>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 animate-fade-in">
      <h3 class="text-2xl font-bold mb-6">Filter Laporan</h3>
      <form action="laporan_masuk.php" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <label for="filter_modul" class="block text-gray-700 text-lg font-bold mb-3">Modul:</label>
          <select name="filter_modul" id="filter_modul" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="0">Semua Modul</option>
            <?php foreach($modul_filter_list as $modul_item): ?>
              <option value="<?php echo $modul_item['id']; ?>" <?php echo ($filter_modul_id == $modul_item['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($modul_item['judul_modul']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label for="filter_mahasiswa" class="block text-gray-700 text-lg font-bold mb-3">Mahasiswa:</label>
          <select name="filter_mahasiswa" id="filter_mahasiswa" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="0">Semua Mahasiswa</option>
            <?php foreach($mahasiswa_filter_list as $user_item): ?>
              <option value="<?php echo $user_item['id']; ?>" <?php echo ($filter_user_id == $user_item['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($user_item['nama'] . " (" . $user_item['username'] . ")"); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label for="filter_status" class="block text-gray-700 text-lg font-bold mb-3">Status:</label>
          <select name="filter_status" id="filter_status" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="">Semua Status</option>
            <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="dinilai" <?php echo ($filter_status == 'dinilai') ? 'selected' : ''; ?>>Dinilai</option>
          </select>
        </div>
        
        <div class="md:col-span-4 flex justify-end gap-4 mt-2">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:-translate-y-1">
            Terapkan Filter
          </button>
          <a href="laporan_masuk.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition">
            Reset Filter
          </a>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-2xl shadow-md p-6 animate-fade-in">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold">Daftar Laporan</h3>
        <span class="bg-blue-100 text-blue-800 text-sm font-bold px-3 py-1 rounded-full">
          Total: <?php echo count($laporan_list); ?>
        </span>
      </div>
      
      <?php if(!empty($laporan_list)): ?>
      <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full bg-white">
          <thead class="bg-gray-100">
            <tr>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">ID</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Mahasiswa</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Praktikum</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Modul</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Tanggal</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Status</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Nilai</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($laporan_list as $laporan): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-4 px-6 font-medium">#<?php echo htmlspecialchars($laporan['laporan_id']); ?></td>
              <td class="py-4 px-6"><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?></td>
              <td class="py-4 px-6"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></td>
              <td class="py-4 px-6"><?php echo htmlspecialchars($laporan['judul_modul']); ?></td>
              <td class="py-4 px-6"><?php echo htmlspecialchars($laporan['tanggal_upload']); ?></td>
              <td class="py-4 px-6">
                <span class="status-badge <?php echo ($laporan['status'] == 'dinilai') ? 'status-dinilai' : 'status-pending'; ?>">
                  <?php echo htmlspecialchars(ucfirst($laporan['status'])); ?>
                </span>
              </td>
              <td class="py-4 px-6 font-bold">
                <?php echo $laporan['nilai'] !== null ? htmlspecialchars($laporan['nilai']) : '-'; ?>
              </td>
              <td class="py-4 px-6 flex gap-2">
                <a href="../public/laporan_praktikum/<?php echo htmlspecialchars($laporan['nama_file_laporan']); ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                  Unduh
                </a>
                <a href="beri_nilai_laporan.php?id=<?php echo $laporan['laporan_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Nilai
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
          <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <h4 class="text-xl font-bold text-gray-700 mb-2">Belum ada laporan yang masuk</h4>
          <p class="text-gray-600">Tidak ada laporan yang ditemukan dengan filter saat ini</p>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>