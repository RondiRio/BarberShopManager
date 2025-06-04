<?php
// config/database.php
define('DB_SERVER', 'localhost'); // Ou o IP do seu servidor MySQL
define('DB_USERNAME', 'root');    // Seu usuário do MySQL
define('DB_PASSWORD', '');        // Sua senha do MySQL
define('DB_NAME', 'barbearia_jb'); // O nome do seu banco de dados


// Tenta conectar ao banco de dados MySQL
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão
if($mysqli === false){
    die("ERRO: Não foi possível conectar ao MySQL. ");
}

// Opcional: Define o charset para utf8 (bom para caracteres acentuados)
if (!$mysqli->set_charset("utf8")) {
    // printf("Erro ao definir utf8: %s\n", $mysqli->error);
    // exit();
}
?>