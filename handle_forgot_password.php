<?php
session_start();
require_once 'config/database.php';
require('PHPMailer/vendor/autoload.php'); // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_message'] = "Por favor, insira um email válido.";
        $_SESSION['forgot_message_type'] = "error";
        header("location: forgot_password.php");
        exit;
    }

    // Verifica se o email existe no banco
    $sql_check_email = "SELECT user_id, name FROM Users WHERE email = ? AND is_active = 1 LIMIT 1"; // Só para contas ativas
    if ($stmt_check = $mysqli->prepare($sql_check_email)) {
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($user = $result->fetch_assoc()) {
            $user_id = $user['user_id'];
            $user_name = $user['name'];

            $reset_token = bin2hex(random_bytes(32));
            $reset_token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora

            // Salva o token e a data de expiração no banco
            $sql_update_token = "UPDATE Users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
            if ($stmt_update = $mysqli->prepare($sql_update_token)) {
                $stmt_update->bind_param("ssi", $reset_token, $reset_token_expiry, $user_id);
                if ($stmt_update->execute()) {
                    // Envia o email com o link de redefinição
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $reset_token;
                    
                    $mail = new PHPMailer(true);
                    try {
                        // Configurações do Servidor SMTP (COPIE DO HANDLE_REGISTER.PHP E AJUSTE)
                        $mail->isSMTP();
                        $mail->Host       = 'seu_servidor_smtp.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'seu_email@seudominio.com';
                        $mail->Password   = 'sua_senha_de_email_ou_app';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->CharSet    = 'UTF-8';

                        $mail->setFrom('naoresponda@barbeariajb.com', 'Barbearia JB');
                        $mail->addAddress($email, $user_name);

                        $mail->isHTML(true);
                        $mail->Subject = 'Redefinição de Senha - Barbearia JB';
                        $mail->Body    = "Olá " . htmlspecialchars($user_name) . ",<br><br>" .
                                         "Recebemos uma solicitação para redefinir sua senha. Se foi você, clique no link abaixo:<br>" .
                                         "<a href='" . $reset_link . "'>" . $reset_link . "</a><br><br>" .
                                         "Este link é válido por 1 hora. Se você não solicitou esta alteração, pode ignorar este email.<br><br>" .
                                         "Atenciosamente,<br>Equipe Barbearia JB";
                        $mail->AltBody = "Olá " . htmlspecialchars($user_name) . ",\n\n" .
                                         "Para redefinir sua senha, copie e cole o seguinte link no seu navegador:\n" .
                                         $reset_link . "\n\nEste link é válido por 1 hora.\n\n" .
                                         "Atenciosamente,\nEquipe Barbearia JB";
                        
                        $mail->send();
                        $_SESSION['forgot_message'] = "Se existir uma conta associada a " . htmlspecialchars($email) . ", um link para redefinir a senha foi enviado.";
                        $_SESSION['forgot_message_type'] = "success";

                    } catch (Exception $e) {
                        error_log("PHPMailer Error (Forgot Pass): " . $mail->ErrorInfo);
                        $_SESSION['forgot_message'] = "Não foi possível enviar o email de redefinição. Tente novamente mais tarde ou contate o suporte. (Link para teste: $reset_link)";
                        $_SESSION['forgot_message_type'] = "error";
                    }
                } else {
                    $_SESSION['forgot_message'] = "Erro ao salvar informações de redefinição. Tente novamente.";
                    $_SESSION['forgot_message_type'] = "error";
                }
                $stmt_update->close();
            } else {
                 $_SESSION['forgot_message'] = "Erro ao preparar para salvar informações de redefinição.";
                 $_SESSION['forgot_message_type'] = "error";
            }
        } else {
            // Email não encontrado ou conta inativa, mas mostramos mensagem genérica por segurança
            $_SESSION['forgot_message'] = "Se existir uma conta associada a " . htmlspecialchars($email) . ", um link para redefinir a senha foi enviado.";
            $_SESSION['forgot_message_type'] = "success"; // Mensagem genérica
        }
        $stmt_check->close();
    } else {
         $_SESSION['forgot_message'] = "Erro ao verificar email.";
         $_SESSION['forgot_message_type'] = "error";
    }

    $mysqli->close();
    header("location: forgot_password.php");
    exit;
} else {
    header("location: forgot_password.php");
    exit;
}
?>