<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho se necessário

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    $_SESSION['message'] = "Acesso não autorizado.";
    $_SESSION['message_type'] = "error";
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        $_SESSION['message'] = "ID do barbeiro inválido.";
        $_SESSION['message_type'] = "error";
        header("location: dashboard.php"); // Volta para a lista de barbeiros
        exit;
    }
    $user_id = (int)$_POST['user_id'];

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $commission_rate_input = trim($_POST['commission_rate']);
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $errors = [];

    // Validações básicas
    if (empty($name)) {
        $errors[] = "O nome é obrigatório.";
    }
    if (empty($email)) {
        $errors[] = "O email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Formato de email inválido.";
    } else {
        // Verifica se o NOVO email já existe para OUTRO usuário
        $sql_check_email = "SELECT user_id FROM Users WHERE email = ? AND user_id != ?";
        if ($stmt_check = $mysqli->prepare($sql_check_email)) {
            $stmt_check->bind_param("si", $email, $user_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Este email já está cadastrado para outro usuário.";
            }
            $stmt_check->close();
        } else {
            $errors[] = "Erro ao verificar email: " . $mysqli->error;
        }
    }

    if (empty($commission_rate_input)) {
        $errors[] = "A taxa de comissão é obrigatória.";
    } elseif (!is_numeric($commission_rate_input) || $commission_rate_input < 0 || $commission_rate_input > 1) {
        $errors[] = "A taxa de comissão deve ser um número entre 0 e 1 (ex: 0.5).";
    }
    $commission_rate = (float)$commission_rate_input;

    if (!in_array($is_active, [0, 1])) {
        $errors[] = "Status inválido.";
    }

    // Validação de senha (somente se uma nova senha for fornecida)
    $update_password_sql_part = "";
    $params_for_password = [];
    $types_for_password = "";

    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "A nova senha deve ter pelo menos 6 caracteres.";
        } elseif ($password !== $confirm_password) {
            $errors[] = "As novas senhas não coincidem.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_password_sql_part = ", password = ?";
            $params_for_password[] = $hashed_password;
            $types_for_password = "s"; // tipo 'string' para o hash da senha
        }
    }

    if (empty($errors)) {
        $sql_update = "UPDATE Users SET name = ?, email = ?, commission_rate = ?, is_active = ?" . $update_password_sql_part . " WHERE user_id = ? AND role = 'barber'";
        
        $param_types = "ssdis" . $types_for_password . "i"; // s(name), s(email), d(commission_rate), i(is_active), [s(password)], i(user_id)
        $params = [$name, $email, $commission_rate, $is_active];
        if (!empty($params_for_password)) {
            $params = array_merge($params, $params_for_password);
        }
        $params[] = $user_id;


        if ($stmt_update = $mysqli->prepare($sql_update)) {
            // Usar call_user_func_array para bind_param dinâmico
            // Primeiro argumento é a string de tipos, os demais são as variáveis por referência
            $bind_names[] = $param_types;
            for ($i=0; $i<count($params);$i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $params[$i];
                $bind_names[] = &$$bind_name;
            }
            call_user_func_array(array($stmt_update,'bind_param'), $bind_names);


            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Dados do barbeiro '".htmlspecialchars($name)."' atualizados com sucesso!";
                $_SESSION['message_type'] = "success";
                header("location: dashboard.php"); // Volta para a lista
                exit;
            } else {
                $_SESSION['message_form_edit'] = "Erro ao atualizar barbeiro: " . $stmt_update->error;
                $_SESSION['message_form_edit_type'] = "error";
            }
            $stmt_update->close();
        } else {
            $_SESSION['message_form_edit'] = "Erro ao preparar para atualizar barbeiro: " . $mysqli->error;
            $_SESSION['message_form_edit_type'] = "error";
        }
    } else {
        $_SESSION['message_form_edit'] = implode("<br>", $errors);
        $_SESSION['message_form_edit_type'] = "error";
    }

    $mysqli->close();
    // Se houve erro, redireciona de volta para a página de edição com o ID
    header("location: edit_barber.php?id=" . $user_id);
    exit;

} else {
    // Se não for POST, redireciona
    $_SESSION['message'] = "Método não permitido.";
    $_SESSION['message_type'] = "error";
    header("location: dashboard.php");
    exit;
}
?>