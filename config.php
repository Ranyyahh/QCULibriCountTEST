<?php

define('DB_HOST', 'localhost');        
define('DB_PORT', '1521');             
define('DB_SERVICE', 'XE');            
define('DB_USER', 'libri_user');      
define('DB_PASS', 'libri_pass');       

function getDBConnection() {
    
    $conn_string = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=" . DB_HOST . ")(PORT=" . DB_PORT . "))(CONNECT_DATA=(SERVICE_NAME=" . DB_SERVICE . ")))";
    
   
    $conn = odbc_connect($conn_string, DB_USER, DB_PASS);
    
    if (!$conn) {
        die("Connection failed: " . odbc_errormsg());
    }
    
    return $conn;
}

$conn = getDBConnection();
?>
