<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$username = "your_oracle_user";
$password = "your_oracle_password";
$connection_string = "localhost/XEPDB1"; // palitan base sa Oracle service mo

$conn = oci_connect($username, $password, $connection_string);

if (!$conn) {
    $e = oci_error();
    echo json_encode([
        "success" => false,
        "error" => "Connection failed: " . $e['message']
    ]);
    exit;
}

$sql = "
    SELECT 
        CASE 
            WHEN al.time_out IS NULL THEN '+1 Entry'
            ELSE '-1 Entry'
        END AS type,
        CASE 
            WHEN al.time_out IS NULL THEN 'Time in'
            ELSE 'Time out'
        END AS action,
        TO_CHAR(
            CASE 
                WHEN al.time_out IS NULL THEN al.time_in
                ELSE al.time_out
            END, 'HH:MI AM'
        ) AS time,
        TO_CHAR(
            CASE 
                WHEN al.time_out IS NULL THEN al.time_in
                ELSE al.time_out
            END, 'MM/DD/YY'
        ) AS date_value
    FROM attendance_logs al
    ORDER BY al.time_in DESC
";

$stid = oci_parse($conn, $sql);
$r = oci_execute($stid);

if (!$r) {
    $e = oci_error($stid);
    echo json_encode([
        "success" => false,
        "error" => $e['message']
    ]);
    oci_free_statement($stid);
    oci_close($conn);
    exit;
}

$activities = [];
while ($row = oci_fetch_assoc($stid)) {
    $activities[] = [
        'type' => $row['TYPE'],
        'action' => $row['ACTION'],
        'time' => $row['TIME'],
        'date' => $row['DATE_VALUE']
    ];
}

echo json_encode([
    "success" => true,
    "activity" => $activities
]);

oci_free_statement($stid);
oci_close($conn);
?>
