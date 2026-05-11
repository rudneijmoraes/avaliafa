# AvaliaFA

Sistema inteligente de avaliações acadêmicas da Faculdade Anasps.

O AvaliaFA é uma plataforma web para criação, aplicação, monitoramento e gestão de provas, simulados, relatórios e certificados digitais. Ele funciona como um sistema standalone, com interface administrativa própria e API REST para integração com sistemas acadêmicos externos, incluindo Moodle.

## Visão Geral

O sistema foi criado para centralizar o ciclo completo de avaliações acadêmicas:

- professores e coordenadores criam provas, simulados e bancos de questões;
- estudantes acessam avaliações por links seguros ou pela área do aluno;
- a instituição acompanha provas em andamento, resultados, auditoria e certificados;
- sistemas externos consomem a API do AvaliaFA com autenticação OAuth2;
- notas, certificados e eventos podem ser integrados ao Moodle e a outros sistemas.

O AvaliaFA não é um cadastro público de alunos. Os estudantes chegam ao sistema por fluxo institucional, normalmente por importação, integração, LTI, Moodle ou links seguros gerados pela plataforma.

## Para Quem Serve

### Professores

- Criar provas e simulados.
- Organizar questões por disciplina.
- Importar questões em massa.
- Acompanhar resultados.
- Consultar relatórios de desempenho.

### Coordenadores e Administração

- Gerenciar provas, estudantes, sistemas clientes e relatórios.
- Monitorar avaliações em tempo real.
- Emitir e validar certificados.
- Acompanhar indicadores institucionais.
- Controlar configurações operacionais do sistema.

### Estudantes

- Acessar provas e simulados em ambiente seguro.
- Responder avaliações com salvamento automático.
- Consultar resultados quando liberados.
- Baixar certificados digitais quando aplicável.

### Sistemas Externos

- Consumir a API REST do AvaliaFA.
- Criar estudantes e sessões de prova.
- Gerar links de acesso com token.
- Receber webhooks de eventos.
- Sincronizar notas com Moodle.

## Principais Funcionalidades

### Gestão de Provas

- Criação e edição de provas.
- Publicação, ativação, encerramento e arquivamento.
- Vínculo de questões com ordem e peso.
- Snapshot de versão da prova publicada.
- Configurações de tempo, aprovação, webcam, tela cheia e embaralhamento.

### Banco de Questões

- Cadastro de questões por disciplina.
- Suporte a múltipla escolha, verdadeiro/falso, múltiplas respostas, dissertativas e ordenação.
- Importação por arquivos estruturados.
- Controle de visibilidade por sistema, departamento ou escopo global.
- Bloqueio de edição de questões já vinculadas a provas publicadas ou ativas.

### Simulados

- Criação de simulados públicos ou vinculados a provas existentes.
- Área do aluno com simulados disponíveis e inscritos.
- Controle de exibição imediata ou posterior de resultado.
- Suporte a hubs de simulados com URL pública compartilhada.

### Aplicação de Provas

- Acesso por deep link seguro.
- Sessão individual por estudante.
- Salvamento automático de respostas.
- Modo offline com IndexedDB para preservar respostas em caso de queda de conexão.
- Captura de foto de perfil quando exigida.
- Tela final neutra, sem expor aprovação, reprovação ou certificado quando isso não for configurado.

### Monitoramento e Segurança

- Eventos de segurança durante a prova.
- Registro de saída de tela cheia, troca de aba, perda de foco, inatividade e tentativas de atalho.
- Detecção básica de webcam obstruída.
- Registro de snapshots quando configurado.
- Pontuação de risco por sessão.
- Painel de monitoramento com fotos, eventos e auditoria por sessão.

### Relatórios

- Relatórios por prova.
- Filtros por estudante, CPF, e-mail, status e simulados.
- Exportação CSV com seleção de colunas.
- Indicadores institucionais no dashboard.
- Visão consolidada para super administradores.

### Certificados

- Emissão de certificados digitais.
- Verificação pública de autenticidade.
- Revogação e reativação.
- Geração em PDF com serviços dedicados.

### Integrações

- API REST em `/api/v1/`.
- OAuth2 com Laravel Passport.
- LTI 1.3 para integração com Moodle.
- Sincronização de notas para Moodle.
- Webhooks para comunicação com sistemas externos.
- Sistemas clientes separados por `client_system_id`.

## Arquitetura

O AvaliaFA opera como uma plataforma central consumida por múltiplos sistemas:

```text
Graduação       ┐
Pós-Graduação   ├── API REST /api/v1/ ── AvaliaFA ── Moodle
Certificadora   ┘        OAuth2
```

Cada sistema consumidor possui um registro próprio em `client_systems`. Toda consulta sensível deve respeitar o `client_system_id`, garantindo isolamento entre os sistemas.

Fluxo típico de integração:

1. O sistema externo autentica usando OAuth2 Client Credentials.
2. O sistema envia ou atualiza o estudante.
3. O sistema cria uma sessão de prova.
4. O AvaliaFA gera um token/link seguro para acesso.
5. O estudante realiza a avaliação.
6. O resultado pode ser sincronizado com Moodle ou enviado por webhook.

## Stack Técnica

- Backend: Laravel 12
- Linguagem: PHP 8.2+
- Banco de dados: MySQL 8.0
- Cache, sessões e filas: Redis
- Frontend: Blade, Alpine.js e TailwindCSS
- OAuth2: Laravel Passport
- Filas: Laravel Horizon
- WebSockets: Laravel Reverb
- Certificados PDF: TCPDF e FPDI
- Containers: Docker e Docker Compose

## Filas

O projeto separa os principais domínios em filas específicas:

- `webhooks`: envio de webhooks;
- `moodle-sync`: sincronização com Moodle;
- `certificates`: emissão de certificados;
- `security`: eventos de segurança e snapshots;
- `default`: demais tarefas assíncronas.

## Como Rodar Localmente

### Com Docker

```bash
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan passport:install --uuids
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan horizon
```

### Sem Docker

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan passport:install --uuids
php artisan migrate --seed
php artisan serve
```

Em outro terminal, execute os serviços auxiliares quando necessário:

```bash
php artisan horizon
php artisan reverb:start
php artisan queue:work --queue=security,moodle-sync,webhooks,certificates,default
npm run dev
```

## Convenções do Projeto

- Código em inglês.
- Interface em português brasileiro.
- Banco de dados em MySQL.
- Rotas de API em `/api/v1/`.
- Soft deletes nas tabelas principais.
- Filtro obrigatório por `client_system_id` em recursos multi-sistema.
- Respostas da API sempre no formato padronizado com `success`, `data`, `errors` ou `message`.

## Status Atual

O projeto já possui a base funcional principal implementada: autenticação, provas, questões, estudantes, simulados, relatórios, certificados, integrações, monitoramento, auditoria, modo offline e estrutura de filas.

As prioridades atuais são ampliar a cobertura de testes, reforçar a estabilidade de filas em cenários de intermitência e evoluir o monitoramento operacional.

## Licença

Projeto privado da Faculdade Anasps. O uso, distribuição e publicação dependem de autorização da instituição.
