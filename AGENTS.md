# AvaliaFA — Instruções para Agentes de IA
> Este arquivo é lido automaticamente pelo Claude Code, Codex e Cursor ao abrir o projeto.
> Não remova nem renomeie este arquivo.

---

## Leitura obrigatória antes de qualquer tarefa

Leia os arquivos abaixo **nesta ordem** antes de escrever qualquer código:

```
1. docs/docs/FONTE_UNICA_CONTEXTO_ATUAL.md → fonte canônica de contexto
2. docs/skills/SKILL_avalia-fa.md          → arquitetura e convenções do projeto
3. prompt de continuidade mais recente em docs/prompts/ → estado atual
```

### Regra de precedência documental

Em caso de conflito entre arquivos, siga esta ordem:

`FONTE_UNICA_CONTEXTO_ATUAL.md` → prompt de continuidade mais recente → `AGENTS.md` → skills específicas → PRD/BRD históricos

Para tarefas específicas, leia também:
```
Criar/editar telas (Blade/CSS):
  → docs/skills/SKILL_design-system.md

Integração Moodle:
  → docs/skills/SKILL_moodle-integration.md

Tela de prova / SecureExamEngine:
  → docs/skills/SKILL_secure-exam-engine.md
```

---

## Projeto

**Nome:** AvaliaFA — Sistema Inteligente de Avaliações da Faculdade Anasps
**Tipo:** Plataforma web de avaliações acadêmicas (standalone + API REST)
**Caminho local:** `E:\xampp\htdocs\avalia-fa`

---

## Stack (NUNCA substituir por outra tecnologia sem aprovação)

| Camada | Tecnologia | Versão |
|---|---|---|
| Backend | Laravel | 12 |
| Linguagem | PHP | 8.2+ (`composer ^8.2`, ambiente local atual 8.2.12) |
| Banco de dados | **MySQL** | 8.0 ← nunca PostgreSQL |
| Cache / Sessões / Filas | Redis | 7 |
| WebSockets | Laravel Reverb | latest |
| OAuth2 (API) | Laravel Passport | latest |
| Frontend templating | Blade + Alpine.js | — |
| CSS | TailwindCSS | 3.x |
| Filas (dashboard) | Laravel Horizon | latest |
| Certificados PDF | TCPDF + FPDI | latest |
| Containers | Docker + Docker Compose | — |

---

## Arquitetura

```
[Graduação]      ─┐
[Pós-Graduação]  ─┼──→ API REST /api/v1/ ──→ [AvaliaFA] ──→ [Moodle]
[Certificadora]  ─┘         OAuth2
```

- AvaliaFA é **standalone** — os 3 sistemas consomem via API REST
- Alunos **NUNCA** se cadastram diretamente — chegam via JWT de uso único
- Cada sistema tem `client_system_id` próprio — **sempre filtrar por ele**

---

## Convenções obrigatórias

### Código
```
Idioma do código:     inglês (variáveis, métodos, classes, comentários)
Idioma da UI:         português brasileiro
Namespace:            App\
Rotas API:            /api/v1/
Soft deletes:         obrigatório em TODAS as tabelas principais
Timestamps:           America/Sao_Paulo
```

### Respostas da API (sempre neste formato)
```php
// Sucesso
return response()->json(['success' => true,  'data'   => $payload], 200);

// Criação
return response()->json(['success' => true,  'data'   => $payload], 201);

// Erro de validação
return response()->json(['success' => false, 'errors' => $errors],  422);

// Não autorizado
return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
```

### Multi-sistema — regra mais importante
```php
// SEMPRE filtrar por client_system_id ao buscar qualquer recurso
$exam = Exam::where('id', $examId)
    ->where('client_system_id', $request->clientSystem()->id)
    ->firstOrFail();
```

### Filas — cada domínio tem sua fila isolada
```
webhooks       → SendWebhook job
moodle-sync    → SyncGradeToMoodle, RetryMoodleSync jobs
certificates   → IssueCertificate job
security       → RecordSecurityEvent, SaveSnapshot jobs
default        → demais jobs
```

---

## O que JÁ foi implementado

### Infraestrutura
- [x] `docker-compose.yml` — 7 serviços (app, nginx, MySQL 8, Redis, queue, reverb, horizon)
- [x] `docker/Dockerfile`, `nginx/default.conf`, `mysql/my.cnf`, `php/local.ini`
- [x] `.env.example` completo

