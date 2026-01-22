<?php
// db.php
//live
// $host = 'localhost';        // Change to your DB host
// $db   = 'u499616432_adullamn_cams';       // Change to your DB name
// $user = 'u499616432_adullamn';             // Change to your DB user
// $pass = 'Rq;u54Y77#QFxx';                 // Change to your DB password
// $charset = 'utf8mb4';

//local
$host = 'localhost';        // Change to your DB host
$db   = 'u499616432_adullamn_cams';       // Change to your DB name
$user = 'root';             // Change to your DB user
$pass = '';                 // Change to your DB password
$charset = 'utf8mb4';

// PDO Setup
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}



//$con=mysqli_connect("localhost", "u499616432_adullamn", "Rq;u54Y77#QFxx", "u499616432_adullamn_cams");