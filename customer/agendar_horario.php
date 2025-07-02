<?php
session_start();
require_once '../config/database.php';

// --- SEGURANÇA E VALIDAÇÃO ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: ../login.php");
    exit;
}

// Valida o ID do barbeiro recebido pela URL
if (!isset($_GET['barbeiro_id']) || !is_numeric($_GET['barbeiro_id'])) {
    // Redireciona com erro se o ID for inválido
    $_SESSION['customer_message'] = "Barbeiro inválido selecionado.";
    $_SESSION['customer_message_type'] = "error";
    header("location: dashboard.php");
    exit;
}
$barbeiro_id = (int)$_GET['barbeiro_id'];

// --- BUSCAR DADOS ---
// Busca o nome do barbeiro para exibir na página
$barbeiro_nome = "";
$sql_barbeiro = "SELECT name FROM users WHERE user_id = ? AND role = 'barber' AND is_active = 1";
if ($stmt_barb = $mysqli->prepare($sql_barbeiro)) {
    $stmt_barb->bind_param("i", $barbeiro_id);
    $stmt_barb->execute();
    $result_barb = $stmt_barb->get_result();
    if ($barb = $result_barb->fetch_assoc()) {
        $barbeiro_nome = $barb['name'];
    } else {
        // Se o barbeiro não for encontrado ou estiver inativo
        $_SESSION['customer_message'] = "Barbeiro não encontrado ou inativo.";
        $_SESSION['customer_message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }
    $stmt_barb->close();
}

// Busca a lista de serviços ativos
$servicos_lista = [];
$sql_servicos = "SELECT service_id, nome, preco FROM servicos WHERE ativo = 1 ORDER BY nome ASC";
if ($result_serv = $mysqli->query($sql_servicos)) {
    while ($serv = $result_serv->fetch_assoc()) {
        $servicos_lista[] = $serv;
    }
    $result_serv->free();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Horário - Barbearia JB</title>
    <link rel="stylesheet" href="path/to/your/customer_style.css"> 
    <style>
        /* Estilos da página anterior + novos estilos */
        body { font-family: Arial, sans-serif; background-color: #121212; color: #FFF; padding: 0; margin:0; line-height: 1.6; }
        .customer-header { background-color: #000; color: #FFF; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom:3px solid #f39c12;}
        .customer-header h1 { margin: 0; font-size: 1.5em; }
        .customer-header a { color: #FFF; text-decoration: none; }
        .customer-container { max-width: 700px; margin: 30px auto; padding: 20px; background-color: #1E1E1E; border-radius: 8px; }
        h2 { color: #f39c12; border-bottom: 1px solid #444; padding-bottom: 10px; margin-top: 0;}
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input[type="date"] {
            width: 100%; padding: 10px; background-color: #333; border: 1px solid #555;
            color: #FFF; border-radius: 4px; font-size: 1em;
        }
        button[type="submit"] { background-color: #f39c12; color: #000; border: none; cursor: pointer; font-weight: bold; padding: 12px 25px; border-radius: 5px; font-size: 1.1em; }
        button[type="submit"]:disabled { background-color: #555; cursor: not-allowed; }
        
        /* Estilos para os horários disponíveis */
        #horarios-disponiveis { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .horario-label { display: block; }
        .horario-label input[type="radio"] { display: none; } /* Esconde o botão de rádio original */
        .horario-label span {
            display: block;
            background-color: #333;
            color: #fff;
            padding: 10px 15px;
            border: 1px solid #555;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .horario-label input[type="radio"]:checked + span {
            background-color: #f39c12;
            border-color: #e67e22;
            color: #000;
            font-weight: bold;
        }
        .loading, .no-slots { color: #888; text-align: center; padding: 20px; width: 100%; }
    </style>
</head>
<body>
    <header class="customer-header">
        <h1>Agendamento</h1>
        <a href="dashboard.php">Voltar para Minha Conta</a>
    </header>

    <div class="customer-container">
        <h2>Agendar com <?php echo htmlspecialchars($barbeiro_nome); ?></h2>
        
        <form id="agendamento-form" action="handle_create_agendamento.php" method="POST">
            <input type="hidden" name="barbeiro_id" value="<?php echo $barbeiro_id; ?>">

            <div class="form-group">
                <label for="servico_id">1. Escolha o serviço:</label>
                <select name="servico_id" id="servico_id" required>
                    <option value="">Selecione um serviço...</option>
                    <?php foreach ($servicos_lista as $servico): ?>
                        <option value="<?php echo $servico['service_id']; ?>" >
                            <?php echo htmlspecialchars($servico['nome']) . " (R$ " . number_format($servico['preco'], 2, ',', '.') . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data">2. Escolha a data:</label>
                <input type="date" id="data" name="data" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>3. Escolha o horário:</label>
                <div id="horarios-disponiveis">
                    <p class="no-slots">Selecione um serviço e uma data para ver os horários.</p>
                </div>
            </div>

            <button type="submit" id="submit-button" disabled>Confirmar Agendamento</button>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const servicoSelect = document.getElementById('servico_id');
    const dataInput = document.getElementById('data');
    const horariosContainer = document.getElementById('horarios-disponiveis');
    const submitButton = document.getElementById('submit-button');
    const barbeiroId = <?php echo $barbeiro_id; ?>;

    // Função para buscar os horários
    async function fetchHorarios() {
        const servicoId = servicoSelect.value;
        const dataSelecionada = dataInput.value;
        const duracao = servicoSelect.options[servicoSelect.selectedIndex]?.dataset.duracao;

        // Só busca se tiver serviço e data selecionados
        if (!servicoId || !dataSelecionada) {
            horariosContainer.innerHTML = '<p class="no-slots">Selecione um serviço e uma data para ver os horários.</p>';
            submitButton.disabled = true;
            return;
        }
        
        horariosContainer.innerHTML = '<p class="loading">Buscando horários...</p>';
        submitButton.disabled = true;

        try {
            const response = await fetch(`get_horarios_disponiveis.php?barbeiro_id=${barbeiroId}&data=${dataSelecionada}&duracao=${duracao}`);
            const horarios = await response.json();

            horariosContainer.innerHTML = ''; // Limpa o container

            if (horarios.error) {
                horariosContainer.innerHTML = `<p class="no-slots">${horarios.error}</p>`;
            } else if (horarios.length === 0) {
                horariosContainer.innerHTML = '<p class="no-slots">Nenhum horário disponível para esta data. Por favor, tente outra.</p>';
            } else {
                horarios.forEach(horario => {
                    const radioId = `horario-${horario.replace(':', '')}`;
                    const label = document.createElement('label');
                    label.className = 'horario-label';
                    label.innerHTML = `
                        <input type="radio" name="horario" id="${radioId}" value="${horario}" required>
                        <span>${horario}</span>
                    `;
                    horariosContainer.appendChild(label);
                });
            }
        } catch (error) {
            console.error('Erro ao buscar horários:', error);
            horariosContainer.innerHTML = '<p class="no-slots">Ocorreu um erro ao buscar os horários. Tente novamente.</p>';
        }
    }
    
    // Adiciona listener para habilitar o botão de submissão
    horariosContainer.addEventListener('change', function(event) {
        if (event.target.name === 'horario') {
            submitButton.disabled = false;
        }
    });

    // Adiciona listeners para os seletores de serviço e data
    servicoSelect.addEventListener('change', fetchHorarios);
    dataInput.addEventListener('change', fetchHorarios);
});
</script>
</body>
</html>