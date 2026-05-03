<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ORACLE CONNECTION
$conn = oci_connect('libricount', 'password', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    echo json_encode(["error" => $e['message']]);
    exit;
}

// ==============================
// MAX CAPACITY
// ==============================
$maxCapacity = 50;

$sql = "SELECT setting_value 
        FROM system_settings 
        WHERE setting_name = 'max_capacity'";

$stid = oci_parse($conn, $sql);
oci_execute($stid);

if ($row = oci_fetch_assoc($stid)) {
    $maxCapacity = (int)$row['SETTING_VALUE'];
}

// ==============================
// CURRENT COUNT
// ==============================
$sql = "SELECT COUNT(*) AS current_count 
        FROM attendance_logs 
        WHERE status = 'inside' 
        AND time_out IS NULL";

$stid = oci_parse($conn, $sql);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

$currentCount = (int)$row['CURRENT_COUNT'];

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
        END AS type,
        s.firstname || ' ' || SUBSTR(s.lastname, 1, 1) || '.' AS student,
        CASE 
            WHEN al.time_out IS NULL THEN TO_CHAR(al.time_in, 'HH:MI AM')
            ELSE TO_CHAR(al.time_out, 'HH:MI AM')
        END AS time,
        CASE 
            WHEN al.time_out IS NULL THEN TO_CHAR(al.time_in, 'MM/DD/YY')
            ELSE TO_CHAR(al.time_out, 'MM/DD/YY')
        END AS date
    FROM attendance_logs al
    JOIN students s ON al.student_id = s.student_id
    ORDER BY al.time_in DESC
)
WHERE ROWNUM <= 12
";

$stid = oci_parse($conn, $sql);
oci_execute($stid);

$activities = [];

while ($row = oci_fetch_assoc($stid)) {
    $activities[] = [
        'type' => $row['TYPE'],
        'student' => $row['STUDENT'],
        'time' => $row['TIME'],
        'date' => $row['DATE']
    ];
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

oci_close($conn);
?>