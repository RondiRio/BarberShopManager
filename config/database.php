<?php
// config/database.php

if (
    isset($_SERVER['HTTP_HOST']) &&
    ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')
) {
    // Ambiente local
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'barbearia_jb');
} else {
    // Ambiente de produção
    define('DB_SERVER', 'SEU_HOST_PRODUCAO');
    define('DB_USERNAME', 'SEU_USUARIO_PRODUCAO');
    define('DB_PASSWORD', 'SUA_SENHA_PRODUCAO');
    define('DB_NAME', 'SEU_BANCO_PRODUCAO');
}

// Tenta conectar ao banco de dados MySQL
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão
if($mysqli === false){
    die("ERRO: Não foi possível conectar ao MySQL. ");
}

// Opcional: Define o charset para utf8
if (!$mysqli->set_charset("utf8")) {
    // printf("Erro ao definir utf8: %s\n", $mysqli->error);
    // exit();
}
?>