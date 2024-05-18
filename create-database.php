<?php

require_once('connect-db.php');
global  $pdo;
try {


    // Path to your SQL file
    $sql_file = "database.sql";

    // Read the SQL file
    $sql = file_get_contents($sql_file);

    // Execute the SQL query
    $pdo->exec($sql);

    echo "SQL file executed successfully.";
} catch (PDOException $e) {
    // Handle database errors
    echo "Error: " . $e->getMessage();
}

