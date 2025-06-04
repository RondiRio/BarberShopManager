<?php
// handle_register.php
session_start();
require_once 'config/database.php';

// Inclui o autoload do Composer (necessário para PHPMailer se instalado via Composer)
// Certifique-se de que o caminho para 'vendor/autoload.php' está correto
// Se handle_register.php está na raiz do projeto onde a pasta 'vendor' também está, o caminho é este:
require_once 'vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $_SESSION['form_data'] = $_POST; // Para repreencher o formulário em caso de erro
    $errors = [];

    // Validações
    if (empty($name)) { 
        $errors[] = "O nome completo é obrigatório."; 
    }
    if (empty($email)) { 
        $errors[] = "O email é obrigatório."; 
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        $errors[] = "Formato de email inválido."; 
    } else {
        // Verifica se o email já existe
        // Adapte 'users' se o nome da sua tabela for diferente (ex: 'Users')
        $sql_check_email = "SELECT user_id FROM users WHERE email = ?"; 
        if ($stmt_check = $mysqli->prepare($sql_check_email)) {
            $stmt_check->bind_param("s", $email); 
            $stmt_check->execute(); 
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) { 
                $errors[] = "Este email já está cadastrado. Tente fazer login."; 
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

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer'; // Novos usuários são clientes por padrão
        $is_active = 0;     // Começa inativo, precisa verificar o email
        $verification_token = bin2hex(random_bytes(32));

        // ATENÇÃO AQUI: Verifique o nome da coluna para o token de verificação na sua tabela 'users'.
        // Usarei 'verification_token' como padrão. Se a sua for 'verificar_token', ajuste abaixo.
        // Adapte 'users' se o nome da sua tabela for diferente.
        $sql_insert = "INSERT INTO users (name, email, password, role, is_active, verificar_token, commission_rate) VALUES (?, ?, ?, ?, ?, ?, NULL)";
        if ($stmt_insert = $mysqli->prepare($sql_insert)) {
            // O 's' final no bind_param é para verification_token (string)
            $stmt_insert->bind_param("ssssis", $name, $email, $hashed_password, $role, $is_active, $verification_token);

            if ($stmt_insert->execute()) {
                unset($_SESSION['form_data']); // Limpa os dados do formulário da sessão em caso de sucesso
                // $user_id_inserted = $stmt_insert->insert_id; // Não estamos usando, mas é bom saber que existe

                $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?token=" . $verification_token;
                
                $mail = new PHPMailer(true);
                try {
                    // Configurações do Servidor SMTP
                    // Para depuração, descomente e ajuste $mail->SMTPDebug e $mail->Debugoutput
                    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // SMTP::DEBUG_OFF em produção; SMTP::DEBUG_SERVER ou SMTP::DEBUG_LOWLEVEL para depurar
                    // $mail->Debugoutput = function($str, $level) { error_log("DEBUG SMTP Register: $str"); };

                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';  // Servidor SMTP do Gmail
                    $mail->SMTPAuth   = true;              // Habilita autenticação SMTP
                    $mail->Username   = 'rondi.rio@gmail.com'; // SEU EMAIL GMAIL
                    $mail->Password   = 'idse ultj ayno gzvy'; // SUA SENHA DE APP DO GMAIL
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Habilita criptografia TLS
                    $mail->Port       = 587;               // Porta TCP para STARTTLS
                    $mail->CharSet    = 'UTF-8';

                    // Remetente e Destinatário
                    $mail->setFrom('rondi.rio@gmail.com', 'Barbearia JB'); // Email e nome do remetente
                    $mail->addAddress($email, $name);                     // Email e nome do novo usuário

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
                    error_log("PHPMailer Error (Register) para " . $email . ": " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
                    $_SESSION['register_message'] = "Cadastro realizado, mas houve um erro ao enviar o email de verificação. Por favor, contate o suporte para ativar sua conta.";
                    // Para depuração, você pode adicionar o link à mensagem:
                    // $_SESSION['register_message'] .= " (Link de teste: $verification_link)";
                    $_SESSION['register_message_type'] = "error";
                }
                
                // Redireciona para login.php (ou register.php para ver a mensagem de sucesso/erro)
                header("location: login.php"); 
                exit;

            } else {
                $errors[] = "Erro ao registrar usuário: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
            $errors[] = "Erro ao preparar o cadastro: " . $mysqli->error;
        }
    }

    // Se houver erros de validação ou de inserção (antes do envio de email)
    if (!empty($errors)) {
        $_SESSION['register_message'] = implode("<br>", $errors);
        $_SESSION['register_message_type'] = "error";
        header("location: register.php");
        exit;
    }
} else {
    // Se não for POST, redireciona para a página de registro
    header("location: register.php");
    exit;
}
?>