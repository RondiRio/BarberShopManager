# Style Manager (anteriormente BarberShop Manager)



**A plataforma definitiva para gerenciamento de negócios de beleza e estética. Do agendamento à gestão financeira, uma solução completa, personalizável e escalável para salões, barbearias, estúdios de manicure e muito mais.**

[![Status da Build](https://img.shields.io/badge/build-passing-brightgreen)](https://github.com/RondiRio/BarberShopManager)
[![Licença](https://img.shields.io/badge/license-MIT-blue)](https://github.com/RondiRio/BarberShopManager/blob/main/LICENSE)
[![Versão](https://img.shields.io/badge/version-2.0.0--beta-orange)](https://github.com/RondiRio/BarberShopManager)

---

## 1. Visão Geral do Projeto

O **Style Manager** é a evolução do BarberShop Manager. Ele nasce com a visão de ser uma plataforma de software como serviço (SaaS) robusta, flexível e elegante, projetada para atender às necessidades administrativas de todo o ecossistema de beleza e bem-estar.

Nossa missão é empoderar donos de estabelecimentos — sejam eles barbearias, salões de beleza femininos, estúdios de manicure/pedicure ou clínicas de estética — com uma ferramenta que não só simplifica a gestão do dia a dia, mas também fortalece sua marca através de uma experiência digital personalizável para seus clientes e equipe.

Este repositório contém o código-fonte do projeto legado (BarberShop Manager em PHP) e servirá como base para a transição para a nova arquitetura de sistema.

## 2. Funcionalidades Principais

O Style Manager manterá todas as funcionalidades consagradas e adicionará novas capacidades para criar uma solução completa:

### Módulos Atuais (a serem migrados)
* **Gestão de Agendamentos:**
    * Agenda online para clientes.
    * Visualização de horários por profissional.
    * Confirmação e cancelamento de agendamentos.
    * Envio de lembretes automáticos por e-mail/WhatsApp.
* **Controle Financeiro (Fluxo de Caixa):**
    * Registro de entradas (serviços, vendas) e saídas (vales, despesas).
    * Cálculo de comissões para profissionais.
    * Relatórios financeiros detalhados (diário, mensal).
* **Gestão de Clientes (CRM):**
    * Cadastro de clientes.
    * Histórico de serviços e compras de cada cliente.
* **Gestão de Profissionais:**
    * Cadastro de barbeiros/profissionais.
    * Controle de horários de trabalho e folgas.
* **Controle de Estoque:**
    * Cadastro de produtos (para venda e para uso interno).
    * Abate automático de estoque na venda.
    * Alertas de estoque baixo.
* **Catálogo de Serviços:**
    * Cadastro de serviços com descrição, duração e preço.

### Novas Funcionalidades (Roadmap "Style Manager")
* **Painel de Personalização White-Label:**
    * **Identidade Visual:** O dono do salão poderá alterar as cores primárias e secundárias de todo o sistema.
    * **Branding:** Upload do logotipo do estabelecimento.
    * **Textos Customizáveis:** Edição de textos de boas-vindas, títulos e notificações.
* **Arquitetura Multi-Tenant:**
    * Isolamento total dos dados de cada salão cliente.
    * Sistema de assinaturas e gerenciamento de planos.
* **Dashboard de Análise (Business Intelligence):**
    * Gráficos interativos sobre faturamento, serviços mais populares, retenção de clientes e performance dos profissionais.
* **Integrações:**
    * API para integração com outras ferramentas.
    * Integração com gateways de pagamento para cobrança online.

---

## 3. Arquitetura do Sistema

O projeto está passando por uma modernização arquitetônica crucial para suportar a visão SaaS.

### 3.1. Arquitetura Legada (BarberShop Manager v1)

A versão inicial foi desenvolvida como uma aplicação web monolítica.

* **Linguagem:** PHP procedural e orientado a objetos.
* **Banco de Dados:** MySQL.
* **Frontend:** HTML, CSS e JavaScript (jQuery) renderizados diretamente pelo PHP no servidor.
* **Estrutura:** Acoplamento forte entre a lógica de negócios, o acesso a dados e a interface do usuário.

```
+------------------------------------------+
|       Navegador do Usuário (Cliente)     |
+------------------------------------------+
                  ^
                  | HTTP Requests
                  v
+------------------------------------------+
|          Servidor Web (Apache)           |
|                                          |
|  +-------------------------------------+ |
|  |           Código PHP                | |
|  |                                     | |
|  |  - Lógica de Negócios               | |
|  |  - Geração de HTML (UI)             | |
|  |  - Acesso ao Banco de Dados (SQL)   | |
|  +-------------------------------------+ |
|                  ^                       |
|                  | DB Queries            |
|                  v                       |
|  +-------------------------------------+ |
|  |        Banco de Dados MySQL         | |
|  +-------------------------------------+ |
+------------------------------------------+
```

### 3.2. Arquitetura Proposta (Style Manager v2)

A nova arquitetura será baseada em uma **API RESTful desacoplada de um frontend moderno (SPA - Single Page Application)**. Esta abordagem é escalável, mais fácil de manter e permite o desenvolvimento de múltiplos clientes (web, mobile) consumindo o mesmo backend.

* **Backend (API):**
    * **Framework:** ASP.NET Core 8 Web API (usando C#).
    * **Princípios:** API RESTful, Injeção de Dependência, Padrão de Repositório, Clean Architecture.
    * **Autenticação:** JWT (JSON Web Tokens).
* **Frontend (Web App):**
    * **Framework:** Blazor WebAssembly (usando C#).
    * **UI Components:** MudBlazor ou Radzen para uma UI rica e profissional.
    * **Estado:** Gerenciamento de estado para uma experiência de usuário fluida.
* **Banco de Dados:**
    * **SGBD:** PostgreSQL ou SQL Server.
    * **ORM:** Entity Framework Core para mapeamento objeto-relacional.
* **Hospedagem:**
    * **Provedor:** Azure ou AWS.
    * **Serviços:** App Service para a API e Frontend, Azure Blob Storage / AWS S3 para armazenamento de logos e imagens.

```
+------------------+     +------------------+
|  Frontend (Web)  |     | Frontend (Mobile)|  <-- Múltiplos clientes
|      Blazor      |     |       (futuro)   |
+------------------+     +------------------+
         ^                        ^
         |      API Calls (JSON)  |
         v                        v
+------------------------------------------+
|       Backend: ASP.NET Core Web API      |
|                                          |
|  +------------------+  +---------------+ |
|  |   Autenticação   |  |   Lógica de   | |
|  |       (JWT)      |  |   Negócios    | |
|  +------------------+  +---------------+ |
|                                          |
+------------------------------------------+
         ^                        ^
         |                        |
         v                        v
+------------------+     +------------------+
| Banco de Dados   |     |  Armazenamento   |  <-- Persistência
|  (PostgreSQL /   |     |   de Arquivos    |
|   SQL Server)    |     |   (Azure Blob)   |
+------------------+     +------------------+
```

---

## 4. Esquema do Banco de Dados (Proposta v2)

Este é o esquema relacional proposto para a nova arquitetura, projetado para suportar a estrutura multi-tenant e as novas funcionalidades.

* A chave `TenantId` em tabelas principais garante o isolamento de dados entre os diferentes salões.

```sql
-- Tabela para gerenciar os "inquilinos" (cada salão é um tenant)
CREATE TABLE Tenants (
    TenantId INT PRIMARY KEY IDENTITY,
    NomeDoSalao VARCHAR(255) NOT NULL,
    Subdominio VARCHAR(100) UNIQUE NOT NULL, -- ex: 'meusalao'.stylemanager.com
    DataCriacao DATETIME DEFAULT GETDATE()
);

-- Tabela de configuração e personalização para cada salão
CREATE TABLE ConfiguracoesTenant (
    ConfigId INT PRIMARY KEY IDENTITY,
    TenantId INT FOREIGN KEY REFERENCES Tenants(TenantId),
    CorPrimaria VARCHAR(7) DEFAULT '#1976D2',
    CorSecundaria VARCHAR(7) DEFAULT '#FF4081',
    LogoUrl NVARCHAR(512),
    TextoBoasVindas NVARCHAR(255)
);

-- Tabela de Usuários (donos, profissionais, recepcionistas)
CREATE TABLE Usuarios (
    UsuarioId INT PRIMARY KEY IDENTITY,
    TenantId INT FOREIGN KEY REFERENCES Tenants(TenantId),
    Nome VARCHAR(255) NOT NULL,
    Email VARCHAR(255) UNIQUE NOT NULL,
    SenhaHash VARCHAR(255) NOT NULL,
    TipoUsuario VARCHAR(50) NOT NULL, -- 'Admin', 'Profissional', 'Cliente'
    Ativo BIT DEFAULT 1
);

-- Tabela de Clientes do salão
CREATE TABLE Clientes (
    ClienteId INT PRIMARY KEY IDENTITY,
    TenantId INT FOREIGN KEY REFERENCES Tenants(TenantId),
    Nome VARCHAR(255) NOT NULL,
    Telefone VARCHAR(20),
    Email VARCHAR(255),
    DataCadastro DATETIME DEFAULT GETDATE()
);

-- Tabela de Serviços oferecidos pelo salão
CREATE TABLE Servicos (
    ServicoId INT PRIMARY KEY IDENTITY,
    TenantId INT FOREIGN KEY REFERENCES Tenants(TenantId),
    Nome VARCHAR(255) NOT NULL,
    Descricao TEXT,
    DuracaoMinutos INT NOT NULL,
    Preco DECIMAL(10, 2) NOT NULL,
    Ativo BIT DEFAULT 1
);

-- Tabela de Agendamentos
CREATE TABLE Agendamentos (
    AgendamentoId INT PRIMARY KEY IDENTITY,
    TenantId INT FOREIGN KEY REFERENCES Tenants(TenantId),
    ClienteId INT FOREIGN KEY REFERENCES Clientes(ClienteId),
    ProfissionalId INT FOREIGN KEY REFERENCES Usuarios(UsuarioId),
    ServicoId INT FOREIGN KEY REFERENCES Servicos(ServicoId),
    DataHoraInicio DATETIME NOT NULL,
    DataHoraFim DATETIME NOT NULL,
    Status VARCHAR(50) NOT NULL, -- 'Agendado', 'Confirmado', 'Cancelado', 'Concluido'
    Observacoes TEXT
);

-- Tabela de Produtos (para venda e controle de estoque)
CREATE TABLE Produtos (
    ProdutoId INT PRIMARY KEY IDENTITY,
    TenantId INT FOREIGN KEY REFERENCES Tenants(TenantId),
    Nome VARCHAR(255) NOT NULL,
    Descricao TEXT,
    PrecoVenda DECIMAL(10, 2) NOT NULL,
    QuantidadeEstoque INT NOT NULL,
    Ativo BIT DEFAULT 1
);

-- Tabela para registrar transações financeiras (vendas de serviços e produtos)
CREATE TABLE Transacoes (
    TransacaoId INT PRIMARY KEY IDENTITY,
    TenantId INT FOREIGN KEY REFERENCES Tenants(TenantId),
    AgendamentoId INT FOREIGN KEY REFERENCES Agendamentos(AgendamentoId) NULL, -- Venda de serviço
    DataTransacao DATETIME DEFAULT GETDATE(),
    ValorTotal DECIMAL(10, 2) NOT NULL,
    MetodoPagamento VARCHAR(50) -- 'Cartão de Crédito', 'Dinheiro', 'Pix'
);

-- Tabela de itens de uma transação (ex: 1 serviço + 2 produtos na mesma venda)
CREATE TABLE ItensTransacao (
    ItemTransacaoId INT PRIMARY KEY IDENTITY,
    TransacaoId INT FOREIGN KEY REFERENCES Transacoes(TransacaoId),
    ProdutoId INT FOREIGN KEY REFERENCES Produtos(ProdutoId) NULL,
    ServicoId INT FOREIGN KEY REFERENCES Servicos(ServicoId) NULL,
    Quantidade INT NOT NULL,
    PrecoUnitario DECIMAL(10, 2) NOT NULL
);
```

---

## 5. Como Começar (Projeto Legado)

Para executar a versão atual (PHP) do BarberShop Manager em um ambiente de desenvolvimento:

**Pré-requisitos:**
* Um ambiente de servidor web local (XAMPP, WAMP, ou MAMP).
* PHP 7.4 ou superior.
* MySQL ou MariaDB.

**Instalação:**
1.  Clone o repositório:
    ```bash
    git clone [https://github.com/RondiRio/BarberShopManager.git](https://github.com/RondiRio/BarberShopManager.git)
    ```
2.  Mova os arquivos do projeto para o diretório raiz do seu servidor web (ex: `htdocs` no XAMPP).
3.  Crie um novo banco de dados no seu servidor MySQL (ex: `barbershop_db`).
4.  Importe o arquivo de schema SQL do banco de dados (se disponível) ou crie as tabelas manualmente com base nos arquivos PHP.
5.  Configure a conexão com o banco de dados no arquivo `config/database.php`, atualizando as credenciais:
    ```php
    $host = 'localhost';
    $dbname = 'barbershop_db';
    $user = 'root';
    $pass = ''; // Sua senha
    ```
6.  Abra seu navegador e acesse `http://localhost/login.php` para iniciar a aplicação.

---

## 6. Roadmap de Desenvolvimento (v2)

Esta é a sequência de desenvolvimento planejada para a migração e evolução do sistema.

* **Fase 1: Fundação do Backend (Q4 2025)**
    * [ ] Estruturar o novo projeto ASP.NET Core Web API.
    * [ ] Implementar o esquema do banco de dados (Code-First com EF Core).
    * [ ] Desenvolver o sistema de autenticação (JWT) e autorização (Roles).
    * [ ] Criar os endpoints CRUD para a entidade `Usuarios`.

* **Fase 2: Fundação do Frontend e Funcionalidade Core (Q1 2026)**
    * [ ] Estruturar o novo projeto Blazor WebAssembly.
    * [ ] Configurar a biblioteca de componentes (MudBlazor).
    * [ ] Implementar o fluxo de Login/Logout, consumindo a API.
    * [ ] Recriar a primeira funcionalidade de ponta a ponta: **Gerenciamento de Serviços**.

* **Fase 3: Migração de Módulos (Q2 2026)**
    * [ ] Migrar o **Gerenciamento de Clientes**.
    * [ ] Migrar o **Gerenciamento de Profissionais**.
    * [ ] Recriar o sistema de **Agendamentos** com uma UI moderna.

* **Fase 4: Funcionalidades "Style Manager" (Q3 2026)**
    * [ ] Desenvolver o painel de administração para personalização (cores, logo, textos).
    * [ ] Implementar a lógica de multi-tenancy na API e no banco de dados.
    * [ ] Desenvolver a lógica de upload e armazenamento seguro de arquivos.

* **Fase 5: Lançamento e Iteração (Q4 2026)**
    * [ ] Deploy da primeira versão beta em um ambiente de nuvem.
    * [ ] Onboarding dos primeiros clientes-piloto.
    * [ ] Coletar feedback e iniciar o ciclo de melhoria contínua.

## 7. Como Contribuir

Agradecemos o interesse em contribuir para o Style Manager! No momento, estamos reestruturando o projeto. Em breve, abriremos o processo de contribuição com mais detalhes. Fique atento às *Issues* e *Projects* do GitHub.

## 8. Licença

Este projeto está licenciado sob a **Licença MIT**. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---
**Style Manager** - Simplificando a gestão, elevando a sua marca.
