<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "mahasiswa") {
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$user_id = $_SESSION["id"];
$praktikum_diikuti = [];

$sql_select_praktikum = "
    SELECT
        mp.id,
        mp.nama_praktikum,
        mp.deskripsi,
        mp.kode_praktikum,
        pp.status_pendaftaran
    FROM
        pendaftaran_praktikum pp
    JOIN
        mata_praktikum mp ON pp.mata_praktikum_id = mp.id
    WHERE
        pp.user_id = ?
    ORDER BY
        mp.nama_praktikum ASC
";

if ($stmt_select_praktikum = mysqli_prepare($link, $sql_select_praktikum)) {
    mysqli_stmt_bind_param($stmt_select_praktikum, "i", $user_id);
    if (mysqli_stmt_execute($stmt_select_praktikum)) {
        $result_praktikum = mysqli_stmt_get_result($stmt_select_praktikum);
        if (mysqli_num_rows($result_praktikum) > 0) {
            while ($row = mysqli_fetch_array($result_praktikum)) {
                $praktikum_diikuti[] = $row;
            }
            mysqli_free_result($result_praktikum);
        }
    } else {
        echo "ERROR: Tidak dapat mengambil data praktikum. " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt_select_praktikum);
} else {
    echo "ERROR: Terjadi kesalahan pada persiapan query. " . mysqli_error($link);
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Praktikum Saya</title>
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
        <a href="dashboard_mahasiswa.php" class="flex items-center px-4 py-2 text-gray-700 hover:text-blue-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M3 12l9-9 9 9M4 10v10h16V10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Dashboard
        </a>
        <a href="praktikum_saya.php" class="flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-lg transition hover:bg-blue-200">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6a2 2 0 012 2v0a2 2 0 01-2 2H9a2 2 0 01-2-2v0a2 2 0 012-2zM9 12h6M9 16h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Praktikum Saya
        </a>
        <a href="../public/katalog_praktikum.php" class="flex items-center px-4 py-2 text-gray-700 hover:text-green-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M3 5h18M3 12h18M3 19h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Katalog Praktikum
        </a>
        <a href="../public/logout.php" class="flex items-center px-4 py-2 text-red-600 hover:text-white hover:bg-red-600 border border-red-600 rounded-lg transition">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Logout
        </a>
      </nav>
    </div>
  </aside>

  <main class="flex-1 overflow-y-auto p-8">
    <h2 class="text-3xl font-bold mb-6 text-gray-800 animate-fade-in">Praktikum Saya</h2>

    <?php if (!empty($praktikum_diikuti)): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($praktikum_diikuti as $praktikum): ?>
          <div class="bg-white p-6 rounded-2xl shadow-md transform opacity-0 animate-fade-in">
            <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?> (<?php echo htmlspecialchars($praktikum['kode_praktikum']); ?>)</h3>
            <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
            <p class="text-sm text-gray-600 mb-4">
              Status:
              <span class="font-bold
                <?php
                  if ($praktikum['status_pendaftaran'] === 'approved') echo 'text-green-600';
                  elseif ($praktikum['status_pendaftaran'] === 'pending') echo 'text-yellow-600';
                  else echo 'text-red-600';
                ?>">
                <?php echo htmlspecialchars(ucfirst($praktikum['status_pendaftaran'])); ?>
              </span>
            </p>
            <a href="detail_praktikum.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
              Lihat Detail & Tugas
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-center text-gray-600">Anda belum mendaftar di mata praktikum manapun. Kunjungi <a href="../public/katalog_praktikum.php" class="text-blue-500 hover:underline">Katalog Praktikum</a> untuk mendaftar.</p>
    <?php endif; ?>
  </main>
</body>
</html>
