# Funcionalidades e Regras de Segurança do Sistema

## Visão Geral

O Sistema de Gestão de Projetos FA é uma plataforma completa para avaliação educacional desenvolvida em Laravel 11/12, PHP 8.2+, com MySQL, Blade, Alpine.js e Tailwind CSS.

---

## 🎯 Funcionalidades Principais

### 1. Gestão de Usuários
- **Autenticação**: Login/logout seguro com sessões
- **Perfis de Usuário**:
  - `super_admin`: Controle total do sistema
  - `admin`: Administração geral
  - `coordinator`: Coordenação de cursos
  - `professor`: Gestão de provas e alunos
  - `student`: Realização de provas
- **Gestão de Perfil**: Atualização de dados pessoais e senha
- **Foto de Perfil**: Captura via webcam no primeiro acesso à prova, troca pelo admin ou aluno
- **Detecção CPF/ID Moodle**: Exibição inteligente de CPF real ou ID Moodle
- **Soft Delete**: Usuários desativados mantêm histórico

### 2. Gestão de Provas
- **CRUD Completo**: Criar, ler, atualizar, excluir provas
- **Publicação**: Controle de disponibilidade das provas
- **Arquivamento**: Organização de provas antigas
- **Gestão de Questões**: Anexar/desanexar questões das provas
- **Disciplinas**: Associação a disciplinas com filtro na listagem
- **Links Seguros**: Geração de links com JWT para acesso dos alunos

### 3. Gestão de Questões
- **Importação em Massa**: CSV, XML e ZIP (QTI v2.2 / Prova Fácil)
- **Templates Disponíveis**: Modelos CSV e XML para importação
- **Tipos de Questões**: Múltipla escolha, verdadeiro/falso, múltiplas respostas
- **Banco Centralizado**: Reutilização entre provas
- **Disciplinas**: Associação a disciplinas por sistema, com criação inline

### 4. Gestão de Estudantes
- **Importação em Massa**: Template CSV disponível
- **Vínculo com Sistemas Externos**: Integração com Moodle
- **Histórico de Provas**: Controle completo de participações

### 5. Sistema de Avaliação
- **Fluxo do Aluno**: Acesso via deep link JWT
- **Monitoramento em Tempo Real**: Captura de eventos de segurança
- **Fingerprinting**: Identificação de dispositivos
- **Snapshots**: Capturas de tela durante provas
- **Barra de Progresso**: Salvamento automático

### 6. Certificados
- **Emissão Automática**: Pós-aprovação
- **Verificação Online**: Link público de validação
- **Gestão de Status**: Ativação/desativação
- **Download Seguro**: PDF protegido

### 7. Relatórios
- **Relatórios de Provas**: Desempenho geral
- **Exportação CSV**: Análise em planilhas
- **Gestão de Notas**: Ajuste manual quando necessário
- **Visualização Detalhada**: Por sessão de prova

### 8. Monitoramento e Dashboard
- **Dashboard com KPIs**: Sessões totais, em andamento, taxa de aprovação, provas cadastradas, questões cadastradas
- **Visão Institucional**: Ranking de sistemas com métricas consolidadas (super admin)
- **Monitor em Tempo Real**: Status das provas ativas com foto dos alunos
- **Eventos de Segurança**: Logs detalhados
- **Gestão de Sistemas Clientes**: Controle de acesso externo
- **Operações Administrativas**: Controle total

### 9. Integrações
- **API RESTful**: Endpoints para sistemas externos
- **LTI 1.3**: Integração nativa com Moodle (OIDC, JWT RS256, AGS, Deep Linking)
- **Webhooks**: Envio de eventos para sistemas externos
- **Sincronização Moodle**: Notas normalizadas para escala do Moodle
- **OAuth2**: Autenticação segura para APIs

### 10. Sistema de Atualização
- **Deploy via ZIP**: Upload de pacotes de atualização pela interface web
- **Backup Automático**: Backup completo (BD + código) antes de cada atualização
- **Migrations Automáticas**: Executadas automaticamente após atualização
- **Whitelist de Paths**: Apenas diretórios permitidos são sobrescritos
- **Histórico**: Últimas 10 atualizações registradas com versão e notas

