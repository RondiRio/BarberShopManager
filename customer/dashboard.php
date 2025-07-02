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

// 3. BUSCAR LISTA DE BARBEIROS (usado no agendamento e recomendações)
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
    <style>
        /* Estilos Gerais */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #121212; color: #FFF; margin:0; line-height: 1.6; }
        .customer-header { background-color: #000; color: #FFF; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom:3px solid #f39c12;}
        .customer-header h1 { margin: 0; font-size: 1.5em; }
        .customer-header a { color: #FFF; text-decoration: none; }
        .customer-container { max-width: 900px; margin: 30px auto; padding: 20px; }
        h2 { color: #f39c12; border-bottom: 1px solid #444; padding-bottom: 10px; margin-top: 0;}
        .section { margin-bottom: 40px; background-color: #1E1E1E; padding: 25px; border-radius: 8px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group select, .form-group textarea, .form-group input { width: 100%; padding: 12px; background-color: #333; border: 1px solid #555; color: #FFF; border-radius: 4px; font-size: 1em; box-sizing: border-box; }
        .form-group button { width: auto; cursor: pointer; background-color: #f39c12; color: #000; font-weight: bold; padding: 10px 20px; border: none; border-radius: 5px; }

        /* Estilo específico para o textarea de recomendação */
        #texto_recomendacao { width: 80%; height: 150px; resize: vertical; }

        /* Agendar Horário */
        .barber-selection-container { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 15px; }
        .barber-card { background-color: #282828; border-radius: 8px; text-align: center; padding: 20px; width: calc(33.333% - 27px); box-sizing: border-box; transition: transform 0.2s; }
        .barber-card:hover { transform: translateY(-5px); }
        .barber-card img { width: 120px; height: 120px; border-radius: 50%; border: 3px solid #f39c12; object-fit: cover; margin-bottom: 15px; }
        .barber-card h3 { margin: 0 0 15px 0; font-size: 1.2em; }
        .barber-card .button { display: inline-block; background-color: #f39c12; color: #000; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; }

        /* Meus Agendamentos */
        .appointments-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .appointments-table th, .appointments-table td { padding: 15px; text-align: left; border-bottom: 1px solid #333; }
        .appointments-table th { color: #f39c12; text-transform: uppercase; font-size: 0.8em; }
        .appointments-table td { color: #ddd; vertical-align: middle; }
        .action-link { text-decoration: none; font-weight: bold; font-size: 0.9em; padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; }
        .edit-button { color: #FFF; background-color: #3498db; }
        .cancel-button { color: #FFF; background-color: #c0392b; margin-left: 10px; }

        /* Mensagens de Feedback */
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px 5px 0 0; text-align: center; font-weight: bold; }
        .message.success { background-color: #27ae60; color: white; }
        .message.error { background-color: #c0392b; color: white; }
        .email-status { padding: 10px; margin-top: -20px; margin-bottom: 20px; border-radius: 0 0 5px 5px; border: 1px solid; border-top: none; font-size: 0.9em; }
        .email-status.status-ok { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .email-status.status-error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .email-status small { color: #555; }

        /* Modais */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background-color: #1E1E1E; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; position: relative; }
        .modal-close { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-content h3 { color: #f39c12; margin-top:0; }
        #horarios-disponiveis-modal { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <header class="customer-header">
        <h1>Barbearia JB - Minha Conta</h1>
        <div>
            <span>Olá, <?php echo htmlspecialchars($cliente_nome); ?>!</span>
            <a href="../logout.php" style="margin-left: 20px;">Sair</a>
        </div>
    </header>

    <div class="customer-container">
        <div id="notification-area">
             <?php
                if (isset($_SESSION['customer_message'])) {
                    $message_type = $_SESSION['customer_message_type'] ?? 'success';
                    echo '<div class="message ' . htmlspecialchars($message_type) . '">' . htmlspecialchars($_SESSION['customer_message']) . '</div>';
                    unset($_SESSION['customer_message']);
                    unset($_SESSION['customer_message_type']);

                    if (isset($_SESSION['email_status'])) {
                        $status_class = ($message_type === 'success') ? 'status-ok' : 'status-error';
                        echo '<div class="email-status ' . $status_class . '">' . $_SESSION['email_status'] . '</div>';
                        unset($_SESSION['email_status']);
                    }
                }
            ?>
        </div>
        
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

        <section class="section">
            <h2>✍️ Deixar uma Recomendação</h2>
            <p>Sua opinião é muito importante para nós e ajuda outros clientes!</p>
            <form action="handle_add_recomendacao.php" method="POST">
                <div class="form-group">
                    <label for="barbeiro_id">Sobre qual barbeiro é a sua recomendação?</label>
                    <select name="barbeiro_id" id="barbeiro_id" required>
                        <option value="">Selecione um profissional</option>
                        <?php foreach($barbeiros_lista as $barbeiro): ?>
                            <option value="<?php echo $barbeiro['user_id']; ?>"><?php echo htmlspecialchars($barbeiro['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="texto_recomendacao">Escreva seu comentário:</label>
                    <textarea name="texto_recomendacao" id="texto_recomendacao" required placeholder="Fale sobre o atendimento, o corte, a limpeza, o ambiente..."></textarea>
                </div>
                <div class="form-group">
                    <button type="submit">Enviar Recomendação</button>
                </div>
            </form>
        </section>
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

    function showNotification(message, type = 'success') {
        const area = document.getElementById('notification-area');
        const notification = document.createElement('div');
        notification.className = `message ${type}`;
        notification.textContent = message;
        area.innerHTML = ''; // Limpa notificações antigas
        area.appendChild(notification);
        area.style.display = 'block';
    }

    function abrirModal(modal) { modal.classList.add('active'); }
    function fecharModais() {
        modalEditar.classList.remove('active');
        modalCancelar.classList.remove('active');
    }

    mainContainer.addEventListener('click', function(e) {
        const target = e.target;
        if (target.matches('.edit-button')) {
            const row = target.closest('tr');
            document.getElementById('editar-agendamento-id').value = target.dataset.id;
            document.getElementById('editar-barbeiro-id').value = target.dataset.barbeiroId;
            const dataInput = document.getElementById('editar-data');
            dataInput.value = row.dataset.date;
            dataInput.dispatchEvent(new Event('change')); // Força a busca de horários
            abrirModal(modalEditar);
        }
        if (target.matches('.cancel-button')) {
            document.getElementById('cancelar-agendamento-id').value = target.dataset.id;
            abrirModal(modalCancelar);
        }
    });

    document.querySelectorAll('.modal-close').forEach(el => el.addEventListener('click', fecharModais));
    document.querySelector('.modal').addEventListener('click', (e) => { if(e.target === e.currentTarget) fecharModais() });
    document.querySelector('#modal-editar').addEventListener('click', (e) => { if(e.target === e.currentTarget) fecharModais() });


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
                    horariosContainerModal.innerHTML += `<label><input type="radio" name="horario" id="${radioId}" value="${horario}" required> <span>${horario}</span></label>`;
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

    document.getElementById('form-editar').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'editar');

        const response = await fetch('handle_appointment_actions.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            const id = formData.get('agendamento_id');
            const row = document.getElementById(`agendamento-row-${id}`);
            const horarioChecado = document.querySelector('#form-editar [name="horario"]:checked');
            if(row && horarioChecado) {
                const novaData = new Date(formData.get('data') + 'T' + horarioChecado.value);
                row.querySelector('.ag-data').textContent = novaData.toLocaleDateString('pt-BR', {timeZone: 'UTC'});
                row.querySelector('.ag-hora').textContent = novaData.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' });
                row.dataset.date = formData.get('data');
            }
            showNotification(result.message, 'success');
        } else {
            showNotification(result.message, 'error');
        }
        fecharModais();
    });

    document.getElementById('form-cancelar').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'cancelar');

        const response = await fetch('handle_appointment_actions.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            const id = formData.get('agendamento_id');
            const row = document.getElementById(`agendamento-row-${id}`);
            if(row) {
                row.style.transition = 'opacity 0.5s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 500);
            }
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