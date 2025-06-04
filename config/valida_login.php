<?php
// handle_login.php
ini_set('display_errors', 1); // Mantenha para depuração durante o teste, remova em produção
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php'; // Garanta que este caminho está correto

$email = "";
$password_from_form = ""; // Renomeado para clareza
$login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["email"]))) {
        $login_err = "Por favor, insira o email.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty(trim($_POST["password"]))) {
        $login_err = "Por favor, insira a senha.";
    } else {
        $password_from_form = trim($_POST["password"]);
    }

    if (empty($login_err)) {
        // Seleciona o hash da senha do banco de dados
        $sql = "SELECT user_id, name, email, password, role, commission_rate FROM Users WHERE email = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    // A variável $db_hashed_password armazenará o HASH da senha vindo do BD
                    $stmt->bind_result($user_id, $name, $db_email, $db_hashed_password, $role, $commission_rate);
                    if ($stmt->fetch()) {
                        // *** VERIFICAÇÃO DE SENHA COM HASH ***
                        // Verifica se a senha está em hash ou em texto puro (legado)
                        if (
                            password_verify($password_from_form, $db_hashed_password) ||
                            $password_from_form === $db_hashed_password // Senha em texto puro (NÃO RECOMENDADO, apenas para compatibilidade)
                        ) {
                            // Senha está correta, então inicia uma nova sessão
                            session_regenerate_id();

                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $db_email;
                            $_SESSION["role"] = $role;
                            if ($role === 'barber') {
                                $_SESSION["commission_rate"] = $commission_rate;
                            }

                            // Redireciona o usuário com base no papel (role)
                            if ($role == 'admin') {
                                header("location: ../admin/dashboard.php");
                                exit;
                            } elseif ($role == 'barber') {
                                header("location: ../barber/dashboard.php");
                                exit;
                            } elseif ($role == 'customer') {
                                header("location: ../customer/dashboard.php");
                                exit;
                            } else {
                                $login_err = "Tipo de usuário não configurado para redirecionamento.";
                            }
                        } else {
                            // Senha não é válida
                            $login_err = "Email ou senha inválidos.";
                        }
                    }
                } else {
                    // Email não existe
                    $login_err = "Email ou senha inválidos.";
                }
            } else {
                $login_err = "Oops! Algo deu errado na execução da consulta: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $login_err = "Erro ao preparar a consulta: " . htmlspecialchars($mysqli->error);
        }
    }
    $mysqli->close();

    if (!empty($login_err)) {
        $_SESSION['login_error_message'] = $login_err;
        header("location: ../login.php");
        exit;
    }
} else {
    header("location: ../login.php");
    exit;
}
?>