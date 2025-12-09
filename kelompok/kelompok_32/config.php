<?php
$host = "localhost";
$username = "root";
$password = "";  
$database = "ujiann";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

date_default_timezone_set('Asia/Jakarta');
?>