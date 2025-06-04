<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho se necessário

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    // Se não for admin, redireciona ou mostra erro
    $_SESSION['message'] = "Acesso não autorizado.";
    $_SESSION['message_type'] = "error";
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $commission_rate_input = trim($_POST['commission_rate']);

    $errors = [];

    // Validações
    if (empty($name)) {
        $errors[] = "O nome é obrigatório.";
    }
    if (empty($email)) {
        $errors[] = "O email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Formato de email inválido.";
    } else {
        // Verifica se o email já existe
        $sql_check_email = "SELECT user_id FROM users WHERE email = ?";
        if ($stmt_check = $mysqli->prepare($sql_check_email)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Este email já está cadastrado.";
            }
            $stmt_check->close();
        } else {
            $errors[] = "Erro ao verificar email: " . $mysqli->error;
        }
    }

    if (empty($password)) {
        $errors[] = "A senha é obrigatória.";
    } elseif (strlen($password) < 6) {
        $errors[] = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "As senhas não coincidem.";
    }

    if (empty($commission_rate_input)) {
        $errors[] = "A taxa de comissão é obrigatória.";
    } elseif (!is_numeric($commission_rate_input) || $commission_rate_input < 0 || $commission_rate_input > 1) {
        $errors[] = "A taxa de comissão deve ser um número entre 0 e 1 (ex: 0.5 para 50%).";
    }
    // Converte para float após validação
    $commission_rate = (float) $commission_rate_input;


    if (empty($errors)) {
        // Tudo OK, vamos inserir o barbeiro
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // HASH DA SENHA!
        $role = 'barber';
        $is_active = 1; // Novo barbeiro começa ativo

        $sql_insert = "INSERT INTO Users (name, email, password, role, commission_rate, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt_insert = $mysqli->prepare($sql_insert)) {
            $stmt_insert->bind_param("ssssdi", $name, $email, $hashed_password, $role, $commission_rate, $is_active);

            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Barbeiro '".htmlspecialchars($name)."' adicionado com sucesso!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Erro ao adicionar barbeiro: " . $stmt_insert->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_insert->close();
        } else {
            $_SESSION['message'] = "Erro ao preparar para adicionar barbeiro: " . $mysqli->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        // Se houver erros de validação, armazena-os para exibir
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "error";
        // Opcional: armazenar os dados do formulário para preenchê-los novamente
        // $_SESSION['form_data'] = $_POST;
    }

    $mysqli->close();
    header("location: dashboard.php"); // Redireciona de volta para o dashboard
    exit;

} else {
    // Se não for POST, redireciona
    $_SESSION['message'] = "Método não permitido.";
    $_SESSION['message_type'] = "error";
    header("location: dashboard.php");
    exit;
}
?>