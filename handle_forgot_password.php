<?php
session_start();
require_once 'config/database.php';
require_once 'vendor/autoload.php'; // Certifique-se que este é o caminho correto

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo 'chegamos no handle_forgot_password.php'; // Debug inicial

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<br>chegamos no POST do handle_forgot_password.php";
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<br>chegamos no erro do email (inválido ou vazio)";
        $_SESSION['forgot_message'] = "Por favor, insira um email válido.";
        $_SESSION['forgot_message_type'] = "error";
        header("location: forgot_password.php");
        exit;
    }

    // Verifica se o email existe no banco E SE A CONTA ESTÁ ATIVA
    // Adapte 'users', 'name', 'is_active' se os nomes das suas colunas/tabelas forem diferentes
    $sql_check_email = "SELECT user_id, name, is_active FROM users WHERE email = ? LIMIT 1";
    echo "<br>SQL para verificar email: " . htmlspecialchars($sql_check_email);

    if ($stmt_check = $mysqli->prepare($sql_check_email)) {
        echo "<br>chegamos no prepare do sql_check_email";
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($user = $result->fetch_assoc()) { // Utilizador encontrado pelo email
            echo "<br>chegamos no fetch_assoc do user. Email encontrado: " . htmlspecialchars($email);
            echo "<br>Status is_active: " . $user['is_active'];

            if ($user['is_active'] == 1) { // Verifica se a conta está ativa
                echo "<br>Conta está ATIVA. Prosseguindo para gerar token.";
                $user_id = $user['user_id'];
                $user_name = $user['name']; // ou $user['nome']

                $reset_token = bin2hex(random_bytes(32));
                $reset_token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $sql_update_token = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
                echo "<br>SQL para atualizar token: " . htmlspecialchars($sql_update_token);

                if ($stmt_update = $mysqli->prepare($sql_update_token)) {
                    echo "<br>Prepare do update de token OK.";
                    $stmt_update->bind_param("ssi", $reset_token, $reset_token_expiry, $user_id);
                    if ($stmt_update->execute()) {
                        echo "<br>Update de token EXECUTADO com sucesso.";
                        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $reset_token;
                        echo "<br>Link de redefinição gerado: " . htmlspecialchars($reset_link);

                        // --- INÍCIO DO BLOCO DE ENVIO DE EMAIL ---
                        $mail = new PHPMailer(true);
                        try {
                            echo '<br>chegamos no try do envio de email';
                            $mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL; // Nível máximo de debug para o log
                            $mail->Debugoutput = function($str, $level) {
                                error_log("DEBUG SMTP: $str"); // Loga toda a comunicação SMTP
                            };

                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'rondi.rio@gmail.com';
                            $mail->Password   = 'idse ultj ayno gzvy'; 
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
                            $mail->CharSet    = 'UTF-8';

                            $mail->setFrom('rondi.rio@gmail.com', 'Barbearia JB');
                            $mail->addAddress($email, $user_name);

                            $mail->isHTML(true);
                            $mail->Subject = 'Redefinicao de Senha - Barbearia JB';
                            $mail->Body    = "Ola " . htmlspecialchars($user_name) . ",<br><br>" .
                                             "Recebemos uma solicitacao para redefinir sua senha. Se foi voce, clique no link abaixo:<br>" .
                                             "<a href='" . $reset_link . "'>" . $reset_link . "</a><br><br>" .
                                             "Este link e valido por 1 hora. Se voce nao solicitou esta alteracao, pode ignorar este email.<br><br>" .
                                             "Atenciosamente,<br>Equipe Barbearia JB";
                            $mail->AltBody = "Ola " . htmlspecialchars($user_name) . ",\n\n" .
                                             "Para redefinir sua senha, copie e cole o seguinte link no seu navegador:\n" . $reset_link . "\n\n" .
                                             "Este link e valido por 1 hora.\n\n" .
                                             "Atenciosamente,\nEquipe Barbearia JB";
                            
                            echo "<br>Tentando enviar o email...";
                            $mail->send();
                            echo "<br>Email enviado (ou tentativa feita).";
                            $_SESSION['forgot_message'] = "Se existir uma conta associada a " . htmlspecialchars($email) . " e ela estiver ativa, um link para redefinir a senha foi enviado.";
                            $_SESSION['forgot_message_type'] = "success";

                        } catch (Exception $e) {
                            echo "<br>ERRO no PHPMailer: " . $mail->ErrorInfo;
                            error_log("PHPMailer Error (Forgot Pass) para " . $email . ": " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
                            $_SESSION['forgot_message'] = "Nao foi possivel enviar o email de redefinicao no momento. Por favor, tente novamente mais tarde ou contate o suporte.";
                            $_SESSION['forgot_message_type'] = "error";
                        }
                        // --- FIM DO BLOCO DE ENVIO DE EMAIL ---
                    } else {
                        echo "<br>ERRO ao executar update de token: " . $stmt_update->error;
                        $_SESSION['forgot_message'] = "Erro ao tentar salvar as informacoes para redefinicao de senha. Tente novamente.";
                        $_SESSION['forgot_message_type'] = "error";
                        error_log("Erro ao executar update de token para user_id " . $user_id . ": " . $stmt_update->error);
                    }
                    $stmt_update->close();
                } else {
                    echo "<br>ERRO ao preparar update de token: " . $mysqli->error;
                    $_SESSION['forgot_message'] = "Erro ao preparar a operacao de redefinicao de senha. Tente novamente.";
                    $_SESSION['forgot_message_type'] = "error";
                    error_log("Erro ao preparar update de token para user_id " . $user_id . ": " . $mysqli->error);
                }
            } else {
                // Utilizador encontrado, mas não está ativo
                echo '<br>chegamos no else do is_active (conta INATIVA)';
                error_log("Tentativa de recuperação de senha para conta INATIVA: " . $email);
                $_SESSION['forgot_message'] = "Se existir uma conta associada a " . htmlspecialchars($email) . " e ela estiver ativa, um link para redefinir a senha foi enviado.";
                $_SESSION['forgot_message_type'] = "success"; // Mensagem genérica por segurança
            }
        } else {
            // Email não encontrado no banco de dados
            echo '<br>chegamos no else do user (email NÃO ENCONTRADO)';
            error_log("Tentativa de recuperação de senha para email NAO CADASTRADO: " . $email);
            $_SESSION['forgot_message'] = "Se existir uma conta associada a " . htmlspecialchars($email) . " e ela estiver ativa, um link para redefinir a senha foi enviado.";
            $_SESSION['forgot_message_type'] = "success"; // Mensagem genérica por segurança
        }
        $stmt_check->close();
    } else {
        echo "<br>ERRO ao preparar select de email: " . $mysqli->error;
        $_SESSION['forgot_message'] = "Erro ao tentar verificar o email. Tente novamente.";
        $_SESSION['forgot_message_type'] = "error";
        error_log("Erro ao preparar select de email: " . $mysqli->error);
    }

    $mysqli->close();
    // Comente o header abaixo TEMPORARIAMENTE para ver os echos de debug na tela
    header("location: forgot_password.php"); 
    exit;
    // echo "<br>Fim do script. Se header estiver comentado, você verá esta mensagem.";

    // exit; // Para garantir que nada mais seja executado após os echos de debug

} else {
    // Se o acesso não for via POST, redireciona
    header("location: forgot_password.php");
    exit;
}
?>