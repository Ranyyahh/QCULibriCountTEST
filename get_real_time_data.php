<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

date_default_timezone_set('Asia/Manila');

$conn = getDBConnection(false);

if (!$conn) {
    echo json_encode([
        'success' => false,
        'current' => 0,
        'max' => 50,
        'percentage' => 0,
        'activity' => [],
        'error' => 'Connection failed'
    ]);
    exit;
}

// ==============================
// MAX CAPACITY
// ==============================
$maxCapacity = 50;

$sql = "SELECT setting_value 
        FROM system_settings 
        WHERE setting_name = 'max_capacity'";

$result = odbc_exec($conn, $sql);

if ($result && $row = odbc_fetch_array($result)) {
    $maxCapacity = (int)$row['SETTING_VALUE'];
}

// ==============================
// CURRENT COUNT
// ==============================
$sql = "SELECT COUNT(*) AS current_count 
        FROM attendance_logs 
        WHERE status = 'inside' 
        AND time_out IS NULL";

$result = odbc_exec($conn, $sql);
$row = $result ? odbc_fetch_array($result) : false;

$currentCount = $row ? (int)$row['CURRENT_COUNT'] : 0;

// ==============================
// PERCENTAGE
// ==============================
$percentage = ($maxCapacity > 0) 
    ? round(($currentCount / $maxCapacity) * 100) 
    : 0;

// ==============================
// RECENT ACTIVITY
// ==============================
$sql = "
SELECT * FROM (
    SELECT 
        CASE 
            WHEN al.time_out IS NULL THEN '+1 Entry'
            ELSE '-1 Entry'
        END AS activity_type,
        s.firstname || ' ' || SUBSTR(s.lastname, 1, 1) || '.' AS student_name,
        CASE 
            WHEN al.time_out IS NULL THEN TO_CHAR(al.time_in, 'HH:MI AM')
            ELSE TO_CHAR(al.time_out, 'HH:MI AM')
        END AS activity_time,
        CASE 
            WHEN al.time_out IS NULL THEN TO_CHAR(al.time_in, 'MM/DD/YY')
            ELSE TO_CHAR(al.time_out, 'MM/DD/YY')
        END AS activity_date
    FROM attendance_logs al
    JOIN students s ON al.student_id = s.student_id
    ORDER BY al.time_in DESC
)
WHERE ROWNUM <= 12
";

$result = odbc_exec($conn, $sql);

$activities = [];

if ($result) {
    while ($row = odbc_fetch_array($result)) {
        $activities[] = [
            'type' => $row['ACTIVITY_TYPE'],
            'student' => $row['STUDENT_NAME'],
            'time' => $row['ACTIVITY_TIME'],
            'date' => $row['ACTIVITY_DATE']
        ];
    }
}

// ==============================
// OUTPUT
// ==============================
echo json_encode([
    'success' => true,
    'current' => $currentCount,
    'max' => $maxCapacity,
    'percentage' => $percentage,
    'activity' => $activities,
    'timestamp' => date('Y-m-d H:i:s')
]);

odbc_close($conn);
?>
