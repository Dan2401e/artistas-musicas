<?php
/*
 * =============================================
 * ARQUIVO: conexao.php
 * FUNÇÃO: Conectar o PHP ao banco de dados MySQL
 * =============================================
 */

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sistema_musical_v2";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . "img")) {
    mkdir(__DIR__ . DIRECTORY_SEPARATOR . "img", 0777, true);
}
