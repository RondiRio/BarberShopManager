<?php
session_start();
require_once '../config/database.php';

// 1. SEGURANÇA E DADOS DO USUÁRIO
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: ../login.php");
    exit;
}
$cliente_id = $_SESSION["user_id"];
$cliente_nome = $_SESSION["name"];

// 2. VERIFICAR CONFIGURAÇÕES DA BARBEARIA
$agendamento_habilitado = false;
$agendamento_online_permitido = false;
$sql_configs = "SELECT agendamento_ativo, permitir_agendamento_cliente FROM configuracoes WHERE config_id = 1";
if ($result_configs = $mysqli->query($sql_configs)) {
    if ($configs_db = $result_configs->fetch_assoc()) {
        $agendamento_habilitado = (bool)$configs_db['agendamento_ativo'];
        $agendamento_online_permitido = (bool)$configs_db['permitir_agendamento_cliente'];
    }
    $result_configs->free();
}

// 3. BUSCAR LISTA DE BARBEIROS
$barbeiros_lista = [];
$sql_barbeiros = "SELECT user_id, name, foto_perfil FROM users WHERE role = 'barber' AND is_active = 1 ORDER BY name ASC";
if ($result_barbs = $mysqli->query($sql_barbeiros)) {
    while ($barb = $result_barbs->fetch_assoc()) {
        $barbeiros_lista[] = $barb;
    }
    $result_barbs->free();
}

