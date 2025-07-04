<?php
session_start();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["role"] == "mahasiswa"){
        header("location: ../app/dashboard_mahasiswa.php");
    } elseif($_SESSION["role"] == "asisten"){
        header("location: ../app/dashboard_asisten.php");
    }
    exit;
}

require_once '../includes/config.php';

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else{
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Mohon masukkan password.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            if($role == "mahasiswa"){
                                header("location: ../app/dashboard_mahasiswa.php");
                            } elseif($role == "asisten"){
                                header("location: ../app/dashboard_asisten.php");
                            }
                        } else{
                            $login_err = "Username atau password salah.";
                        }
                    }
                } else{
                    $login_err = "Username atau password salah.";
                }
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
    <title>Login - SIMPRAK</title>
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
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
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
        .forgot-link {
            transition: color 0.3s ease;
        }
        .forgot-link:hover {
            color: #4338ca;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="mx-auto bg-indigo-100 text-indigo-700 p-4 rounded-full w-16 h-16 flex items-center justify-center mb-4">
                <i class="fas fa-lock text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Masuk ke Akun Anda</h1>
            <p class="text-gray-600 mt-2">Silakan masuk untuk melanjutkan</p>
        </div>
        
        <div class="card bg-white p-6 sm:p-8">
            <?php if(!empty($login_err)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $login_err; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!-- Username -->
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">Username</label>
                    <input type="text" name="username" value="<?php echo $username; ?>" 
                           class="input-field w-full py-3 px-4 border rounded-lg focus:outline-none <?php echo !empty($username_err) ? 'border-red-500' : 'border-gray-300'; ?>"
                           placeholder="Masukkan username">
                    <?php if(!empty($username_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $username_err; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Password -->
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" name="password" 
                           class="input-field w-full py-3 px-4 border rounded-lg focus:outline-none <?php echo !empty($password_err) ? 'border-red-500' : 'border-gray-300'; ?>"
                           placeholder="Masukkan password">
                    <?php if(!empty($password_err)): ?>
                        <p class="text-red-500 text-sm mt-1"><?php echo $password_err; ?></p>
                    <?php endif; ?>
                </div>
                
                
                <!-- Submit Button -->
                <button type="submit" class="btn-primary w-full text-white font-bold py-3 px-4 rounded-lg focus:outline-none">
                    Masuk Sekarang
                </button>
                
                <div class="mt-6 text-center text-gray-600 text-sm">
                    Belum punya akun? <a href="register.php" class="text-indigo-600 font-medium">Daftar disini</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>