### 11. Gestão de Disciplinas
- **Cadastro por Sistema**: Cada disciplina pertence a um sistema (client_system)
- **Criação Inline**: Criar disciplinas diretamente nos formulários de provas e questões
- **Detecção Automática**: Em imports ZIP do Prova Fácil, disciplina detectada do nome do arquivo
- **Idempotência**: `Discipline::findOrCreateByName()` reutiliza disciplinas existentes

---

## 🔒 Regras de Segurança

### 1. Autenticação e Autorização
- **OAuth2 com Passport**: Tokens de acesso seguros
- **Session Guard**: Sessões criptografadas
- **Role-Based Access Control**: Hierarquia de permissões
- **Password Hashing**: Senhas criptografadas com bcrypt
- **Session Timeout**: Expiração automática de sessões

### 2. Controle de Acesso à API
- **Client Credentials**: Autenticação de sistemas clientes
- **IP Allowlist**: Restrição por endereço IP
- **Bearer Tokens**: Token obrigatório para acesso
- **Active Client Check**: Verificação de status do cliente

### 3. Segurança de Provas
- **JWT Links**: Links temporários e únicos
- **Fingerprinting**: Identificação de dispositivo/browser
- **Security Events**: Captura de atividades suspeitas
- **Snapshots**: Capturas automáticas de tela
- **Time Tracking**: Controle rigoroso do tempo
- **Session Isolation**: Prevenção de sessões paralelas

### 4. Monitoramento e Auditoria
- **Audit Logs**: Registro completo de ações
- **Security Events**: Classificação de eventos por tipo
- **Risk Scoring**: Pontuação de risco por comportamento
- **Webhook Logs**: Registro de integrações externas
- **Moodle Sync Logs**: Histórico de sincronizações

### 5. Proteção de Dados
- **Soft Delete**: Preservação de dados históricos
- **Data Encryption**: Dados sensíveis criptografados
- **Input Validation**: Validação rigorosa de entradas
- **CSRF Protection**: Proteção contra ataques CSRF
- **XSS Prevention**: Escapamento automático de saídas

### 6. Controles de Integridade
- **Database Constraints**: Chaves estrangeiras e unicidade
- **Transaction Management**: Operações atômicas
- **Data Validation**: Regras de validação em múltiplos níveis
- **Error Handling**: Tratamento seguro de exceções

---

## 🔐 Eventos de Segurança Monitorados

### 1. Eventos de Sessão
- **Session Expired**: Expiração de tempo
- **Suspicious Activity**: Comportamento anômalo
- **Violation Detected**: Quebra de regras
- **Multiple Sessions**: Tentativa de sessões paralelas

### 2. Eventos de Prova
- **Exam Started**: Início da prova
- **Exam Submitted**: Entrega da prova
- **Grade Published**: Publicação de notas
- **Certificate Issued**: Emissão de certificado

### 3. Eventos de Sistema
- **Login Attempts**: Tentativas de acesso
- **Password Changes**: Alteração de senha
- **Profile Updates**: Atualizações de cadastro
- **API Access**: Acesso a endpoints protegidos

---

## 🛡️ Middleware de Segurança

### 1. AuthApiClient
- Validação de tokens OAuth2
- Verificação de client credentials
- Checagem de status do cliente
- Injeção de contexto de autenticação

### 2. ApiIpAllowlist
- Verificação de IP permitido
- Bloqueio de acessos não autorizados
- Configuração por cliente

### 3. ParallelSessionGuard
- Detecção de sessões múltiplas
- Prevenção de fraudes
- Isolamento de contexto

---

## 📊 Estrutura de Dados