### Banco de Dados — migrations existentes
```
2026_01_01_000001  → client_systems
2026_01_01_000002  → users
2026_01_01_000003  → questions, choices, exams, exam_questions
2026_01_01_000004  → exam_sessions, answers, security_events, snapshots
2026_01_01_000005  → certificates, moodle_sync_logs, webhook_logs, audit_logs
2026_01_01_000006  → exam_versions + alterações em exam_sessions/questions
2026_03_10_190428+ → tabelas OAuth do Passport
```

### Models criados
- [x] `app/Models/User.php`
- [x] `app/Models/ClientSystem.php`
- [x] `app/Models/ExamSession.php`
- [x] `app/Models/Exam.php`
- [x] `app/Models/Question.php`
- [x] `app/Models/Choice.php`
- [x] `app/Models/Answer.php`
- [x] `app/Models/Certificate.php`
- [x] `app/Models/SecurityEvent.php`
- [x] `app/Models/Snapshot.php`
- [x] `app/Models/MoodleSyncLog.php`
- [x] `app/Models/WebhookLog.php`
- [x] `app/Models/ExamVersion.php`
- [x] `app/Models/ExamQuestion.php`
- [x] `app/Models/Discipline.php`
- [x] `app/Models/AuditLog.php`
- [x] `app/Models/Setting.php`
- [x] `app/Models/LtiRegistration.php`
- [x] `app/Models/LtiResourceLink.php`
- [x] `app/Models/LtiLaunchLog.php`

### Controllers (API v1)
- [x] `app/Http/Controllers/Api/V1/AuthController.php`
- [x] `app/Http/Controllers/Api/V1/ExamController.php`
- [x] `app/Http/Controllers/Api/V1/StudentController.php`
- [x] `app/Http/Controllers/Api/V1/ExamSessionController.php`

### Controllers (Web)
- [x] `app/Http/Controllers/Web/AuthWebController.php`
- [x] `app/Http/Controllers/Web/DashboardController.php`
- [x] `app/Http/Controllers/Web/ExamWebController.php`
- [x] `app/Http/Controllers/Web/ProvaController.php`
- [x] `app/Http/Controllers/Web/QuestaoController.php`
- [x] `app/Http/Controllers/Web/EstudanteController.php`
- [x] `app/Http/Controllers/Web/PerfilController.php`
- [x] `app/Http/Controllers/Web/ConfiguracaoController.php`
- [x] `app/Http/Controllers/Web/RelatorioController.php`
- [x] `app/Http/Controllers/Web/CertificadoController.php`
- [x] `app/Http/Controllers/Web/SistemaController.php`
- [x] `app/Http/Controllers/Web/OperacoesController.php`
- [x] `app/Http/Controllers/Web/LtiToolController.php`

### Middlewares
- [x] `app/Http/Middleware/AuthApiClient.php`
- [x] `app/Http/Middleware/ApiIpAllowlist.php`

### Services
- [x] `app/Services/Api/SessionTokenService.php`
- [x] `app/Services/MoodleSyncService.php`
- [x] `app/Services/ParallelSessionGuard.php`
- [x] `app/Services/SmartShuffleService.php`
- [x] `app/Services/AuditLogService.php`
- [x] `app/Services/CertificateService.php`
- [x] `app/Services/WebhookService.php`
- [x] `app/Services/RiskScoreService.php`
- [x] `app/Services/MoodleActivityResolver.php`
- [x] `app/Services/MoodleEnrollmentService.php`
- [x] `app/Services/Lti/LtiLaunchService.php`
- [x] `app/Services/Lti/LtiGradeSyncService.php`
- [x] `app/Services/SystemMaintenanceService.php`

### Frontend
- [x] `resources/js/secure-exam-engine.js`
- [x] `resources/views/layouts/app.blade.php`
- [x] `resources/views/layouts/exam.blade.php`
- [x] `resources/views/auth/login.blade.php`
- [x] `resources/views/dashboard.blade.php`
- [x] `resources/views/exam/start.blade.php`
- [x] `resources/views/exam/show.blade.php`
- [x] `resources/views/exam/result.blade.php`
- [x] `resources/views/exam/error.blade.php`
- [x] `resources/views/monitor/index.blade.php`
- [x] `resources/views/provas/index.blade.php`
- [x] `resources/views/provas/form.blade.php`
- [x] `resources/views/provas/show.blade.php`
- [x] `resources/views/questoes/index.blade.php`
- [x] `resources/views/questoes/form.blade.php`
- [x] `resources/views/questoes/show.blade.php`
- [x] `resources/views/questoes/import.blade.php`
- [x] `resources/views/estudantes/index.blade.php`
- [x] `resources/views/estudantes/form.blade.php`
- [x] `resources/views/estudantes/show.blade.php`
- [x] `resources/views/perfil/index.blade.php`
- [x] `resources/views/perfil/senha.blade.php`

