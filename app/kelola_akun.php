<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "asisten"){
    header("location: ../public/login.php");
    exit;
}

require_once '../includes/config.php';

$username = $password = $confirm_password = $nama = $email = $role = "";
$username_err = $password_err = $confirm_password_err = $nama_err = $email_err = $role_err = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id_edit = isset($_GET['id']) ? (int)$_GET['id'] : null;

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else {
        $sql_check = "SELECT id FROM users WHERE username = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }

        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_username, $param_id);
                $param_id = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_username);
            }
            $param_username = trim($_POST["username"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $username_err = "Username ini sudah terdaftar.";
            } else {
                $username = trim($_POST["username"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    if(empty(trim($_POST["nama"]))){
        $nama_err = "Mohon masukkan nama lengkap.";
    } else{
        $nama = trim($_POST["nama"]);
    }

    if(empty(trim($_POST["email"]))){
        $email_err = "Mohon masukkan email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Format email tidak valid.";
    } else {
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_email, $param_id_email);
                $param_id_email = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_email);
            }
            $param_email = trim($_POST["email"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $email_err = "Email ini sudah terdaftar.";
            } else {
                $email = trim($_POST["email"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    if(empty(trim($_POST["role"]))){
        $role_err = "Mohon pilih peran (role).";
    } elseif(!in_array($_POST["role"], ['mahasiswa', 'asisten'])){
        $role_err = "Peran yang dipilih tidak valid.";
    } else {
        $role = trim($_POST["role"]);
    }

    if ($action == 'add' || (!empty(trim($_POST["password"])) && $action == 'edit')) {
        if(empty(trim($_POST["password"]))){
            $password_err = "Mohon masukkan password.";
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password harus minimal 6 karakter.";
        } else{
            $password = trim($_POST["password"]);
        }

        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = "Mohon konfirmasi password.";
        } else{
            if(empty($password_err) && ($password != trim($_POST["confirm_password"]))){
                $confirm_password_err = "Password tidak cocok.";
            }
        }
    }

    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($nama_err) && empty($email_err) && empty($role_err)){
        if($action == 'add'){
            $sql = "INSERT INTO users (username, password, role, nama, email) VALUES (?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $param_role, $param_nama, $param_email);
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_role = $role;
                $param_nama = $nama;
                $param_email = $email;
                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_akun.php");
                    exit();
                } else{
                    echo "Terjadi kesalahan saat menambahkan akun. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit' && $id_edit) {
            $sql = "UPDATE users SET username = ?, role = ?, nama = ?, email = ? WHERE id = ?";
            if(!empty($password)){
                $sql = "UPDATE users SET username = ?, password = ?, role = ?, nama = ?, email = ? WHERE id = ?";
            }

            if($stmt = mysqli_prepare($link, $sql)){
                if (!empty($password)) {
                    mysqli_stmt_bind_param($stmt, "sssssi", $param_username, $param_password, $param_role, $param_nama, $param_email, $param_id);
                    $param_password = password_hash($password, PASSWORD_DEFAULT);
                } else {
                    mysqli_stmt_bind_param($stmt, "ssssi", $param_username, $param_role, $param_nama, $param_email, $param_id);
                }
                $param_username = $username;
                $param_role = $role;
                $param_nama = $nama;
                $param_email = $email;
                $param_id = $id_edit;

                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_akun.php");
                    exit();
                } else{
                    echo "Terjadi kesalahan saat mengubah akun. Mohon coba lagi.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if($action == 'edit' && $id_edit){
    $sql = "SELECT username, nama, email, role FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_edit;
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $username, $nama, $email, $role);
                mysqli_stmt_fetch($stmt);
            } else {
                echo "Akun tidak ditemukan.";
                $action = 'list';
            }
        } else {
            echo "Ada yang salah. Mohon coba lagi nanti.";
        }
        mysqli_stmt_close($stmt);
    }
}

if($action == 'delete' && isset($_GET['id'])){
    $id_delete = (int)$_GET['id'];
    if ($id_delete == $_SESSION['id']) {
        $_SESSION['flash_message_akun'] = "Tidak bisa menghapus akun sendiri!";
        header("location: kelola_akun.php");
        exit;
    }

    $sql = "DELETE FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_delete;
        if(mysqli_stmt_execute($stmt)){
            $_SESSION['flash_message_akun'] = "Akun berhasil dihapus!";
            header("location: kelola_akun.php");
            exit();
        } else{
            $_SESSION['flash_message_akun'] = "Terjadi kesalahan saat menghapus akun. Mohon coba lagi.";
            header("location: kelola_akun.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

$users_list = [];
$sql_select = "SELECT id, username, nama, email, role, created_at FROM users ORDER BY created_at DESC";
if($result = mysqli_query($link, $sql_select)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $users_list[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    echo "ERROR: Tidak dapat mengambil data akun. " . mysqli_error($link);
}

$flash_message_akun = '';
if (isset($_SESSION['flash_message_akun'])) {
    $flash_message_akun = $_SESSION['flash_message_akun'];
    unset($_SESSION['flash_message_akun']);
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kelola Akun</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
      animation: fadeIn 0.6s ease-out forwards;
    }
    .role-badge {
      @apply px-3 py-1 rounded-full text-xs font-bold;
    }
    .role-mahasiswa {
      @apply bg-purple-100 text-purple-800;
    }
    .role-asisten {
      @apply bg-blue-100 text-blue-800;
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
           class="flex items-center px-4 py-2 text-gray-700 hover:text-purple-600 transition hover:bg-gray-50 rounded-lg">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 13l4 4L19 7"/>
          </svg>
          Laporan Masuk
        </a>
        <a href="kelola_akun.php"
           class="flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-lg transition hover:bg-blue-200">
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
        Kelola Akun Pengguna
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

    <?php if (!empty($flash_message_akun)): ?>
      <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
        <?php echo htmlspecialchars($flash_message_akun); ?>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-md p-6 mb-8 animate-fade-in">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold"><?php echo ($action == 'edit' ? 'Edit' : 'Tambah') . ' Akun Pengguna'; ?></h3>
        <?php if($action == 'edit'): ?>
          <a href="kelola_akun.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition">
            Batal Edit
          </a>
        <?php endif; ?>
      </div>
      
      <form action="kelola_akun.php?action=<?php echo ($action == 'edit' && $id_edit ? 'edit&id=' . $id_edit : 'add'); ?>" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="username" class="block text-gray-700 text-lg font-bold mb-3">Username:</label>
          <input type="text" name="username" id="username" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
          <span class="text-red-500 text-sm"><?php echo $username_err; ?></span>
        </div>
        
        <div>
          <label for="nama" class="block text-gray-700 text-lg font-bold mb-3">Nama Lengkap:</label>
          <input type="text" name="nama" id="nama" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($nama); ?>">
          <span class="text-red-500 text-sm"><?php echo $nama_err; ?></span>
        </div>
        
        <div>
          <label for="email" class="block text-gray-700 text-lg font-bold mb-3">Email:</label>
          <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
          <span class="text-red-500 text-sm"><?php echo $email_err; ?></span>
        </div>
        
        <div>
          <label for="role" class="block text-gray-700 text-lg font-bold mb-3">Peran (Role):</label>
          <select name="role" id="role" class="block appearance-none w-full bg-white border border-gray-400 hover:border-gray-500 py-3 px-4 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($role_err)) ? 'border-red-500' : ''; ?>">
            <option value="">Pilih Peran</option>
            <option value="mahasiswa" <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
            <option value="asisten" <?php echo ($role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
          </select>
          <span class="text-red-500 text-sm"><?php echo $role_err; ?></span>
        </div>
        
        <div>
          <label for="password" class="block text-gray-700 text-lg font-bold mb-3">Password <?php echo ($action == 'edit' ? '(kosongkan jika tidak ingin mengubah)' : ''); ?>:</label>
          <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
          <span class="text-red-500 text-sm"><?php echo $password_err; ?></span>
        </div>
        
        <div>
          <label for="confirm_password" class="block text-gray-700 text-lg font-bold mb-3">Konfirmasi Password:</label>
          <input type="password" name="confirm_password" id="confirm_password" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : ''; ?>">
          <span class="text-red-500 text-sm"><?php echo $confirm_password_err; ?></span>
        </div>
        
        <div class="md:col-span-2 flex justify-end">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:-translate-y-1">
            <?php echo ($action == 'edit' ? 'Update Akun' : 'Tambah Akun'); ?>
          </button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-2xl shadow-md p-6 animate-fade-in">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold">Daftar Akun Pengguna</h3>
        <span class="bg-blue-100 text-blue-800 text-sm font-bold px-3 py-1 rounded-full">
          Total: <?php echo count($users_list); ?>
        </span>
      </div>
      
      <?php if(!empty($users_list)): ?>
      <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full bg-white">
          <thead class="bg-gray-100">
            <tr>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">ID</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Username</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Nama</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Email</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Peran</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Terdaftar</th>
              <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($users_list as $user): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($user['id']); ?></td>
              <td class="py-4 px-6"><?php echo htmlspecialchars($user['username']); ?></td>
              <td class="py-4 px-6"><?php echo htmlspecialchars($user['nama']); ?></td>
              <td class="py-4 px-6"><?php echo htmlspecialchars($user['email']); ?></td>
              <td class="py-4 px-6">
                <span class="role-badge <?php echo ($user['role'] == 'asisten') ? 'role-asisten' : 'role-mahasiswa'; ?>">
                  <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                </span>
              </td>
              <td class="py-4 px-6"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
              <td class="py-4 px-6 flex gap-2">
                <a href="kelola_akun.php?action=edit&id=<?php echo $user['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition">
                  Edit
                </a>
                <?php if ($user['id'] != $_SESSION['id']): ?>
                  <a href="kelola_akun.php?action=delete&id=<?php echo $user['id']; ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                    Hapus
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
          <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <h4 class="text-xl font-bold text-gray-700 mb-2">Belum ada akun pengguna yang terdaftar</h4>
          <p class="text-gray-600">Mulai dengan menambahkan akun baru menggunakan form di atas</p>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>