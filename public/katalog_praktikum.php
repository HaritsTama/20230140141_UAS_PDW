<?php
session_start();
require_once '../includes/config.php';

$flash_message = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$mata_praktikum_list = [];
$sql_select = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
if ($result = mysqli_query($link, $sql_select)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $mata_praktikum_list[] = $row;
        }
        mysqli_free_result($result);
    }
} else {
    echo "ERROR: Tidak dapat mengambil data mata praktikum. " . mysqli_error($link);
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Katalog Praktikum</title>
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
        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
          <?php if($_SESSION["role"] === "mahasiswa"): ?>
            <a href="../app/dashboard_mahasiswa.php" class="flex items-center px-4 py-2 text-gray-700 hover:text-blue-600 transition hover:bg-gray-50 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M3 12l9-9 9 9M4 10v10h16V10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
              Dashboard
            </a>
            <a href="../app/praktikum_saya.php" class="flex items-center px-4 py-2 text-gray-700 hover:text-blue-600 transition hover:bg-gray-50 rounded-lg">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6a2 2 0 012 2v0a2 2 0 01-2 2H9a2 2 0 01-2-2v0a2 2 0 012-2zM9 12h6M9 16h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
              Praktikum Saya
            </a>
            <a href="katalog_praktikum.php" class="flex items-center px-4 py-2 text-gray-700 hover:text-green-600 transition hover:bg-gray-50 rounded-lg">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M3 5h18M3 12h18M3 19h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                Katalog Praktikum
            </a>
          <?php endif; ?>
          <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 hover:text-white hover:bg-red-600 border border-red-600 rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
            Logout
          </a>
        <?php else: ?>
          <a href="login.php" class="block w-full bg-blue-500 text-white text-center py-2 rounded hover:bg-blue-600 mb-2">Login</a>
          <a href="register.php" class="block w-full bg-green-500 text-white text-center py-2 rounded hover:bg-green-600">Register</a>
        <?php endif; ?>
      </nav>
    </div>
  </aside>

  <main class="flex-1 overflow-y-auto p-8">
    <?php if (!empty($flash_message)): ?>
      <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-6" role="alert">
        <?php echo htmlspecialchars($flash_message); ?>
      </div>
    <?php endif; ?>

    <h2 class="text-3xl font-bold mb-6 text-gray-800 animate-fade-in">Katalog Mata Praktikum</h2>

    <?php if (!empty($mata_praktikum_list)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($mata_praktikum_list as $praktikum): ?>
          <div class="bg-white p-6 rounded-2xl shadow-md transform opacity-0 animate-fade-in">
            <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?> (<?php echo htmlspecialchars($praktikum['kode_praktikum']); ?>)</h3>
            <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>

            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === "mahasiswa"): ?>
              <form action="daftar_praktikum.php" method="post">
                <input type="hidden" name="mata_praktikum_id" value="<?php echo $praktikum['id']; ?>">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                  Daftar Praktikum
                </button>
              </form>
            <?php elseif (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === "asisten"): ?>
              <p class="text-sm text-gray-500">Anda login sebagai Asisten.</p>
            <?php else: ?>
              <p class="text-sm text-gray-500">Login sebagai Mahasiswa untuk mendaftar.</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-600">Belum ada mata praktikum yang tersedia. Silakan hubungi admin.</p>
    <?php endif; ?>
  </main>
</body>
</html>