### Seeders
- [x] `database/seeders/ClientSystemSeeder.php`
- [x] `database/seeders/AdminUserSeeder.php`

### Eventos / Jobs / Filas
- [x] `app/Events/ExamStarted.php`
- [x] `app/Events/ExamSubmitted.php`
- [x] `app/Events/ViolationDetected.php`
- [x] `app/Events/GradePublished.php`
- [x] `app/Events/CertificateIssued.php`
- [x] `app/Jobs/SyncGradeToMoodle.php`
- [x] `app/Jobs/SendWebhook.php`
- [x] `app/Jobs/IssueCertificate.php`
- [x] `app/Jobs/RecordSecurityEvent.php`
- [x] `config/horizon.php` (5 workers separados)

### Rotas
- [x] `routes/api.php`
- [x] `routes/web.php`

---

## O que FALTA implementar (próximas tarefas)

### 🔴 Prioridade Alta — MVP bloqueado sem isso
```
[x] Migration: exam_versions (nova tabela)
[x] Migration: alter exam_sessions (+exam_version_id, +device_fingerprint, +risk_score, +is_simulation)
[x] Migration: alter questions (+owner_department, +visibility_scope)
[x] Model: Exam
[x] Model: Question
[x] Model: Choice
[x] Model: Answer
[x] Model: Certificate
[x] Model: SecurityEvent
[x] Model: Snapshot
[x] Model: MoodleSyncLog
[x] Model: WebhookLog
[x] Middleware: app/Http/Middleware/AuthApiClient.php
[x] Middleware: app/Http/Middleware/ApiIpAllowlist.php
[x] Controller: Api/V1/AuthController.php (OAuth2 token endpoint)
[x] Controller: Api/V1/ExamController.php
[x] Seeder: ClientSystemSeeder (Graduação, Pós, Certificadora)
[x] Seeder: AdminUserSeeder
[x] Config: config/horizon.php (5 workers separados)
[x] Views Blade: layout base, tela de prova, painel monitoramento
[x] Vínculo de questões a provas
[x] Geração de link JWT para aluno em `provas/show`
[x] Importação de estudantes por CSV
[x] Migration: add first_name, last_name to users
```

### 🟡 Prioridade Média — Fase 2
```
[x] Events: ExamStarted, ExamSubmitted, ViolationDetected, GradePublished, CertificateIssued
[x] Listeners mapeados via `AppServiceProvider`
[x] Job: SyncGradeToMoodle (queue: moodle-sync)
[x] Job: SendWebhook (queue: webhooks)
[x] Job: IssueCertificate (queue: certificates)
[x] Job: RecordSecurityEvent (queue: security)
[x] Service: ParallelSessionGuard (Redis)
[x] Service: SmartShuffleService
[x] SecureExamEngine v2 (inatividade + múltiplos monitores)
[x] Views Blade: layout base, tela de prova, painel monitoramento
[x] Relatórios
[x] Módulo Sistemas (Super Admin)
[x] Certificados (listagem + verificação pública)
```

### 🟢 Prioridade Normal — Fase 3
```
[x] Service: RiskScoreService
[x] Service: CertificateService (TCPDF)
[x] Service: WebhookService
[x] Service: MoodleActivityResolver
[x] Dashboard institucional
[x] Simulador de prova
[x] Modo offline IndexedDB
```

---

## Banco de Dados — campos já implementados

```sql
-- Nova tabela
exam_versions (
  id, exam_id FK, version_number SMALLINT,
  snapshot JSON, questions_count,
  published_by FK users, published_at TIMESTAMP
)

-- Alterações exam_sessions
ADD COLUMN exam_version_id    BIGINT UNSIGNED NULL
ADD COLUMN device_fingerprint VARCHAR(64) NULL
ADD COLUMN device_metadata    JSON NULL
ADD COLUMN risk_score         TINYINT UNSIGNED DEFAULT 0
ADD COLUMN is_simulation      BOOLEAN DEFAULT FALSE

-- Alterações questions
ADD COLUMN owner_department  VARCHAR(100) NULL
ADD COLUMN visibility_scope  ENUM('private','department','system','global') DEFAULT 'department'
```

