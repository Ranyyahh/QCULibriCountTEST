<?php   
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

// Handle AJAX request if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    function loginAdmin($username, $password) {
        try {
            $conn = getDBConnection();
            
            if (!$conn) {
                throw new Exception("Database connection failed: " . odbc_errormsg());
            }
            
            $sql = "SELECT admin_id, username, password FROM admin WHERE username = '$username'";
            $result = odbc_exec($conn, $sql);
            
            if (!$result) {
                throw new Exception("Query failed: " . odbc_errormsg($conn));
            }
            
            $admin = odbc_fetch_array($result);
            
            if ($admin) {
                if ($password === $admin['PASSWORD']) {
                    $_SESSION['admin_id'] = $admin['ADMIN_ID'];
                    $_SESSION['username'] = $admin['USERNAME'];
                    $_SESSION['admin_logged_in'] = true;
                    
                    session_regenerate_id(true);
                    odbc_close($conn);
                    return ['success' => true];
                } else {
                    odbc_close($conn);
                    return ['success' => false, 'error' => 'password'];
                }
            } else {
                odbc_close($conn);
                return ['success' => false, 'error' => 'username'];
            }
            
        } catch(Exception $e) {
            error_log("Database error in loginAdmin: " . $e->getMessage());
            return ['success' => false, 'error' => 'database', 'message' => $e->getMessage()];
        }
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Please enter both username and password!'
        ]);
        exit();
    }
    
    $login_result = loginAdmin($username, $password);
    
    if ($login_result['success'] === true) {
        echo json_encode([
            'success' => true,
            'message' => '✅ Login successful! Redirecting...',
            'redirect' => 'ADMIN_DASHBOARD.html'
        ]);
    } else {
        $message = "❌ Login failed!";
        if (isset($login_result['message'])) {
            $message = "❌ " . $login_result['message'];
        } elseif ($login_result['error'] === 'username') {
            $message = "❌ Username not found!";
        } elseif ($login_result['error'] === 'password') {
            $message = "❌ Incorrect password!";
        }
        
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="stylesheet" href="AdminLog.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Log In</title>
    </head>
    <body>

<header class="header">
    <div class="logo-wrap">
        <img src="Images/libricount-logo.png" class="logo">
        <h1>QCU LibriCount</h1>
    </div>

    <div class="Nav-bar">
        <nav>
            <ul>
                <li><a href="ADMIN_DASHBOARD.html">Dashboard</a></li>
                <li><a href="ADMIN_LOGS.html">Logs</a></li>
                <li><a href= "ADMIN_SETTINGS.php">Settings</a></li>
            </ul>
        </nav>
    </div>
</header>

    
        <div id="alertContainer" class="alert"></div>

     
        <section class="admin-login-form">
            <img src="Images/admin-logo.png" alt="login-logo" class="admin-logo">

            <form id="loginForm" method="POST">
               
               <div class="input-wrapper">
                    <img src="Images/username-logo.png" alt="username-logo" class="input-icon">
                    <input type="text" id="username" name="username" required placeholder="Username"><br><br>
               </div> 

                 <div class="input-wrapper password-wrapper">
                    <img src="Images/password-logo.png" alt="password-logo" class="input-icon">
                    <input type="password" id="password" name="password" required placeholder="Password"><br><br>
                    <button type="button" id="togglePassword" class="eye-toggle">
                        <i class="far fa-eye"></i>
                    </button>
                </div>

                <div>
                    <button type="submit" class="login-btn" id="submitBtn">
                        Login
                        <div class="spinner" id="submitSpinner"></div>
                    </button>
                </div>

            </form>
        </section>

    </body>
    <script src="AdminLog.js"></script>
</html>
