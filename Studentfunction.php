<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'config.php';

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// eto yung para sa auto fill haahahh
if ($method === 'GET' && isset($_GET['studentNo'])) {
    $studentNo = $_GET['studentNo'];
    
    $sql = "SELECT firstname, middlename, lastname, course, year_level 
            FROM students 
            WHERE student_number = '$studentNo'";
    
    $result = odbc_exec($conn, $sql);
    
    if (odbc_fetch_row($result)) {
        $student = [
            "firstName" => odbc_result($result, 'FIRSTNAME'),
            "middleName" => odbc_result($result, 'MIDDLENAME'),
            "lastName" => odbc_result($result, 'LASTNAME'),
            "course" => odbc_result($result, 'COURSE'),
            "yearLvl" => odbc_result($result, 'YEAR_LEVEL')
        ];
        echo json_encode($student);
    } else {
        echo json_encode(["error" => "Student not found"]);
    }
    
    odbc_close($conn);
    exit();
}

// post para sa time in and out
if ($method === 'POST') {
    $studentNo = isset($_POST['studentNo']) ? trim($_POST['studentNo']) : '';
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if (empty($studentNo)) {
        echo json_encode(["error" => "Student number is required"]);
        odbc_close($conn);
        exit();
    }
    
    // student info
    $sql = "SELECT student_id, firstname, lastname FROM students WHERE student_number = '$studentNo'";
    $result = odbc_exec($conn, $sql);
    
    if (!$result || !odbc_fetch_row($result)) {
        echo json_encode(["error" => "Student not found"]);
        odbc_close($conn);
        exit();
    }
    
    $student_id = odbc_result($result, 'STUDENT_ID');
    $firstname = odbc_result($result, 'FIRSTNAME');
    $lastname = odbc_result($result, 'LASTNAME');
    $now = date('Y-m-d H:i:s');
    
    // taym in
    if ($action === 'timein') {
        
        // Check if already inside
        $checkSql = "SELECT COUNT(*) as active_count FROM attendance_logs 
                     WHERE student_id = $student_id AND status = 'inside' AND time_out IS NULL";
        $checkResult = odbc_exec($conn, $checkSql);
        $activeCount = 0;
        
        if ($checkResult && odbc_fetch_row($checkResult)) {
            $activeCount = odbc_result($checkResult, 'ACTIVE_COUNT');
        }
        
        if ($activeCount > 0) {
            echo json_encode(["error" => "You are already inside the library. Please time out first."]);
            odbc_close($conn);
            exit();
        }
        
        // mag iinserrt ng time in record
        $insertSql = "INSERT INTO attendance_logs (log_id, student_id, time_in, status) 
                      VALUES (seq_attendance_log_id.NEXTVAL, $student_id, TO_TIMESTAMP('$now', 'YYYY-MM-DD HH24:MI:SS'), 'inside')";
        $insert = odbc_exec($conn, $insertSql);
        
        if ($insert) {
            echo json_encode(["success" => true, "message" => "Time IN recorded successfully"]);
        } else {
            echo json_encode(["error" => "Failed to record Time IN"]);
        }
        
        odbc_close($conn);
        exit();
    }
    
    // taym out
    if ($action === 'timeout') {
        
        // Check if has active time in
        $checkSql = "SELECT log_id, time_in FROM attendance_logs 
                     WHERE student_id = $student_id AND status = 'inside' AND time_out IS NULL
                     ORDER BY time_in DESC";
        $checkResult = odbc_exec($conn, $checkSql);
        
        if (!$checkResult || !odbc_fetch_row($checkResult)) {
            echo json_encode(["error" => "You have no active Time IN record. Please time in first."]);
            odbc_close($conn);
            exit();
        }
        
        $logId = odbc_result($checkResult, 'LOG_ID');
        $timeIn = odbc_result($checkResult, 'TIME_IN');
        
        
        $timeInTimestamp = strtotime($timeIn);
        $timeOutTimestamp = strtotime($now);
        $sessionDuration = round(($timeOutTimestamp - $timeInTimestamp) / 60);
        
        if ($sessionDuration < 0) {
            $sessionDuration = 0;
        }
        
        // Update time out
        $updateSql = "UPDATE attendance_logs 
                      SET time_out = TO_TIMESTAMP('$now', 'YYYY-MM-DD HH24:MI:SS'), 
                          status = 'exited',
                          session_duration = $sessionDuration
                      WHERE log_id = $logId";
        $update = odbc_exec($conn, $updateSql);
        
        if ($update) {
            echo json_encode(["success" => true, "message" => "Time OUT recorded successfully"]);
        } else {
            echo json_encode(["error" => "Failed to record Time OUT"]);
        }
        
        odbc_close($conn);
        exit();
    }
    
    echo json_encode(["error" => "Invalid action"]);
    odbc_close($conn);
    exit();
}

odbc_close($conn);
?>