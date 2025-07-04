<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../public/login.php");
    exit;
}
if ($_SESSION["role"] !== "asisten") {
    if ($_SESSION["role"] == "mahasiswa") {
        header("location: dashboard_mahasiswa.php");
    } else {
        header("location: ../public/logout.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Asisten</title>
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

  <!-- Sidebar -->
  <aside class="w-64 bg-white shadow-lg flex-shrink-0">
    <div class="p-6">
      <h2 class="text-2xl font-bold text-blue-600 mb-6">E - Learning</h2>
      <nav class="space-y-4">
        <a href="dashboard_asisten.php"
           class="flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-lg transition hover:bg-blue-200">
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

  <!-- Main content -->
  <main class="flex-1 overflow-y-auto p-8">
    <header class="mb-8">
      <h1 class="text-3xl font-extrabold text-gray-800 animate-fade-in">
        Selamat Datang, <?php echo htmlspecialchars($_SESSION["username"]); ?>!
      </h1>
    </header>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="bg-white rounded-2xl shadow-md p-6 transform opacity-0 animate-fade-in" style="animation-delay:0.2s;">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-blue-100 rounded-full animate-pulse">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4"/>
            </svg>
          </div>
          <h3 class="ml-3 text-xl font-semibold text-gray-700">Kelola Praktikum</h3>
        </div>
        <p class="text-gray-600 mb-6">Tambahkan atau ubah mata praktikum yang tersedia.</p>
        <a href="kelola_mata_praktikum.php"
           class="inline-block px-4 py-2 bg-blue-600 text-white font-medium rounded-lg transition hover:bg-blue-700 hover:-translate-y-1">
          Kelola Praktikum
        </a>
      </div>

      <div class="bg-white rounded-2xl shadow-md p-6 transform opacity-0 animate-fade-in" style="animation-delay:0.4s;">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-green-100 rounded-full animate-pulse">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5h18M3 12h18M3 19h18"/>
            </svg>
          </div>
          <h3 class="ml-3 text-xl font-semibold text-gray-700">Kelola Modul</h3>
        </div>
        <p class="text-gray-600 mb-6">Atur modul dan materi untuk setiap mata praktikum.</p>
        <a href="kelola_modul.php"
           class="inline-block px-4 py-2 bg-green-600 text-white font-medium rounded-lg transition hover:bg-green-700 hover:-translate-y-1">
          Kelola Modul
        </a>
      </div>

      <div class="bg-white rounded-2xl shadow-md p-6 transform opacity-0 animate-fade-in" style="animation-delay:0.6s;">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-purple-100 rounded-full animate-pulse">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h3 class="ml-3 text-xl font-semibold text-gray-700">Laporan Masuk</h3>
        </div>
        <p class="text-gray-600 mb-6">Periksa dan nilai laporan yang dikumpulkan mahasiswa.</p>
        <a href="laporan_masuk.php"
           class="inline-block px-4 py-2 bg-purple-600 text-white font-medium rounded-lg transition hover:bg-purple-700 hover:-translate-y-1">
          Lihat Laporan
        </a>
      </div>

      <div class="bg-white rounded-2xl shadow-md p-6 transform opacity-0 animate-fade-in" style="animation-delay:0.8s;">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-red-100 rounded-full animate-pulse">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 21v-2a4 4 0 00-3-3.87M4 15v2a4 4 0 003 3.87M12 11a4 4 0 100-8 4 4 0 000 8z"/>
            </svg>
          </div>
          <h3 class="ml-3 text-xl font-semibold text-gray-700">Kelola Akun</h3>
        </div>
        <p class="text-gray-600 mb-6">Tambahkan, ubah, atau hapus akun pengguna sistem.</p>
        <a href="kelola_akun.php"
           class="inline-block px-4 py-2 bg-red-600 text-white font-medium rounded-lg transition hover:bg-red-700 hover:-translate-y-1">
          Kelola Akun
        </a>
      </div>
    </section>
  </main>

</body>
</html>
