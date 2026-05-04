<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$max_capacity = 50;
$current_count = 0;
$percentage = 0;

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

unset($_SESSION['message']);
unset($_SESSION['message_type']);

$conn = getDBConnection();
$admin_id = $_SESSION['admin_id'];

// ==========================
// HANDLE POST REQUESTS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔴 UPDATE CAPACITY
    if (isset($_POST['update_capacity'])) {
        $new_capacity = (int)$_POST['max_capacity'];

        if ($new_capacity >= 1 && $new_capacity <= 500) {

            $stid = oci_parse($conn, "BEGIN update_capacity_proc(:capacity, :admin_id); END;");
            oci_bind_by_name($stid, ":capacity", $new_capacity);
            oci_bind_by_name($stid, ":admin_id", $admin_id);

            if (oci_execute($stid)) {
                $_SESSION['message'] = "✅ Maximum capacity updated to $new_capacity";
                $_SESSION['message_type'] = "success";
            } else {
                $e = oci_error($stid);
                $_SESSION['message'] = "❌ Error: " . $e['message'];
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "❌ Capacity must be between 1 and 500!";
            $_SESSION['message_type'] = "error";
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // 🔴 RESET COUNT
    if (isset($_POST['reset_count'])) {

        $stid = oci_parse($conn, "BEGIN reset_count_proc(:admin_id); END;");
        oci_bind_by_name($stid, ":admin_id", $admin_id);

        if (oci_execute($stid)) {
            $_SESSION['message'] = "✅ Library count has been reset!";
            $_SESSION['message_type'] = "success";
        } else {
            $e = oci_error($stid);
            $_SESSION['message'] = "❌ Error: " . $e['message'];
            $_SESSION['message_type'] = "error";
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // 🔴 CLEAR LOGS
    if (isset($_POST['clear_logs'])) {

        $stid = oci_parse($conn, "BEGIN clear_logs_proc(:admin_id); END;");
        oci_bind_by_name($stid, ":admin_id", $admin_id);

        if (oci_execute($stid)) {
            $_SESSION['message'] = "✅ All attendance logs cleared!";
            $_SESSION['message_type'] = "success";
        } else {
            $e = oci_error($stid);
            $_SESSION['message'] = "❌ Error: " . $e['message'];
            $_SESSION['message_type'] = "error";
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ==========================
// FETCH DATA (REAL-TIME)
// ==========================

try {
    // 🔴 GET MAX CAPACITY
    $stid1 = oci_parse($conn, "BEGIN get_max_capacity_proc(:capacity); END;");
    oci_bind_by_name($stid1, ":capacity", $max_capacity, 32);
    oci_execute($stid1);

    // 🔴 GET CURRENT COUNT
    $stid2 = oci_parse($conn, "BEGIN get_current_count_proc(:count); END;");
    oci_bind_by_name($stid2, ":count", $current_count, 32);
    oci_execute($stid2);

    // Compute percentage
    $percentage = ($max_capacity > 0) 
        ? min(($current_count / $max_capacity * 100), 100) 
        : 0;

} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QCU LibriCount - Settings</title>
    <link rel="stylesheet" href="ADMIN_SETTINGS.css">
</head>

<body>

<?php if ($message): ?>
<div class="message-popup <?php echo $message_type; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="message error">
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<header class="header">
    <h1>QCU LibriCount</h1>
</header>

<div class="container">

    <div class="card">
        <h2>System Configuration</h2>
        <form method="POST">
            <input type="number" name="max_capacity" 
                   value="<?php echo htmlspecialchars($max_capacity); ?>" 
                   min="1" max="500">
            <button type="submit" name="update_capacity">Save</button>
        </form>
    </div>

    <div class="card">
        <h2>Reset Tools</h2>
        <form method="POST">
            <button type="submit" name="reset_count">Reset Count</button>
            <button type="submit" name="clear_logs">Clear Logs</button>
        </form>
    </div>

    <div class="card">
        <h2>System Status</h2>
        <p>
            Current Count: 
            <?php echo $current_count; ?> /
            <?php echo $max_capacity; ?>
        </p>

        <p>Status:
        <?php
            if ($percentage >= 90) echo "🔴 Full";
            elseif ($percentage >= 70) echo "🟡 Almost Full";
            else echo "🟢 Available";
        ?>
        </p>
    </div>

</div>

</body>
</html>