<?php
require_once '../includes/config.php';

$username = $password = $confirm_password = $nama = $email = $role = "";
$username_err = $password_err = $confirm_password_err = $nama_err = $email_err = $role_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "Username ini sudah terdaftar.";
                } else{
                    $username = trim($_POST["username"]);
                }
            }
            mysqli_stmt_close($stmt);
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
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Email ini sudah terdaftar.";
                } else{
                    $email = trim($_POST["email"]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

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

    if(empty(trim($_POST["role"]))){
        $role_err = "Mohon pilih peran (role).";
    } else {
        $role = trim($_POST["role"]);
    }

    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($nama_err) && empty($email_err) && empty($role_err)){
        $sql = "INSERT INTO users (username, password, role, nama, email) VALUES (?, ?, ?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $param_role, $param_nama, $param_email);
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_role = $role;
            $param_nama = $nama;
            $param_email = $email;
            if(mysqli_stmt_execute($stmt)){
                header("location: login.php");
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SIMPRAK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f0f7ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }
        .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #4f46e5;
        }
        .input-field {
            padding-left: 45px;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }
        .role-card {
            transition: all 0.2s ease;
        }
        .role-card:hover {
            background-color: #f0f7ff;
            border-color: #4f46e5;
        }
        .role-card.selected {
            background-color: #f0f7ff;
            border-color: #4f46e5;
        }
        .btn-primary {
            background: #4f46e5;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="mx-auto bg-indigo-100 text-indigo-700 p-4 rounded-full w-16 h-16 flex items-center justify-center mb-4">
                <i class="fas fa-user-plus text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Buat Akun Baru</h1>
            <p class="text-gray-600 mt-2">Isi formulir di bawah untuk mendaftar</p>
        </div>
        
        <div class="card bg-white p-6 sm:p-8">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!-- Username -->
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">Username</label>
                    <input type="text" name="username" value="<?php echo $username; ?>" 
                        class="w-full py-3 px-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo !empty($username_err) ? 'border-red-500' : 'border-gray-300'; ?>"
                        placeholder="Masukkan username">
                    <?php if(!empty($username_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $username_err; ?></p>
                    <?php endif; ?>
                </div>

                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">Nama Lengkap</label>
                    <input type="text" name="nama" value="<?php echo $nama; ?>" 
                        class="w-full py-3 px-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo !empty($nama_err) ? 'border-red-500' : 'border-gray-300'; ?>"
                        placeholder="Masukkan nama lengkap">
                    <?php if(!empty($nama_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $nama_err; ?></p>
                    <?php endif; ?>
                </div>

                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo $email; ?>" 
                        class="w-full py-3 px-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo !empty($email_err) ? 'border-red-500' : 'border-gray-300'; ?>"
                        placeholder="Masukkan email">
                    <?php if(!empty($email_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $email_err; ?></p>
                    <?php endif; ?>
                </div>

                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" name="password" 
                        class="w-full py-3 px-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo !empty($password_err) ? 'border-red-500' : 'border-gray-300'; ?>"
                        placeholder="Password">
                    <?php if(!empty($password_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $password_err; ?></p>
                    <?php endif; ?>
                </div>

                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" 
                        class="w-full py-3 px-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo !empty($confirm_password_err) ? 'border-red-500' : 'border-gray-300'; ?>"
                        placeholder="Konfirmasi password">
                    <?php if(!empty($confirm_password_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $confirm_password_err; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Role Selection -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Daftar sebagai</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="role-card p-3 rounded-lg border cursor-pointer text-center <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>" onclick="selectRole('mahasiswa')">
                            <div class="mx-auto bg-indigo-100 text-indigo-700 p-2 rounded-full w-10 h-10 flex items-center justify-center mb-2">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <span class="font-medium">Mahasiswa</span>
                            <input type="radio" name="role" value="mahasiswa" class="hidden" <?php echo ($role == 'mahasiswa') ? 'checked' : ''; ?>>
                        </div>
                        <div class="role-card p-3 rounded-lg border cursor-pointer text-center <?php echo ($role == 'asisten') ? 'selected' : ''; ?>" onclick="selectRole('asisten')">
                            <div class="mx-auto bg-indigo-100 text-indigo-700 p-2 rounded-full w-10 h-10 flex items-center justify-center mb-2">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <span class="font-medium">Asisten</span>
                            <input type="radio" name="role" value="asisten" class="hidden" <?php echo ($role == 'asisten') ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    <?php if(!empty($role_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $role_err; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-primary w-full text-white font-bold py-3 px-4 rounded-lg focus:outline-none">
                    Daftar Akun
                </button>
            </form>
            
            <div class="mt-6 text-center text-gray-600 text-sm">
                Sudah punya akun? <a href="login.php" class="text-indigo-600 font-medium">Masuk disini</a>
            </div>
        </div>
    </div>
    
    <script>
        function selectRole(role) {
            // Remove selected class from all cards
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Set the radio button value
            document.querySelector(`input[name="role"][value="${role}"]`).checked = true;
        }
    </script>
</body>
</html>