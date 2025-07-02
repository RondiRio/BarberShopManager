<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '\../../vendor/autoload.php';
require_once __DIR__ . '\../email_config.php';

function generateEmailHTML($title, $message, $details = []) {
    $details_html = '';
    if (!empty($details)) {
        foreach ($details as $key => $value) {
            $details_html .= '<p style="margin: 5px 0; font-size: 16px;"><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</p>';
        }
    }

    return '
    <!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family: Arial, sans-serif; background-color: #121212; color: #FFF; padding: 20px; text-align: center;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td align="center">
            <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #1E1E1E; border-radius: 8px; padding: 30px; border: 1px solid #444;">
                <tr><td align="center">
                    <h1 style="color: #f39c12; margin: 0;">Barbearia JB</h1>
                </td></tr>
                <tr><td align="left" style="padding: 20px 0;">
                    <h2 style="color: #FFF;">' . $title . '</h2>
                    <p style="color: #DDD; font-size: 16px; line-height: 1.6;">' . $message . '</p>
                    <div style="background-color: #282828; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: left;">' . $details_html . '</div>
                </td></tr>
                <tr><td align="center" style="padding-top: 20px;">
                    <p style="font-size: 12px; color: #888;">Este é um e-mail automático, por favor, não responda.</p>
                </td></tr>
            </table>
        </td></tr></table>
    </body></html>';
}

function sendAppointmentEmail($to_email, $to_name, $subject, $html_body) {
    $mail = new PHPMailer(true);
    try {
        //Configurações do Servidor
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        //Remetente e Destinatário
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        //Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = 'Para ver esta mensagem, por favor, use um cliente de e-mail compatível com HTML.';

        $mail->send();
        
        // ALTERADO: Retorna um array de sucesso
        return ['success' => true, 'message' => 'E-mail enviado com sucesso.'];
    } catch (Exception $e) {
        // ALTERADO: Retorna um array de erro com a mensagem detalhada do PHPMailer
        // A mensagem $mail->ErrorInfo é segura para ser exibida para o desenvolvedor/admin.
        return ['success' => false, 'message' => 'O e-mail não pôde ser enviado. Erro: ' . $mail->ErrorInfo];
    
    }
}