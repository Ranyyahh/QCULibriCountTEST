<?php
// Oracle Database Configuration - ODBC Version

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_USER', 'system');
define('DB_PASS', '1234');
define('DB_DSN', 'QCU_Libricount');  

function getDBConnection() {
    $conn = odbc_connect(DB_DSN, DB_USER, DB_PASS);
    
    if (!$conn) {
        die("Connection failed: " . odbc_errormsg());
    }
    
    return $conn;
}

// Helper functions
function executeQuery($conn, $sql) {
    return odbc_exec($conn, $sql);
}

function fetchAll($result) {
    $rows = [];
    while ($row = odbc_fetch_array($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function fetchOne($result) {
    return odbc_fetch_array($result);
}
?>
