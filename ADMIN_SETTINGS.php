<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: Admin_login.php');
    exit();
}

date_default_timezone_set('Asia/Manila');

$max_capacity = 50;
$current_count = 0;
$percentage = 0;

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

unset($_SESSION['message']);
unset($_SESSION['message_type']);

$conn = getDBConnection();
$admin_id = $_SESSION['admin_id'];

function setFlashMessage($message, $type) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function redirectToSelf() {
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_capacity'])) {
        $new_capacity = (int)($_POST['max_capacity'] ?? 0);

        if ($new_capacity >= 1 && $new_capacity <= 500) {
            $updated = executeProcedure($conn, 'update_capacity_proc', [$new_capacity, $admin_id]);

            if (!$updated) {
                $updated = executePrepared(
                    $conn,
                    "UPDATE system_settings
                     SET setting_value = ?, admin_id = ?, changed_at = CURRENT_TIMESTAMP
                     WHERE setting_name = 'max_capacity'",
                    [(string)$new_capacity, $admin_id]
                );
            }

            if ($updated) {
                setFlashMessage("Maximum capacity updated to $new_capacity", "success");
            } else {
                setFlashMessage("Error: " . dbError($conn), "error");
            }
        } else {
            setFlashMessage("Capacity must be between 1 and 500!", "error");
        }

        redirectToSelf();
    }

    if (isset($_POST['reset_count'])) {
        $reset = executeProcedure($conn, 'reset_count_proc', [$admin_id]);

        if (!$reset) {
            $reset = odbc_exec(
                $conn,
                "UPDATE attendance_logs
                 SET status = 'exited', time_out = CURRENT_TIMESTAMP
                 WHERE status = 'inside' AND time_out IS NULL"
            );
        }

        if ($reset) {
            setFlashMessage("Library count has been reset!", "success");
        } else {
            setFlashMessage("Error: " . dbError($conn), "error");
        }

        redirectToSelf();
    }

    if (isset($_POST['clear_logs'])) {
        $cleared = executeProcedure($conn, 'clear_logs_proc', [$admin_id]);

        if (!$cleared) {
            $cleared = odbc_exec($conn, "DELETE FROM attendance_logs");
        }

        if ($cleared) {
            setFlashMessage("All attendance logs cleared!", "success");
        } else {
            setFlashMessage("Error: " . dbError($conn), "error");
        }

        redirectToSelf();
    }
}

