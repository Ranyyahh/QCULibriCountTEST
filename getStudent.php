<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$conn = getDBConnection();

if (isset($_GET['studentNo']) && !empty($_GET['studentNo'])) {

    $studentNo = trim($_GET['studentNo']);
    
    // Direct query - exact match
    $sql = "SELECT 
                firstname, 
                middlename, 
                lastname, 
                course, 
                year_level
            FROM students 
            WHERE student_number = '$studentNo'";
    
    error_log("SQL Query: " . $sql);
    
    $result = odbc_exec($conn, $sql);
    
    if (!$result) {
        echo json_encode([
            "error" => "Query execution failed",
            "sql_error" => odbc_errormsg($conn),
            "sql" => $sql
        ]);
        odbc_close($conn);
        exit();
    }
    
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
        // Subukan kung may kahit anong record sa table
        $testSql = "SELECT COUNT(*) as total FROM students";
        $testResult = odbc_exec($conn, $testSql);
        $totalRecords = 0;
        if ($testResult && odbc_fetch_row($testResult)) {
            $totalRecords = odbc_result($testResult, 'TOTAL');
        }
        
        echo json_encode([
            "error" => "Student not found",
            "student_number_searched" => $studentNo,
            "total_records_in_table" => $totalRecords,
            "hint" => "Make sure the student number exists exactly as in database"
        ]);
    }

} else {
    echo json_encode(["error" => "Student number not provided"]);
}

odbc_close($conn);
?>