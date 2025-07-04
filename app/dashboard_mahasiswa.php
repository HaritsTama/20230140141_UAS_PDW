<?php
session_start();

// Authentication & role guard (unchanged)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../public/login.php");
    exit;
}
if ($_SESSION["role"] !== "mahasiswa") {
    if ($_SESSION["role"] == "asisten") {
        header("location: dashboard_asisten.php");
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
  <title>Dashboard Mahasiswa</title>
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
        <a href="dashboard_mahasiswa.php"
           class="flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-lg transition hover:bg-blue-200">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l9-9 9 9M4 10v10h16V10"/>
          </svg>
          Dashboard
        </a>
        <a href="praktikum_saya.php"
           class="flex items-center px-4 py-2 text-gray-700 hover:text-blue-600 transition hover:bg-gray-50 rounded-lg">
          <!-- clipboard-list icon -->
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-widanimate-pulseth="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6a2 2 0 012 2v0a2 2 0 01-2 2H9a2 2 0 01-2-2v0a2 2 0 012-2zM9 12h6M9 16h6"/>
          </svg>
          Praktikum Saya
        </a>
        <a href="../public/katalog_praktikum.php"
           class="flex items-center px-4 py-2 text-gray-700 hover:text-green-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 5h18M3 12h18M3 19h18"/>
          </svg>
          Katalog Praktikum
        </a>

        <!-- Smaller logout button -->
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
      <!-- Card 1 -->
      <div class="bg-white rounded-2xl shadow-md p-6 transform opacity-0 animate-fade-in" style="animation-delay:0.2s;">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-blue-100 rounded-full animate-pulse">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6a2 2 0 012 2v0a2 2 0 01-2 2H9a2 2 0 01-2-2v0a2 2 0 012-2zM9 12h6M9 16h6"/>
            </svg>
          </div>
          <h3 class="ml-3 text-xl font-semibold text-gray-700">Mata Praktikum Saya</h3>
        </div>
        <p class="text-gray-600 mb-6">Lihat daftar praktikum yang sedang Anda ikuti.</p>
        <a href="praktikum_saya.php"
           class="inline-block px-4 py-2 bg-blue-600 text-white font-medium rounded-lg transition hover:bg-blue-700 hover:-translate-y-1">
          Lihat Praktikum
        </a>
      </div>

      <!-- Card 2 -->
      <div class="bg-white rounded-2xl shadow-md p-6 transform opacity-0 animate-fade-in" style="animation-delay:0.4s;">
        <div class="flex items-center mb-4">
          <div class="p-3 bg-green-100 rounded-full animate-pulse">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5h18M3 12h18M3 19h18"/>
            </svg>
          </div>
          <h3 class="ml-3 text-xl font-semibold text-gray-700">Cari Mata Praktikum</h3>
        </div>
        <p class="text-gray-600 mb-6">Telusuri dan daftar mata praktikum baru.</p>
        <a href="../public/katalog_praktikum.php"
           class="inline-block px-4 py-2 bg-green-600 text-white font-medium rounded-lg transition hover:bg-green-700 hover:-translate-y-1">
          Cari Praktikum
        </a>
      </div>
    </section>
  </main>

</body>
</html>