try {
    $capacityResult = odbc_exec(
        $conn,
        "SELECT setting_value
         FROM system_settings
         WHERE setting_name = 'max_capacity'"
    );

    if ($capacityResult && $capacityRow = odbc_fetch_array($capacityResult)) {
        $max_capacity = (int)$capacityRow['SETTING_VALUE'];
    }

    $countResult = odbc_exec(
        $conn,
        "SELECT COUNT(*) AS current_count
         FROM attendance_logs
         WHERE status = 'inside'
         AND time_out IS NULL"
    );

    if ($countResult && $countRow = odbc_fetch_array($countResult)) {
        $current_count = (int)$countRow['CURRENT_COUNT'];
    }

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
        <div class="message-popup <?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <script>
            setTimeout(() => {
                const message = document.querySelector('.message-popup');
                if (message) {
                    message.style.opacity = '0';
                    message.style.transform = 'translateX(100%)';
                    message.style.transition = 'opacity 0.3s, transform 0.3s';
                    setTimeout(() => message.remove(), 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <header class="header">
        <div class="logo-wrap">
            <img src="Images/libricount-logo.png" class="logo" alt="LibriCount Logo">
            <h1>QCU LibriCount</h1>
        </div>

        <div class="Nav-bar">
            <nav>
                <ul>
                    <li><a href="ADMIN_DASHBOARD.html">Dashboard</a></li>
                    <li><a href="ADMIN_LOGS.html">Logs</a></li>
                    <li><a href="ADMIN_SETTINGS.php" class="active">Settings</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="left-column">
            <div class="card">
                <h2>System Configuration</h2>
                <form method="POST" action="">
                    <label class="label">Max Capacity:</label>
                    <input type="number" id="maxCapInput" name="max_capacity"
                           value="<?php echo htmlspecialchars($max_capacity); ?>"
                           min="1" max="500" class="input-box" disabled>
                    <div class="btn-groupCRUD">
                        <button type="button" id="editBtn" class="btn btn-gray">Edit</button>
                        <button type="submit" id="Savebttn" name="update_capacity" class="btn btn-red" disabled>Save</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Reset Tools</h2>
                <form method="POST" action="" onsubmit="return confirmAction(this)">
                    <div class="btn-group">
                        <button type="submit" name="reset_count" class="btn btn-gray">Reset Count</button>
                        <button type="submit" name="clear_logs" class="btn btn-red">Clear Logs</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>System Status</h2>
                <div class="status-info">
                    <p>Current Count: <b>
                        <span id="currentCount"><?php echo htmlspecialchars($current_count); ?></span> /
                        <span id="maxCapacity"><?php echo htmlspecialchars($max_capacity); ?></span>
                    </b></p>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"
                             style="width: <?php echo htmlspecialchars($percentage); ?>%;
                             background: <?php
                                if ($percentage >= 90) echo 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
                                elseif ($percentage >= 70) echo 'linear-gradient(135deg, #ffc107 0%, #e0a800 100%)';
                                else echo 'linear-gradient(135deg, #28a745 0%, #218838 100%)';
                             ?>;">
                        </div>
                    </div>
                    <p id="datetime"><?php echo "As of " . date("F j, Y \\a\\t g:i:s A"); ?></p>
                    <p>Status: <span id="systemStatus" style="color: <?php
                        if ($percentage >= 90) echo '#dc3545';
                        elseif ($percentage >= 70) echo '#ffc107';
                        else echo '#28a745';
                    ?>;">
                        <?php
                        if ($percentage >= 90) echo 'Full';
                        elseif ($percentage >= 70) echo 'Almost Full';
                        else echo 'Online';
                        ?>
                    </span></p>
                </div>
            </div>
        </div>

        <div class="right-column">
            <div class="card">
                <h2>About</h2>
                <p>
                    Quezon City University <br>
                    Library Capacity Monitoring System v1.0
                </p>
                <p class="italic">
                    Developed by SBIT - 2D <br>
                    Elevator Etiquette Group
                </p>
            </div>
            <form method="POST" action="logout.php" onsubmit="return confirm('Are you sure you want to log out?')" class="logout-button-container">
                <button type="submit" class="btn btn-red logout">Log out</button>
            </form>
        </div>
    </div>

    <script>
    function confirmAction(form) {
        const button = window.event.submitter;

        if (button.name === 'reset_count') {
            return confirm('Are you sure you want to reset the library count? This will log out all current students.');
        }

        if (button.name === 'clear_logs') {
            return confirm('Are you sure you want to clear all attendance logs? This action cannot be undone!');
        }

        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const editBtn = document.getElementById('editBtn');
        const maxCapInput = document.getElementById('maxCapInput');
        const saveBtn = document.getElementById('Savebttn');

        if (editBtn && maxCapInput && saveBtn) {
            maxCapInput.disabled = true;
            saveBtn.disabled = true;

            editBtn.addEventListener('click', function() {
                if (maxCapInput.disabled) {
                    maxCapInput.disabled = false;
                    maxCapInput.focus();
                    maxCapInput.select();

                    editBtn.textContent = 'Cancel';
                    editBtn.classList.remove('btn-gray');
                    editBtn.classList.add('btn-red');

                    saveBtn.disabled = false;
                } else {
                    maxCapInput.disabled = true;
                    maxCapInput.value = document.getElementById('maxCapacity').textContent;

                    editBtn.textContent = 'Edit';
                    editBtn.classList.remove('btn-red');
                    editBtn.classList.add('btn-gray');

                    saveBtn.disabled = true;
                }
            });

            maxCapInput.addEventListener('input', function() {
                const currentValue = document.getElementById('maxCapacity').textContent;
                saveBtn.disabled = maxCapInput.value === currentValue;
            });
        }
    });

    function updateDateTime() {
        const now = new Date();
        const options = {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };

        const formattedDate = now.toLocaleString('en-US', options);
        const datetimeElement = document.getElementById('datetime');

        if (datetimeElement) {
            datetimeElement.textContent = `As of ${formattedDate}`;
        }
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);
    </script>
</body>
</html>
