<?php
// handle_register.php
session_start();
require_once 'config/database.php';

// Inclui o autoload do Composer (necessário para PHPMailer se instalado via Composer)
require_once 'vendor/autoload.php'; // Ajuste o caminho se o vendor estiver em outro lugar

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $_SESSION['form_data'] = $_POST;
    $errors = [];

    // ... (suas validações existentes aqui: nome, email, duplicidade de email, senha, etc.) ...
    if (empty($name)) { $errors[] = "O nome completo é obrigatório."; }
    if (empty($email)) { $errors[] = "O email é obrigatório."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Formato de email inválido."; }
    else {
        $sql_check_email = "SELECT user_id FROM Users WHERE email = ?";
        if ($stmt_check = $mysqli->prepare($sql_check_email)) {
            $stmt_check->bind_param("s", $email); $stmt_check->execute(); $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) { $errors[] = "Este email já está cadastrado."; }
            $stmt_check->close();
        } else { $errors[] = "Erro ao verificar email."; }
    }
    if (empty($password)) { $errors[] = "A senha é obrigatória."; }
    elseif (strlen($password) < 6) { $errors[] = "A senha deve ter pelo menos 6 caracteres."; }
    elseif ($password !== $confirm_password) { $errors[] = "As senhas não coincidem."; }


    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';
        $is_active = 0; // Inativo até verificação
        $verification_token = bin2hex(random_bytes(32));

        $sql_insert = "INSERT INTO Users (name, email, password, role, is_active, verification_token, commission_rate) VALUES (?, ?, ?, ?, ?, ?, NULL)";
        if ($stmt_insert = $mysqli->prepare($sql_insert)) {
            $stmt_insert->bind_param("ssssis", $name, $email, $hashed_password, $role, $is_active, $verification_token);

            if ($stmt_insert->execute()) {
                unset($_SESSION['form_data']);
                $user_id_inserted = $stmt_insert->insert_id; // Pega o ID do usuário inserido

                // Envio do Email de Verificação
                $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?token=" . $verification_token;
                
                $mail = new PHPMailer(true);
                try {
                    // Configurações do Servidor SMTP (SUBSTITUA COM AS SUAS)
                    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Para depuração detalhada
                    $mail->isSMTP();
                    $mail->Host       = 'seu_servidor_smtp.com';  // Ex: smtp.gmail.com ou o SMTP do seu provedor
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'seu_email@seudominio.com'; // Seu email de envio
                    $mail->Password   = 'sua_senha_de_email_ou_app'; // Sua senha
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Ou PHPMailer::ENCRYPTION_SMTPS
                    $mail->Port       = 587; // Porta TLS (ou 465 para SMTPS)
                    $mail->CharSet    = 'UTF-8';

                    // Remetente e Destinatário
                    $mail->setFrom('naoresponda@barbeariajb.com', 'Barbearia JB'); // Email e nome que aparecerão como remetente
                    $mail->addAddress($email, $name);     // Email e nome do novo usuário

                    // Conteúdo do Email
                    $mail->isHTML(true);
                    $mail->Subject = 'Ative sua conta na Barbearia JB';
                    $mail->Body    = "Olá " . htmlspecialchars($name) . ",<br><br>" .
                                     "Obrigado por se cadastrar na Barbearia JB! Por favor, clique no link abaixo para ativar sua conta:<br>" .
                                     "<a href='" . $verification_link . "'>" . $verification_link . "</a><br><br>" .
                                     "Se você não se cadastrou, por favor ignore este email.<br><br>" .
                                     "Atenciosamente,<br>Equipe Barbearia JB";
                    $mail->AltBody = "Olá " . htmlspecialchars($name) . ",\n\n" .
                                     "Obrigado por se cadastrar na Barbearia JB! Por favor, copie e cole o seguinte link no seu navegador para ativar sua conta:\n" .
                                     $verification_link . "\n\n" .
                                     "Se você não se cadastrou, por favor ignore este email.\n\n" .
                                     "Atenciosamente,\nEquipe Barbearia JB";

                    $mail->send();
                    $_SESSION['register_message'] = "Cadastro realizado com sucesso! Um email de verificação foi enviado para " . htmlspecialchars($email) . ". Por favor, verifique sua caixa de entrada (e spam).";
                    $_SESSION['register_message_type'] = "success";

                } catch (Exception $e) {
                    // Se o email falhar, o usuário está no BD, mas inativo.
                    // É uma boa ideia logar o erro $mail->ErrorInfo
                    error_log("PHPMailer Error: " . $mail->ErrorInfo);
                    $_SESSION['register_message'] = "Cadastro realizado, mas houve um erro ao enviar o email de verificação. Por favor, contate o suporte. (Para teste, o link seria: $verification_link)";
                    $_SESSION['register_message_type'] = "error"; // Ou "warning"
                }
                
                header("location: login.php"); // Ou register.php para ver a mensagem
                exit;

            } else {
                $errors[] = "Erro ao registrar usuário: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $errors[] = "Erro ao preparar o cadastro: " . $mysqli->error;
        }
    }

    if (!empty($errors)) {
        $_SESSION['register_message'] = implode("<br>", $errors);
        $_SESSION['register_message_type'] = "error";
        header("location: register.php");
        exit;
    }
} else {
    header("location: register.php");
    exit;
}
?>