// 4. BUSCAR AGENDAMENTOS FUTUROS DO CLIENTE
$meus_agendamentos = [];
if ($agendamento_habilitado) {
    $sql_meus_agendamentos = "SELECT a.agendamento_id, a.data_hora_agendamento, a.barbeiro_id, u.name AS barbeiro_nome, s.nome AS servico_nome FROM agendamentos a JOIN Users u ON a.barbeiro_id = u.user_id JOIN servicos s ON a.servico_id = s.service_id WHERE a.cliente_id = ? AND a.data_hora_agendamento >= NOW() AND a.status = 'Confirmado' ORDER BY a.data_hora_agendamento ASC";
    if ($stmt_meus_agend = $mysqli->prepare($sql_meus_agendamentos)) {
        $stmt_meus_agend->bind_param("i", $cliente_id);
        $stmt_meus_agend->execute();
        $result_meus_agend = $stmt_meus_agend->get_result();
        while ($agend = $result_meus_agend->fetch_assoc()) {
            $meus_agendamentos[] = $agend;
        }
        $stmt_meus_agend->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Barbearia JB</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <header class="customer-header">
        <h1>Barbearia JB - <?php print_r($_SESSION['name'])?></h1>
        <div>
            <span>Olá, <?php echo htmlspecialchars($cliente_nome); ?>!</span>
            <a href="../logout.php" style="margin-left: 20px;">Sair</a>
        </div>
    </header>

    <div class="customer-container">
        <div id="notification-area" style="display: none;"></div>
        
        <?php if ($agendamento_habilitado && $agendamento_online_permitido): ?>
            <section class="section">
                <h2>Agendar um Horário</h2>
                <div class="barber-selection-container">
                    <?php foreach($barbeiros_lista as $barbeiro): ?>
                        <div class="barber-card">
                            <img src="../<?php echo htmlspecialchars($barbeiro['foto_perfil'] ?? 'imagens/perfis/default.png'); ?>" alt="Foto de <?php echo htmlspecialchars($barbeiro['name']); ?>">
                            <h3><?php echo htmlspecialchars($barbeiro['name']); ?></h3>
                            <a href="agendar_horario.php?barbeiro_id=<?php echo $barbeiro['user_id']; ?>" class="button">Agendar</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($agendamento_habilitado): ?>
            <section class="section">
                <h2>Meus Próximos Agendamentos</h2>
                <div id="minha-lista-de-agendamentos">
                    <?php if (!empty($meus_agendamentos)): ?>
                        <table class="appointments-table">
                            <thead>
                                <tr><th>Data</th><th>Horário</th><th>Barbeiro</th><th>Serviço</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($meus_agendamentos as $agendamento): ?>
                                    <tr id="agendamento-row-<?php echo $agendamento['agendamento_id']; ?>" data-date="<?php echo date('Y-m-d', strtotime($agendamento['data_hora_agendamento'])); ?>">
                                        <td class="ag-data"><?php echo date('d/m/Y', strtotime($agendamento['data_hora_agendamento'])); ?></td>
                                        <td class="ag-hora"><?php echo date('H:i', strtotime($agendamento['data_hora_agendamento'])); ?></td>
                                        <td><?php echo htmlspecialchars($agendamento['barbeiro_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($agendamento['servico_nome']); ?></td>
                                        <td>
                                            <button class="action-link edit-button" data-id="<?php echo $agendamento['agendamento_id']; ?>" data-barbeiro-id="<?php echo $agendamento['barbeiro_id']; ?>">Editar</button>
                                            <button class="action-link cancel-button" data-id="<?php echo $agendamento['agendamento_id']; ?>">Cancelar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Você não possui agendamentos futuros.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div id="modal-cancelar" class="modal">
        <div class="modal-content">
            <span class="modal-close" data-modal="modal-cancelar">&times;</span>
            <h3>Confirmar Cancelamento</h3>
            <p>Tem certeza que deseja cancelar este agendamento? Esta ação não pode ser desfeita.</p>
            <form id="form-cancelar">
                <input type="hidden" id="cancelar-agendamento-id" name="agendamento_id">
                <button type="submit">Sim, Cancelar</button>
            </form>
        </div>
    </div>

    <div id="modal-editar" class="modal">
        <div class="modal-content">
            <span class="modal-close" data-modal="modal-editar">&times;</span>
            <h3>Editar Agendamento</h3>
            <form id="form-editar">
                <input type="hidden" id="editar-agendamento-id" name="agendamento_id">
                <input type="hidden" id="editar-barbeiro-id" name="barbeiro_id">
                <div class="form-group">
                    <label for="editar-data">Escolha a nova data:</label>
                    <input type="date" id="editar-data" name="data" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Escolha o novo horário:</label>
                    <div id="horarios-disponiveis-modal"><p>Selecione uma data.</p></div>
                </div>
                <button type="submit" id="btn-confirmar-edicao" disabled>Salvar Alterações</button>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainContainer = document.querySelector('.customer-container');
    const modalEditar = document.getElementById('modal-editar');
    const modalCancelar = document.getElementById('modal-cancelar');

    // Função genérica para notificação
    function showNotification(message, type = 'success') {
        alert(message); // Simples, substitua por um toast mais elegante se desejar
    }

    // --- CONTROLE DOS MODAIS ---
    function abrirModal(modal) { modal.classList.add('active'); }
    function fecharModais() {
        modalEditar.classList.remove('active');
        modalCancelar.classList.remove('active');
    }

    mainContainer.addEventListener('click', function(e) {
        if (e.target.matches('.edit-button')) {
            const row = e.target.closest('tr');
            document.getElementById('editar-agendamento-id').value = e.target.dataset.id;
            document.getElementById('editar-barbeiro-id').value = e.target.dataset.barbeiroId;
            document.getElementById('editar-data').value = row.dataset.date;
            fetchHorarios();
            abrirModal(modalEditar);
        }
        if (e.target.matches('.cancel-button')) {
            document.getElementById('cancelar-agendamento-id').value = e.target.dataset.id;
            abrirModal(modalCancelar);
        }
    });

    document.querySelectorAll('.modal-close').forEach(el => el.addEventListener('click', fecharModais));
    document.querySelectorAll('.modal').forEach(el => el.addEventListener('click', (e) => {
        if (e.target === el) fecharModais();
    }));

    // --- LÓGICA DE EDIÇÃO E AJAX ---
    const dataInputEditar = document.getElementById('editar-data');
    const horariosContainerModal = document.getElementById('horarios-disponiveis-modal');
    const btnConfirmarEdicao = document.getElementById('btn-confirmar-edicao');

    async function fetchHorarios() {
        const barbeiroId = document.getElementById('editar-barbeiro-id').value;
        const data = dataInputEditar.value;
        const agendamentoId = document.getElementById('editar-agendamento-id').value;
        btnConfirmarEdicao.disabled = true;

        if (!data) return;
        horariosContainerModal.innerHTML = `<p>Buscando horários...</p>`;

        try {
            const response = await fetch(`get_horarios_disponiveis.php?barbeiro_id=${barbeiroId}&data=${data}&exclude_id=${agendamentoId}`);
            const horarios = await response.json();
            horariosContainerModal.innerHTML = '';

            if (horarios.error || horarios.length === 0) {
                horariosContainerModal.innerHTML = `<p>Nenhum horário disponível nesta data.</p>`;
            } else {
                horarios.forEach(horario => {
                    const radioId = `horario-modal-${horario.replace(':', '')}`;
                    horariosContainerModal.innerHTML += `
                        <label><input type="radio" name="horario" id="${radioId}" value="${horario}" required> <span>${horario}</span></label>
                    `;
                });
            }
        } catch (e) {
            horariosContainerModal.innerHTML = `<p>Erro ao carregar horários.</p>`;
        }
    }

    dataInputEditar.addEventListener('change', fetchHorarios);
    horariosContainerModal.addEventListener('change', (e) => {
        if (e.target.name === 'horario') btnConfirmarEdicao.disabled = false;
    });

    // Submeter Formulário de Edição
    document.getElementById('form-editar').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'editar');

        const response = await fetch('handle_appointment_actions.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            const id = formData.get('agendamento_id');
            const row = document.getElementById(`agendamento-row-${id}`);
            const novaData = new Date(formData.get('data') + 'T' + document.querySelector('[name="horario"]:checked').value);
            row.querySelector('.ag-data').textContent = novaData.toLocaleDateString('pt-BR');
            row.querySelector('.ag-hora').textContent = novaData.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            row.dataset.date = formData.get('data');
            showNotification(result.message, 'success');
        } else {
            showNotification(result.message, 'error');
        }
        fecharModais();
    });

    // Submeter Formulário de Cancelamento
    document.getElementById('form-cancelar').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'cancelar');

        const response = await fetch('handle_appointment_actions.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            const id = formData.get('agendamento_id');
            const row = document.getElementById(`agendamento-row-${id}`);
            row.style.transition = 'opacity 0.5s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 500);
            showNotification(result.message, 'success');
        } else {
            showNotification(result.message, 'error');
        }
        fecharModais();
    });
});
</script>

</body>
</html>