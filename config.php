<?php
// Oracle Database Configuration - ODBC Version

define('DB_USER', 'system');
define('DB_PASS', '1234');
define('DB_DSN', 'QCU_Libricount');  

function getDBConnection($haltOnFailure = true) {
    $conn = @odbc_connect(DB_DSN, DB_USER, DB_PASS);
    
    if (!$conn) {
        if ($haltOnFailure) {
            die("Connection failed: " . dbError());
        }

        return false;
    }
    
    return $conn;
}

// Helper functions
function dbError($conn = null) {
    $message = $conn ? odbc_errormsg($conn) : odbc_errormsg();
    return $message ?: 'Unknown database error';
}

function executeQuery($conn, $sql) {
    return odbc_exec($conn, $sql);
}

function executePrepared($conn, $sql, $params = []) {
    $stmt = odbc_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    return odbc_execute($stmt, $params);
}

function executeProcedure($conn, $procedureName, $params = []) {
    $placeholders = implode(', ', array_fill(0, count($params), '?'));
    $sql = "BEGIN {$procedureName}({$placeholders}); END;";

    return executePrepared($conn, $sql, $params);
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
