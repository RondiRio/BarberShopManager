<?php
// verify_email.php
session_start();
require_once 'config/database.php';

if (isset($_GET['token'])) {
    $verification_token = trim($_GET['token']);

    if (empty($verification_token)) {
        $_SESSION['login_error_message'] = "Token de verificação inválido ou ausente.";
        header("location: login.php");
        exit;
    }

    // Busca o usuário pelo token de verificação
    // E verifica se a conta já não está ativa (para não re-ativar ou gastar o token à toa)
    $sql = "SELECT user_id, is_active FROM users WHERE verificar_token = ? LIMIT 1";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $verification_token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id, $is_active_db);
            $stmt->fetch();

            if ($is_active_db == 1) {
                $_SESSION['login_message'] = "Sua conta já está ativa. Você pode fazer login."; // Mensagem informativa
                $_SESSION['login_message_type'] = "success";
            } else {
                // Ativa a conta e limpa o token
                $sql_update = "UPDATE users SET is_active = 1, verificar_token = NULL WHERE user_id = ?";
                if ($stmt_update = $mysqli->prepare($sql_update)) {
                    $stmt_update->bind_param("i", $user_id);
                    if ($stmt_update->execute()) {
                        $_SESSION['login_message'] = "Email verificado com sucesso! Você já pode fazer login.";
                        $_SESSION['login_message_type'] = "success";
                    } else {
                        $_SESSION['login_error_message'] = "Erro ao ativar sua conta. Tente novamente mais tarde ou contate o suporte.";
                    }
                    $stmt_update->close();
                } else {
                     $_SESSION['login_error_message'] = "Erro ao preparar ativação. Contate o suporte.";
                }
            }
        } else {
            $_SESSION['login_error_message'] = "Token de verificação inválido ou expirado.";
        }
        $stmt->close();
    } else {
        $_SESSION['login_error_message'] = "Erro ao processar verificação. Contate o suporte.";
    }
    $mysqli->close();
    header("location: login.php");
    exit;

} else {
    $_SESSION['login_error_message'] = "Nenhum token de verificação fornecido.";
    header("location: login.php");
    exit;
}
?>