---

## Enums importantes

```php
// User roles
['super_admin', 'admin', 'coordinator', 'professor', 'student']

// Exam status
['draft', 'published', 'active', 'closed', 'archived']

// ExamSession status
['pending', 'in_progress', 'submitted', 'expired', 'terminated', 'graded']

// Question types
['multiple_choice', 'true_false', 'essay', 'multiple_answer', 'ordering']

// SecurityEvent types
['fullscreen_exit', 'fullscreen_denied', 'tab_switch', 'window_blur',
 'shortcut_blocked', 'right_click_blocked', 'violation_warning',
 'violation_limit_reached', 'inactivity_warning', 'inactivity_timeout',
 'webcam_unavailable', 'possible_second_monitor', 'connection_lost']

// Snapshot trigger
['start', 'scheduled', 'violation', 'end']
```

---

## Dependências Composer necessárias

```bash
composer require laravel/passport
composer require firebase/php-jwt
composer require tecnickcom/tcpdf
composer require laravel/horizon
composer require laravel/reverb
```

---

## Como rodar localmente

```bash
# Com Docker
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan passport:install --uuids
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan horizon

# Sem Docker (XAMPP — ambiente atual)
cd E:\xampp\htdocs\avalia-fa
php artisan key:generate
php artisan passport:install --uuids
php artisan migrate --seed
php artisan horizon
php artisan reverb:start
php artisan queue:work --queue=security,moodle-sync,webhooks,certificates,default
```

---

### Novas convenções adotadas na Sessão 06

```
Toast system:     layouts/app.blade.php — session('success'|'error'|'warning'|'info') → toast
                  Não usar @if(session()) banners inline — todos os flash vão via toast

Campos de nome:   User → first_name + last_name + name (computed = trim(first+last))
                  Formulários usam dois campos; name é preenchido automaticamente

scopeActive():    ClientSystem::active()->get() em vez de
                  ClientSystem::where('active', true)->orderBy('name')->get()

Import CSV:       Aceita dois formatos: novo (primeiro_nome+sobrenome) ou legado (nome)
                  Pré-carrega CPFs e emails antes do loop (evita N+1)
                  Redireciona para import.form quando há erros; para index quando limpo
                  import_errors exibidos apenas em import.blade.php
```

### Novas convenções adotadas na Sessão 07

```
Foto de perfil:  User::hasProfilePhoto(), User::profilePhotoUrl()
                 Storage: profile-photos/{user_id}/photo.jpg (disco public)
                 Captura obrigatória na tela exam/start se aluno não tem foto

CPF vs Moodle:   preg_match('/^\d{11}$/', $cpfRaw) → CPF real
                 Senão → exibir como "ID Moodle"

LTI 1.3:         app/Services/Lti/ — OIDC, JWT RS256, AGS, Deep Linking
                 LtiGradeSyncService normaliza score para escala do Moodle
                 LtiLaunchService detecta sessões existentes (evita duplicatas)
```

### Novas convenções adotadas na Sessão 08

```
Discipline:      app/Models/Discipline.php — belongsTo(ClientSystem), hasMany(Question)
                 Discipline::findOrCreateByName($name, $clientSystemId) → idempotente
                 Exam e Question possuem discipline_id (nullable FK)

Criação inline:  Formulários usam Alpine.js toggle com sentinela '__new__'
                 Controller normaliza sentinelas antes de resolveDisciplineId()
                 Validação usa closure (não 'exists:disciplines,id')

Filtragem:       Disciplinas filtradas por sistema via data-system no <option>
                 JS vanilla filtra ao mudar select client_system_id

Dashboard KPIs:  DashboardController passa $stats com total_questions
                 Cards KPI usam padrão: kpi-card, kpi-icon gradient, kpi-value, kpi-badge
```

### Migrations adicionais (Sessões 07-08)
```
2026_03_18_000001  → add profile_photo_path to users
2026_03_18_000003  → add discipline_id to exams
2026_03_16_000001  → create settings table
2026_03_17_120000  → LTI foundation tables (lti_registrations, lti_resource_links, lti_launch_logs)
```

*AGENTS.md v2.3 — AvaliaFA — Atualizado Sessão 08 — Março de 2026*
*Lido automaticamente por: Claude Code · Codex · Cursor · GitHub Copilot*