### Models Principais
- **User**: Usuários do sistema (com foto de perfil)
- **Exam**: Provas/avaliações (com disciplina opcional)
- **Question**: Banco de questões (com disciplina opcional)
- **Discipline**: Disciplinas por sistema
- **ExamSession**: Sessões de prova
- **SecurityEvent**: Eventos de segurança
- **AuditLog**: Logs de auditoria
- **Certificate**: Certificados emitidos
- **ClientSystem**: Sistemas clientes integrados
- **Setting**: Configurações do sistema
- **LtiRegistration**: Registros LTI 1.3

### Relacionamentos Chave
- User → ExamSession (1:N)
- Exam → ExamQuestion (N:M)
- ExamSession → SecurityEvent (1:N)
- User → Certificate (1:N)
- ClientSystem → User (1:N)

---

## 🔧 Configurações de Segurança

### Configurações de Autenticação
- **Password Timeout**: 3 horas (configurável)
- **Password Reset**: 60 minutos de validade
- **Session Driver**: Base de dados
- **API Driver**: Passport OAuth2

### Configurações de API
- **Token Expiry**: Configurável por cliente
- **Rate Limiting**: Prevenção de abuso
- **CORS**: Controle de origens permitidas
- **Request Validation**: Validação obrigatória

---

## 📋 Fluxos de Trabalho Seguros

### 1. Fluxo de Prova do Aluno
1. Recebe link JWT seguro
2. Valida token e fingerprint
3. Inicia sessão monitorada
4. Responde com controle de tempo
5. Eventos de segurança capturados
6. Submissão e geração de resultado

### 2. Fluxo de Integração API
1. Autenticação OAuth2
2. Validação de IP e cliente
3. Execução de operações permitidas
4. Registro de auditoria
5. Retorno estruturado

### 3. Fluxo de Certificação
1. Verificação de aprovação
2. Geração automática
3. Registro de emissão
4. Disponibilização de link
5. Verificação online pública

---

## 🚀 Recursos Avançados

### 1. Smart Shuffle
- Embaralhamento inteligente de questões
- Manutenção da coerência temática
- Distribuição equilibrada de dificuldade

### 2. Risk Score Service
- Cálculo dinâmico de risco
- Análise comportamental
- Alertas automáticos

### 3. Webhook Service
- Envio de eventos em tempo real
- Retentativas automáticas
- Logs detalhados de entrega

### 4. Moodle Sync Service
- Sincronização bidirecional
- Mapeamento de atividades
- Tratamento de conflitos

---

## 📈 Métricas e Monitoramento

### KPIs de Segurança
- Taxa de eventos suspeitos
- Tempo médio de detecção
- Taxa de fraudes prevenidas
- Disponibilidade do sistema

### KPIs de Utilização
- Provas realizadas por período
- Taxa de aprovação
- Tempo médio de conclusão
- Utilização por perfil

---

## 🔍 Auditoria e Compliance

### Registros Mantidos
- Todas as ações de usuários
- Eventos de segurança
- Alterações de dados
- Acessos à API
- Sincronizações externas

### Período de Retenção
- Logs de auditoria: 5 anos
- Eventos de segurança: 2 anos
- Dados de usuários: Indeterminado (soft delete)
- Certificados: Vitalícios

### Exportação de Dados
- Relatórios em CSV
- Logs estruturados
- Backup automatizado
- Recuperação seletiva

---

## 📞 Suporte e Manutenção

### Níveis de Suporte
- **Nível 1**: Usuários finais
- **Nível 2**: Professores e coordenadores
- **Nível 3**: Administradores do sistema
- **Nível 4**: Desenvolvedores

### Procedimentos de Emergência
- Bloqueio imediato de acessos suspeitos
- Isolamento de eventos críticos
- Notificação automática de administradores
- Preservação de evidências

---

## 🔄 Roadmap de Evolução

### Próximas Funcionalidades
- Análise preditiva de fraudes
- Integração com mais LMSs
- Mobile app para monitoramento
- IA para análise de respostas

### Melhorias de Segurança
- Autenticação multifator
- Blockchain para certificados
- Machine learning para detecção
- Criptografia quântica

---

**Documento atualizado em**: 19 de março de 2026
**Versão do sistema**: Laravel 12, PHP 8.2+
**Responsável**: Equipe de Desenvolvimento FA
