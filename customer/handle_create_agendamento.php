<?php
session_start();
require_once '../config/database.php';

// Define um tempo fixo em minutos para todos os agendamentos.
// Este valor DEVE ser o mesmo usado em 'get_horarios_disponiveis.php'.
define('DURACAO_FIXA_MINUTOS', 60);

// --- PASSO 1: SEGURANÇA E VERIFICAÇÃO INICIAL ---

// Apenas aceita requisições do tipo POST, o método do nosso formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: dashboard.php");
    exit;
}

// Garante que o usuário está logado e tem a permissão de 'customer'
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: ../login.php");
    exit;
}


// --- PASSO 2: COLETA E VALIDAÇÃO DOS DADOS DO FORMULÁRIO ---

// Verifica se todos os campos essenciais foram enviados pelo formulário
if (empty($_POST['barbeiro_id']) || empty($_POST['servico_id']) || empty($_POST['data']) || empty($_POST['horario'])) {
    $_SESSION['customer_message'] = "Erro: Todos os campos são obrigatórios para o agendamento.";
    $_SESSION['customer_message_type'] = "error";
    // Devolve para a página anterior, se possível, para o usuário corrigir
    header("location: " . (isset($_POST['barbeiro_id']) ? "agendar_horario.php?barbeiro_id=".$_POST['barbeiro_id'] : "dashboard.php"));
    exit;
}

$cliente_id = $_SESSION['user_id'];
$barbeiro_id = (int)$_POST['barbeiro_id'];
$servico_id = (int)$_POST['servico_id'];
$data_selecionada = $_POST['data'];
$horario_selecionado = $_POST['horario'];

// Combina data e hora para criar um objeto DateTime, que é mais seguro e flexível
$data_hora_agendamento_str = $data_selecionada . ' ' . $horario_selecionado;
$data_hora_agendamento_obj = DateTime::createFromFormat('Y-m-d H:i', $data_hora_agendamento_str);

// Validação extra: verifica se a data/hora é válida e se não está no passado
if ($data_hora_agendamento_obj === false || $data_hora_agendamento_obj < new DateTime()) {
    $_SESSION['customer_message'] = "Erro: A data ou hora selecionada é inválida ou já passou.";
    $_SESSION['customer_message_type'] = "error";
    header("location: agendar_horario.php?barbeiro_id=".$barbeiro_id);
    exit;
}


// --- PASSO 3: BUSCAR INFORMAÇÕES ADICIONAIS ---

// Busca o preço do serviço para registrar no agendamento.
// É uma boa prática guardar o preço no momento da compra/agendamento.
$preco_servico = 0.00;
$sql_servico = "SELECT preco FROM servicos WHERE service_id = ?";
if ($stmt_serv = $mysqli->prepare($sql_servico)) {
    $stmt_serv->bind_param("i", $servico_id);
    $stmt_serv->execute();
    $result_serv = $stmt_serv->get_result();
    if ($serv = $result_serv->fetch_assoc()) {
        $preco_servico = $serv['preco'];
    } else {
        $_SESSION['customer_message'] = "Erro: O serviço selecionado não existe mais.";
        $_SESSION['customer_message_type'] = "error";
        header("location: agendar_horario.php?barbeiro_id=".$barbeiro_id);
        exit;
    }
    $stmt_serv->close();
}


// --- PASSO 4: VERIFICAÇÃO FINAL DE CONFLITO (A MAIS IMPORTANTE) ---
// Esta verificação previne que dois clientes marquem o mesmo horário ao mesmo tempo.

$inicio_novo_agendamento = clone $data_hora_agendamento_obj;
$fim_novo_agendamento = (clone $inicio_novo_agendamento)->modify("+" . DURACAO_FIXA_MINUTOS . " minutes");

$sql_conflito = "SELECT data_hora_agendamento, duracao_minutos FROM agendamentos WHERE barbeiro_id = ? AND DATE(data_hora_agendamento) = ? AND status = 'Confirmado'";
if ($stmt_conflito = $mysqli->prepare($sql_conflito)) {
    $stmt_conflito->bind_param("is", $barbeiro_id, $data_selecionada);
    $stmt_conflito->execute();
    $result_conflito = $stmt_conflito->get_result();
    
    while ($agendamento_existente = $result_conflito->fetch_assoc()) {
        $inicio_existente = new DateTime($agendamento_existente['data_hora_agendamento']);
        $fim_existente = (clone $inicio_existente)->modify("+{$agendamento_existente['duracao_minutos']} minutes");

        // A lógica de sobreposição de intervalos
        if ($inicio_novo_agendamento < $fim_existente && $fim_novo_agendamento > $inicio_existente) {
            $_SESSION['customer_message'] = "Desculpe, este horário acabou de ser preenchido por outra pessoa. Por favor, escolha outro.";
            $_SESSION['customer_message_type'] = "error";
            header("location: agendar_horario.php?barbeiro_id=".$barbeiro_id);
            exit;
        }
    }
    $stmt_conflito->close();
}


// --- PASSO 5: INSERIR O AGENDAMENTO NO BANCO DE DADOS ---

// Se o código chegou até aqui, o agendamento é válido e pode ser salvo.
$sql_insert = "INSERT INTO agendamentos (cliente_id, barbeiro_id, servico_id, data_hora_agendamento, duracao_minutos, preco_servico, status) VALUES (?, ?, ?, ?, ?, ?, 'Confirmado')";

if ($stmt_insert = $mysqli->prepare($sql_insert)) {
    $duracao_para_salvar = DURACAO_FIXA_MINUTOS;
    
    $stmt_insert->bind_param("iiisid", 
        $cliente_id, 
        $barbeiro_id, 
        $servico_id, 
        $data_hora_agendamento_str, 
        $duracao_para_salvar, 
        $preco_servico
    );

    if ($stmt_insert->execute()) {
        // Sucesso! Define a mensagem e redireciona para o painel principal.
        $_SESSION['customer_message'] = "Seu horário foi agendado com sucesso para o dia " . $data_hora_agendamento_obj->format('d/m/Y \à\s H:i') . "!";
        $_SESSION['customer_message_type'] = "success";
        header("location: dashboard.php");
        exit;
    } else {
        // Falha na inserção por algum erro do banco
        $_SESSION['customer_message'] = "Ocorreu um erro inesperado ao salvar seu agendamento. Tente novamente.";
        $_SESSION['customer_message_type'] = "error";
        header("location: agendar_horario.php?barbeiro_id=".$barbeiro_id);
        exit;
    }
    // $stmt_insert->close();
}

$mysqli->close();
?>