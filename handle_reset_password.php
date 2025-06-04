<?php
session_start();
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $user_id_for_reset = isset($_POST['user_id_for_reset']) ? (int)$_POST['user_id_for_reset'] : 0; // Pega o user_id do formulário
    $new_password = trim($_POST['new_password']);
    $confirm_new_password = trim($_POST['confirm_new_password']);

    $_SESSION['form_data_reset'] = $_POST; // Para repreencher em caso de erro se necessário
    $errors = [];

    if (empty($token) || $user_id_for_reset <= 0) {
        $errors[] = "Token ou identificador de usuário inválido.";
    }
    if (empty($new_password)) {
        $errors[] = "A nova senha é obrigatória.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "A nova senha deve ter pelo menos 6 caracteres.";
    } elseif ($new_password !== $confirm_new_password) {
        $errors[] = "As novas senhas não coincidem.";
    }

    if (empty($errors)) {
        // Verifica novamente o token e se ele corresponde ao user_id (segurança extra)
        $sql_verify = "SELECT user_id FROM Users WHERE user_id = ? AND reset_token = ? AND reset_token_expiry > NOW() LIMIT 1";
        if ($stmt_verify = $mysqli->prepare($sql_verify)) {
            $stmt_verify->bind_param("is", $user_id_for_reset, $token);
            $stmt_verify->execute();
            $stmt_verify->store_result();

            if ($stmt_verify->num_rows == 1) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Atualiza a senha e limpa o token de reset
                $sql_update = "UPDATE Users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?";
                if ($stmt_update_pass = $mysqli->prepare($sql_update)) {
                    $stmt_update_pass->bind_param("si", $hashed_password, $user_id_for_reset);
                    if ($stmt_update_pass->execute()) {
                        unset($_SESSION['form_data_reset']);
                        $_SESSION['login_message'] = "Sua senha foi redefinida com sucesso! Você já pode fazer login com a nova senha.";
                        $_SESSION['login_message_type'] = "success";
                        header("location: login.php");
                        exit;
                    } else {
                        $errors[] = "Erro ao atualizar a senha: " . $stmt_update_pass->error;
                    }
                    $stmt_update_pass->close();
                } else {
                    $errors[] = "Erro ao preparar atualização da senha: " . $mysqli->error;
                }
            } else {
                $errors[] = "Token de redefinição inválido, expirado ou não corresponde ao usuário. Por favor, solicite um novo link.";
            }
            $stmt_verify->close();
        } else {
             $errors[] = "Erro ao verificar token: " . $mysqli->error;
        }
    }

    if (!empty($errors)) {
        $_SESSION['reset_message'] = implode("<br>", $errors);
        $_SESSION['reset_message_type'] = "error";
        // Redireciona de volta para reset_password.php COM o token na URL para que a página possa revalidá-lo
        header("location: reset_password.php?token=" . urlencode($token));
        exit;
    }

} else {
    header("location: login.php");
    exit;
}
?>