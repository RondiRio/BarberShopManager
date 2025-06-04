<?php
session_start();
require_once 'config/database.php'; // Garanta que o caminho está correto

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter dados do formulário
    $nome_cliente = isset($_POST['nome_cliente']) ? trim($_POST['nome_cliente']) : '';
    $texto_recomendacao = isset($_POST['texto_recomendacao']) ? trim($_POST['texto_recomendacao']) : '';
    // Opcional: Se você adicionar um select de barbeiro no formulário público
    $barbeiro_id = isset($_POST['barbeiro_id']) && !empty($_POST['barbeiro_id']) ? (int)$_POST['barbeiro_id'] : null;

    $errors = [];

    // Validações básicas
    if (empty($nome_cliente)) {
        $errors[] = "O seu nome é obrigatório.";
    } elseif (strlen($nome_cliente) > 255) {
        $errors[] = "O nome não pode exceder 255 caracteres.";
    }

    if (empty($texto_recomendacao)) {
        $errors[] = "O texto da recomendação é obrigatório.";
    } elseif (strlen($texto_recomendacao) < 10) {
        $errors[] = "Sua recomendação parece muito curta. Por favor, elabore um pouco mais.";
    }

    if ($barbeiro_id !== null && !filter_var($barbeiro_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Seleção de barbeiro inválida.";
        // Poderia também verificar se o barbeiro_id existe na tabela Users com role='barber'
    }


    if (empty($errors)) {
        // Inserir no banco de dados com status pendente (aprovado = 0)
        // cliente_id será NULL pois é uma submissão pública
        $sql = "INSERT INTO recomendacoes (cliente_id, nome_cliente, barbeiro_id, texto_recomendacao, aprovado) VALUES (NULL, ?, ?, ?, 0)";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("sis", $nome_cliente, $barbeiro_id, $texto_recomendacao); // s para nome_cliente, i para barbeiro_id (se for int), s para texto

            if ($stmt->execute()) {
                $_SESSION['public_rec_message'] = "Obrigado, ".htmlspecialchars($nome_cliente)."! Sua recomendação foi enviada com sucesso e será analisada em breve.";
                $_SESSION['public_rec_message_type'] = "success";
            } else {
                $_SESSION['public_rec_message'] = "Desculpe, ocorreu um erro ao enviar sua recomendação. Tente novamente mais tarde. Erro: " . $stmt->error;
                $_SESSION['public_rec_message_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['public_rec_message'] = "Desculpe, ocorreu um erro técnico ao processar sua recomendação. Erro: " . $mysqli->error;
            $_SESSION['public_rec_message_type'] = "error";
        }
    } else {
        // Se houver erros de validação
        $_SESSION['public_rec_message'] = implode("<br>", $errors);
        $_SESSION['public_rec_message_type'] = "error";
        // Opcional: Salvar os dados do formulário na sessão para repreencher
        // $_SESSION['form_data_public_rec'] = $_POST;
    }

    $mysqli->close();
    header("Location: recomendacoes.php"); // Redireciona de volta para a página de recomendações
    exit;

} else {
    // Se o acesso não for via POST, redireciona
    header("Location: recomendacoes.php");
    exit;
}
?>