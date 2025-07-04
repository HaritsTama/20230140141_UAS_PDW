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

$nama_praktikum = $deskripsi = $kode_praktikum = "";
$nama_praktikum_err = $deskripsi_err = $kode_praktikum_err = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id_edit = isset($_GET['id']) ? $_GET['id'] : null;

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["nama_praktikum"]))) {
        $nama_praktikum_err = "Mohon masukkan nama praktikum.";
    } else {
        $sql_check = "SELECT id FROM mata_praktikum WHERE nama_praktikum = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_nama_praktikum, $param_id);
                $param_id = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_nama_praktikum);
            }
            $param_nama_praktikum = trim($_POST["nama_praktikum"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $nama_praktikum_err = "Nama praktikum ini sudah ada.";
            } else {
                $nama_praktikum = trim($_POST["nama_praktikum"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    $deskripsi = trim($_POST["deskripsi"]);

    if(empty(trim($_POST["kode_praktikum"]))) {
        $kode_praktikum_err = "Mohon masukkan kode praktikum.";
    } else {
        $sql_check = "SELECT id FROM mata_praktikum WHERE kode_praktikum = ?";
        if ($action == 'edit' && $id_edit) {
            $sql_check .= " AND id != ?";
        }
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            if ($action == 'edit' && $id_edit) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_kode_praktikum, $param_id_kode);
                $param_id_kode = $id_edit;
            } else {
                mysqli_stmt_bind_param($stmt_check, "s", $param_kode_praktikum);
            }
            $param_kode_praktikum = trim($_POST["kode_praktikum"]);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $kode_praktikum_err = "Kode praktikum ini sudah ada.";
            } else {
                $kode_praktikum = trim($_POST["kode_praktikum"]);
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    if(empty($nama_praktikum_err) && empty($kode_praktikum_err)){
        if($action == 'add'){
            $sql = "INSERT INTO mata_praktikum (nama_praktikum, deskripsi, kode_praktikum) VALUES (?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sss", $param_nama, $param_deskripsi, $param_kode);
                $param_nama = $nama_praktikum;
                $param_deskripsi = $deskripsi;
                $param_kode = $kode_praktikum;
                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_mata_praktikum.php");
                    exit();
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit' && $id_edit) {
            $sql = "UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ?, kode_praktikum = ? WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssi", $param_nama, $param_deskripsi, $param_kode, $param_id);
                $param_nama = $nama_praktikum;
                $param_deskripsi = $deskripsi;
                $param_kode = $kode_praktikum;
                $param_id = $id_edit;
                if(mysqli_stmt_execute($stmt)){
                    header("location: kelola_mata_praktikum.php");
                    exit();
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if($action == 'edit' && $id_edit){
    $sql = "SELECT nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_edit;
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $nama_praktikum, $deskripsi, $kode_praktikum);
                mysqli_stmt_fetch($stmt);
            } else {
                $action = 'list';
            }
        }
        mysqli_stmt_close($stmt);
    }
}

if($action == 'delete' && isset($_GET['id'])){
    $id_delete = $_GET['id'];
    $sql = "DELETE FROM mata_praktikum WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id_delete;
        if(mysqli_stmt_execute($stmt)){
            header("location: kelola_mata_praktikum.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

$mata_praktikum_list = [];
$sql_select = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
if($result = mysqli_query($link, $sql_select)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result)){
            $mata_praktikum_list[] = $row;
        }
        mysqli_free_result($result);
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Praktikum</title>
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
                   class="flex items-center px-4 py-2 text-blue-700 bg-blue-100 rounded-lg transition hover:bg-blue-200">
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

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-3xl font-bold mb-6 animate-fade-in">Kelola Mata Praktikum</h1>
        
        <?php if($action == 'edit' || $action == 'add'): ?>
        <div class="bg-white rounded-2xl shadow-md p-6 mb-8 animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold"><?php echo ($action == 'edit' ? 'Edit' : 'Tambah') . ' Mata Praktikum'; ?></h2>
                <?php if($action == 'edit'): ?>
                    <a href="kelola_mata_praktikum.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition">
                        Batal Edit
                    </a>
                <?php endif; ?>
            </div>
            <form action="kelola_mata_praktikum.php?action=<?php echo ($action == 'edit' && $id_edit ? 'edit&id=' . $id_edit : 'add'); ?>" method="post">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nama_praktikum" class="block text-gray-700 text-lg font-bold mb-3">Nama Praktikum:</label>
                        <input type="text" name="nama_praktikum" id="nama_praktikum" 
                               class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_praktikum_err)) ? 'border-red-500' : ''; ?>" 
                               value="<?php echo htmlspecialchars($nama_praktikum); ?>">
                        <span class="text-red-500 text-sm"><?php echo $nama_praktikum_err; ?></span>
                    </div>
                    
                    <div>
                        <label for="kode_praktikum" class="block text-gray-700 text-lg font-bold mb-3">Kode Praktikum:</label>
                        <input type="text" name="kode_praktikum" id="kode_praktikum" 
                               class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($kode_praktikum_err)) ? 'border-red-500' : ''; ?>" 
                               value="<?php echo htmlspecialchars($kode_praktikum); ?>">
                        <span class="text-red-500 text-sm"><?php echo $kode_praktikum_err; ?></span>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="deskripsi" class="block text-gray-700 text-lg font-bold mb-3">Deskripsi:</label>
                        <textarea name="deskripsi" id="deskripsi" rows="4" 
                                  class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:-translate-y-1">
                        <?php echo ($action == 'edit' ? 'Update' : 'Tambah'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-md p-6 animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Daftar Mata Praktikum</h2>
                <a href="kelola_mata_praktikum.php?action=add" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition">
                    Tambah Praktikum
                </a>
            </div>
            
            <?php if(!empty($mata_praktikum_list)): ?>
            <div class="overflow-x-auto rounded-lg">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">ID</th>
                            <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Nama</th>
                            <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Kode</th>
                            <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Deskripsi</th>
                            <th class="py-4 px-6 text-left text-gray-700 font-bold uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mata_praktikum_list as $praktikum): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-4 px-6"><?php echo htmlspecialchars($praktikum['id']); ?></td>
                            <td class="py-4 px-6 font-medium"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></td>
                            <td class="py-4 px-6"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></td>
                            <td class="py-4 px-6"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></td>
                            <td class="py-4 px-6 flex gap-2">
                                <a href="kelola_modul.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Modul
                                </a>
                                <a href="kelola_mata_praktikum.php?action=edit&id=<?php echo $praktikum['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </a>
                                <a href="kelola_mata_praktikum.php?action=delete&id=<?php echo $praktikum['id']; ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-sm transition flex items-center" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini?');">
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
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
                    <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h4 class="text-xl font-bold text-gray-700 mb-2">Belum ada mata praktikum</h4>
                    <p class="text-gray-600 mb-4">Tambahkan mata praktikum baru untuk memulai</p>
                    <a href="kelola_mata_praktikum.php?action=add" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Praktikum
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>