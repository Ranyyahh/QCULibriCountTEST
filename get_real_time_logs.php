<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

date_default_timezone_set('Asia/Manila');

require_once 'config.php';

$conn = getDBConnection();

if (!$conn) {
    echo json_encode(["success" => false, "activity" => [], "error" => "Connection failed"]);
    exit();
}

$sql = "SELECT 
            al.time_in,
            al.time_out,
            s.student_number,
            s.firstname,
            s.lastname,
            s.course,
            al.status,
            al.session_duration
        FROM attendance_logs al
        JOIN students s ON al.student_id = s.student_id
        ORDER BY al.time_in DESC";

$result = odbc_exec($conn, $sql);

$activities = [];

while (odbc_fetch_row($result)) {
    $timeInRaw = odbc_result($result, 'TIME_IN');
    $timeOutRaw = odbc_result($result, 'TIME_OUT');
    
    // Add 6 hours (4:36 AM system -> 10:36 AM Pinas)
    $timeIn = add6Hours($timeInRaw);
    $timeOut = $timeOutRaw ? add6Hours($timeOutRaw) : null;
    
    $displayTime = $timeOut ? $timeOut : $timeIn;
    
    $activities[] = [
        'type' => $timeOut ? '-1 Entry' : '+1 Entry',
        'action' => $timeOut ? 'Time out' : 'Time in',
        'time' => date('h:i A', strtotime($displayTime)),
        'date' => date('m/d/y', strtotime($displayTime)),
        'student_number' => odbc_result($result, 'STUDENT_NUMBER'),
        'firstname' => odbc_result($result, 'FIRSTNAME'),
        'lastname' => odbc_result($result, 'LASTNAME'),
        'course' => odbc_result($result, 'COURSE'),
        'status' => odbc_result($result, 'STATUS'),
        'duration' => odbc_result($result, 'SESSION_DURATION')
    ];
}

echo json_encode([
    'success' => true,
    'activity' => $activities,
    'ph_time_now' => date('Y-m-d H:i:s')
]);

odbc_close($conn);

function add6Hours($oracleTime) {
    if (!$oracleTime) return null;
    
    $oracleTime = trim($oracleTime);
    $date = new DateTime($oracleTime);
    
    // Add 6 hours
    $date->modify('+6 hours');
    
    return $date->format('Y-m-d H:i:s');
}
?>