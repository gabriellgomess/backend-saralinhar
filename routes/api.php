<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ResumeController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\CandidatePreferenceController;
use App\Http\Controllers\Api\DiscTestController;
use App\Http\Controllers\Api\DiscTestTokenController;
use App\Http\Controllers\Api\CultureFitTestController;
use App\Http\Controllers\Api\CultureFitTestTokenController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AssessmentBuilderController;
use App\Http\Controllers\Api\AssessmentPublicController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\GoogleWebhookController;
use App\Http\Controllers\RecruitmentClientController;
use App\Http\Controllers\RecruitmentActivityController;
use App\Http\Middleware\ValidateDiscToken;
use App\Http\Middleware\ValidateCultureFitToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Rotas públicas do Google Agenda
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
Route::post('/webhooks/google-calendar', [GoogleWebhookController::class, 'handle']);

// Rotas públicas de vagas (feed)
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/pending', [JobController::class, 'pendingJobs'])->middleware(['auth:sanctum', 'block_roles:client']); // ANTES do {id}
Route::get('/jobs/{id}', [JobController::class, 'show']);

// Rotas públicas de categorias
Route::get('/categories', [CategoryController::class, 'index']);

// Rotas públicas do EntrevistaPro AI (app mobile)
Route::get('/interview/areas', [\App\Http\Controllers\Api\InterviewContentController::class, 'areas']);
Route::get('/interview/areas/{id}/questions', [\App\Http\Controllers\Api\InterviewContentController::class, 'questions']);

// Rota pública para upload de currículo (ANTIGO - será deprecado)
Route::post('/resumes', [ResumeController::class, 'store']);

// NOVO: Rota pública para submissão de candidato
Route::post('/candidates/submit', [CandidateController::class, 'submit']);

// Rota pública para o construtor de currículo
Route::post('/resume-builder', [CandidateController::class, 'submitFromBuilder']);
Route::get('/resume-builder/download/{filename}', [CandidateController::class, 'downloadBuilderResume']);

// Rotas públicas para teste DISC via token
Route::get('/disc/questions', [DiscTestController::class, 'getQuestions']); // Buscar questões (público)
Route::get('/disc/token/{token}/validate', [DiscTestTokenController::class, 'validateToken']); // Validar token
Route::middleware('validate.disc.token')->group(function () {
    Route::post('/disc/token/{token}/submit', [DiscTestController::class, 'submitPublicTest']); // Submeter teste público
    Route::get('/disc/token/{token}/result/{resultId}', [DiscTestController::class, 'getPublicResult']); // Buscar resultado público
});

// Rotas públicas para teste Culture Fit via token (questões são públicas para candidatos)
Route::get('/culture-fit/questions', [CultureFitTestController::class, 'getQuestions']); // Buscar questões (público)


// Rotas protegidas (requer autenticação)
Route::middleware('auth:sanctum')->group(function () {
    // Autenticação
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Notificações (leitura manual — polling removido por limite de conexões da hospedagem)
    Route::get('/notifications',           [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all',  [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    // Vagas
    Route::post('/jobs', [JobController::class, 'store']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::patch('/jobs/{id}/toggle-status', [JobController::class, 'toggleStatus']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
    Route::get('/my-jobs', [JobController::class, 'myJobs']);
    Route::get('/dashboard-stats', [JobController::class, 'dashboardStats']);
    Route::post('/test-whatsapp', [JobController::class, 'testWhatsAppIntegration'])->middleware('block_roles:client');

    // Categorias (apenas admin autenticado)
    Route::middleware('block_roles:client')->group(function () {
        Route::get('/categories/all', [CategoryController::class, 'all']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    });

    // EntrevistaPro AI — gerenciamento de áreas e perguntas (painel do site)
    Route::prefix('interview/admin')->middleware('block_roles:client,candidate,company')->group(function () {
        Route::get('/areas', [\App\Http\Controllers\Api\InterviewContentController::class, 'areasAll']);
        Route::post('/areas', [\App\Http\Controllers\Api\InterviewContentController::class, 'storeArea']);
        Route::put('/areas/{id}', [\App\Http\Controllers\Api\InterviewContentController::class, 'updateArea']);
        Route::delete('/areas/{id}', [\App\Http\Controllers\Api\InterviewContentController::class, 'destroyArea']);
        Route::get('/questions', [\App\Http\Controllers\Api\InterviewContentController::class, 'questionsAll']);
        Route::post('/questions', [\App\Http\Controllers\Api\InterviewContentController::class, 'storeQuestion']);
        Route::put('/questions/{id}', [\App\Http\Controllers\Api\InterviewContentController::class, 'updateQuestion']);
        Route::delete('/questions/{id}', [\App\Http\Controllers\Api\InterviewContentController::class, 'destroyQuestion']);
    });

    // Currículos ANTIGOS (apenas admin autenticado) - será deprecado
    Route::get('/resumes', [ResumeController::class, 'index']);
    Route::get('/resumes/professional-areas', [ResumeController::class, 'professionalAreas']);
    Route::get('/resumes/{id}', [ResumeController::class, 'show']);
    Route::get('/resumes/{id}/download', [ResumeController::class, 'download']);
    Route::delete('/resumes/{id}', [ResumeController::class, 'destroy']);
    Route::get('/jobs/{jobId}/resumes', [ResumeController::class, 'getByJob']);

    // Preferências do Candidato (Must be before /candidates/{id})
    Route::get('/candidates/preferences', [CandidatePreferenceController::class, 'show']);
    Route::put('/candidates/profile', [CandidatePreferenceController::class, 'update']);
    Route::put('/candidates/{id}/preferences', [CandidatePreferenceController::class, 'update']);

    // NOVO: Candidatos (apenas admin autenticado)
    // ATENÇÃO: Rotas com segmentos literais DEVEM vir antes de /candidates/{id}
    Route::get('/candidates', [CandidateController::class, 'index']); // Banco de Currículos
    Route::get('/candidates/professional-areas', [CandidateController::class, 'professionalAreas']);
    Route::get('/candidates/map-stats', [CandidateController::class, 'mapStats']);

    // Extração e importação em lote (apenas equipe interna)
    Route::middleware('block_roles:client')->group(function () {
        Route::post('/candidates/extract-data', [CandidateController::class, 'extractData']);
        Route::post('/candidates/batch-store',  [CandidateController::class, 'batchStore']);

        // Importação em lote via fila (novo fluxo assíncrono)
        Route::get('/candidates/batches',           [CandidateController::class, 'batches']);
        Route::delete('/candidates/batches/{id}',   [CandidateController::class, 'destroyBatch']);
        Route::post('/candidates/batch-start',      [CandidateController::class, 'batchStart']);
        Route::post('/candidates/batch-add-file',   [CandidateController::class, 'batchAddFile']);
        Route::get('/candidates/batch-status/{id}', [CandidateController::class, 'batchStatus']);
        Route::post('/candidates/batch-confirm',    [CandidateController::class, 'batchConfirm']);
    });

    Route::get('/candidates/search-tests', [CandidateController::class, 'searchTestsByEmail']);

    // Rotas com wildcard {id} — devem ficar por último
    Route::get('/candidates/{id}', [CandidateController::class, 'show']);
    Route::put('/candidates/{id}', [CandidateController::class, 'update']);
    Route::get('/candidates/{id}/download', [CandidateController::class, 'download']);
    Route::delete('/candidates/{id}', [CandidateController::class, 'destroy']);
    Route::get('/jobs/{jobId}/applications', [CandidateController::class, 'getApplicationsByJob']);
    Route::post('/jobs/{jobId}/link-candidates', [CandidateController::class, 'linkCandidatesToJob']);
    Route::post('/jobs/{jobId}/add-candidate', [CandidateController::class, 'addCandidateToJob']);
    Route::post('/jobs/{jobId}/applications/{applicationId}/update-files-tests', [CandidateController::class, 'updateApplicationFilesAndTests']);
    Route::delete('/jobs/{jobId}/applications/{applicationId}', [CandidateController::class, 'unlinkCandidateFromJob']);
    Route::get('/jobs/{jobId}/applications/{applicationId}/download/{fileType}', [CandidateController::class, 'downloadApplicationFile']);
    Route::patch('/jobs/{jobId}/applications/{applicationId}/stage', [JobController::class, 'updateApplicationStage']);
    Route::get('/applications/{applicationId}/comments', [CandidateController::class, 'getComments']);
    Route::post('/applications/{applicationId}/comments', [CandidateController::class, 'addComment']);
    Route::delete('/applications/{applicationId}/comments/{commentId}', [CandidateController::class, 'deleteComment']);

    // Teste DISC (requer autenticação)
    Route::post('/disc/submit', [DiscTestController::class, 'submitTest']); // Submeter respostas
    Route::get('/disc/result/{id}', [DiscTestController::class, 'getResult']); // Buscar resultado específico
    Route::delete('/disc/result/{id}', [DiscTestController::class, 'destroy']); // Excluir resultado
    Route::get('/disc/my-results', [DiscTestController::class, 'getMyResults']); // Histórico de testes
    Route::get('/disc/latest', [DiscTestController::class, 'getLatestResult']); // Último teste realizado
    Route::get('/disc/result/{id}/pdf', [DiscTestController::class, 'downloadPdf']); // Download PDF

    // Gerenciamento de tokens DISC (requer autenticação)
    Route::get('/disc/tokens', [DiscTestTokenController::class, 'index']); // Listar tokens
    Route::post('/disc/tokens', [DiscTestTokenController::class, 'store']); // Criar token
    Route::get('/disc/tokens/{id}', [DiscTestTokenController::class, 'show']); // Mostrar token
    Route::put('/disc/tokens/{id}', [DiscTestTokenController::class, 'update']); // Atualizar token
    Route::patch('/disc/tokens/{id}/cancel', [DiscTestTokenController::class, 'cancel']); // Cancelar token
    Route::delete('/disc/tokens/{id}', [DiscTestTokenController::class, 'destroy']); // Excluir token

    // Teste Culture Fit (requer autenticação)
    Route::post('/culture-fit/submit', [CultureFitTestController::class, 'submitTest']); // Submeter respostas
    Route::get('/culture-fit/result/{id}', [CultureFitTestController::class, 'getResult']); // Buscar resultado específico
    Route::delete('/culture-fit/result/{id}', [CultureFitTestController::class, 'destroy']); // Excluir resultado
    Route::get('/culture-fit/my-results', [CultureFitTestController::class, 'getMyResults']); // Histórico de testes
    Route::get('/culture-fit/latest', [CultureFitTestController::class, 'getLatestResult']); // Último teste realizado
    Route::get('/culture-fit/result/{id}/pdf', [CultureFitTestController::class, 'downloadPdf']); // Download PDF

    // Gerenciamento de tokens Culture Fit (requer autenticação)
    Route::get('/culture-fit/tokens', [CultureFitTestTokenController::class, 'index']); // Listar tokens
    Route::post('/culture-fit/tokens', [CultureFitTestTokenController::class, 'store']); // Criar token
    Route::get('/culture-fit/tokens/{id}', [CultureFitTestTokenController::class, 'show']); // Mostrar token
    Route::put('/culture-fit/tokens/{id}', [CultureFitTestTokenController::class, 'update']); // Atualizar token
    Route::patch('/culture-fit/tokens/{id}/cancel', [CultureFitTestTokenController::class, 'cancel']); // Cancelar token
    Route::delete('/culture-fit/tokens/{id}', [CultureFitTestTokenController::class, 'destroy']); // Excluir token

    // Pareceres PDFs acessíveis para todos autenticados (incluindo clientes)
    Route::get('candidate-reports/{id}/pdf', [\App\Http\Controllers\Api\CandidateReportController::class, 'downloadPdf']);
    Route::get('candidate-reports/{id}/player-pdf', [\App\Http\Controllers\Api\CandidateReportController::class, 'downloadPlayerPdf']);
    Route::get('player-reports/{id}/pdf', [\App\Http\Controllers\Api\PlayerReportController::class, 'downloadPdf']);

    // Rotas administrativas (bloqueadas para perfil 'client')
    Route::middleware('block_roles:client')->group(function () {
        // Gerenciamento de Clientes e Atividades
        Route::get('/recruitment/clients', [RecruitmentClientController::class, 'index']);
        Route::post('/recruitment/clients', [RecruitmentClientController::class, 'store']);
        Route::get('/recruitment/clients/{id}', [RecruitmentClientController::class, 'show']);
        Route::put('/recruitment/clients/{id}', [RecruitmentClientController::class, 'update']);
        Route::delete('/recruitment/clients/{id}', [RecruitmentClientController::class, 'destroy']);

        Route::get('/recruitment/activities', [RecruitmentActivityController::class, 'index']);
        Route::post('/recruitment/activities', [RecruitmentActivityController::class, 'store']);
        Route::get('/recruitment/activities/{id}', [RecruitmentActivityController::class, 'show']);
        Route::put('/recruitment/activities/{id}', [RecruitmentActivityController::class, 'update']);
        Route::delete('/recruitment/activities/{id}', [RecruitmentActivityController::class, 'destroy']);
        Route::get('/recruitment/dashboard-stats', [RecruitmentActivityController::class, 'dashboardStats']);

        // Aprovação de vagas de clientes
        Route::patch('jobs/{id}/approve', [\App\Http\Controllers\Api\JobController::class, 'approve']);
        Route::patch('jobs/{id}/reject', [\App\Http\Controllers\Api\JobController::class, 'reject']);

        // Usuários por cliente
        Route::get('recruitment/clients/{id}/users', [\App\Http\Controllers\Api\UserController::class, 'byClient']);

        // ============================================================
        // Motor Genérico de Testes — Rotas apenas admin/operational
        // ============================================================
        Route::prefix('assessments')->group(function () {
            // Ações destrutivas/administrativas bloqueadas para clients
            Route::delete('/applications/{id}', [AssessmentController::class, 'destroyApplication']);
            Route::patch('/applications/{id}/extend', [AssessmentController::class, 'extend']);

            // Construtor de instrumentos
            Route::prefix('builder')->group(function () {
                Route::get('/',                                  [AssessmentBuilderController::class, 'index']);
                Route::post('/',                                 [AssessmentBuilderController::class, 'store']);
                Route::get('/{id}',                              [AssessmentBuilderController::class, 'show']);
                Route::put('/{id}',                              [AssessmentBuilderController::class, 'update']);
                Route::delete('/{id}',                           [AssessmentBuilderController::class, 'destroy']);
                Route::patch('/{id}/toggle',                     [AssessmentBuilderController::class, 'toggle']);
                Route::post('/{id}/dimensions',                  [AssessmentBuilderController::class, 'storeDimension']);
                Route::put('/{id}/dimensions/{dimId}',           [AssessmentBuilderController::class, 'updateDimension']);
                Route::delete('/{id}/dimensions/{dimId}',        [AssessmentBuilderController::class, 'destroyDimension']);
                Route::post('/{id}/questions',                   [AssessmentBuilderController::class, 'storeQuestion']);
                Route::put('/{id}/questions/{qId}',              [AssessmentBuilderController::class, 'updateQuestion']);
                Route::delete('/{id}/questions/{qId}',           [AssessmentBuilderController::class, 'destroyQuestion']);
            });
        });

        // Pareceres de Candidatos (Sara)
        Route::post('candidate-reports/process-audio', [\App\Http\Controllers\Api\CandidateReportController::class, 'processAudio']);
        Route::get('candidate-reports/interviewers', [\App\Http\Controllers\Api\CandidateReportController::class, 'interviewers']);
        Route::apiResource('candidate-reports', \App\Http\Controllers\Api\CandidateReportController::class);
        Route::get('candidate-reports/{id}/audio', [\App\Http\Controllers\Api\CandidateReportController::class, 'streamAudio']);
        Route::post('candidate-reports/{id}/regenerate', [\App\Http\Controllers\Api\CandidateReportController::class, 'regenerate']);

        // Pareceres Player (Consultoria Player)
        Route::post('player-reports/process-audio', [\App\Http\Controllers\Api\PlayerReportController::class, 'processAudio']);
        Route::get('player-reports/interviewers', [\App\Http\Controllers\Api\PlayerReportController::class, 'interviewers']);
        Route::apiResource('player-reports', \App\Http\Controllers\Api\PlayerReportController::class);
        Route::get('player-reports/{id}/audio', [\App\Http\Controllers\Api\PlayerReportController::class, 'streamAudio']);
        Route::post('player-reports/{id}/regenerate', [\App\Http\Controllers\Api\PlayerReportController::class, 'regenerate']);

        // Fluxo Conversacional de Pareceres (Modo Experimental)
        Route::prefix('conversational-reports')->group(function () {
            Route::post('chats', [\App\Http\Controllers\Api\ConversationalReportController::class, 'startChat']);
            Route::get('chats', [\App\Http\Controllers\Api\ConversationalReportController::class, 'listChats']);
            Route::get('chats/{id}', [\App\Http\Controllers\Api\ConversationalReportController::class, 'showChat']);
            Route::post('chats/{id}/messages', [\App\Http\Controllers\Api\ConversationalReportController::class, 'sendMessage']);
            Route::post('chats/{id}/finalize', [\App\Http\Controllers\Api\ConversationalReportController::class, 'finalizeReport']);
            Route::delete('chats/{id}', [\App\Http\Controllers\Api\ConversationalReportController::class, 'destroyChat']);
        });
    });

    // ============================================================
    // Motor Genérico de Testes — Rotas autenticadas (admin + client)
    // O controller já aplica scoping multitenant por recruitment_client_id.
    // Apenas destroy e extend ficam restritos (dentro do grupo block_roles:client acima).
    // ============================================================
    Route::prefix('assessments')->group(function () {
        Route::get('/stats',                               [AssessmentController::class, 'stats']);
        Route::get('/applications',                        [AssessmentController::class, 'listApplications']);
        Route::post('/applications',                       [AssessmentController::class, 'createApplication']);
        Route::post('/applications/compare',               [AssessmentController::class, 'compare']);
        Route::post('/applications/compare/pdf',           [AssessmentController::class, 'comparePdf']);
        Route::get('/applications/{id}/result',            [AssessmentController::class, 'result']);
        Route::get('/applications/{id}/pdf',               [AssessmentController::class, 'downloadPdf']);
        Route::post('/applications/{id}/resend',           [AssessmentController::class, 'resend']);
        Route::get('/applications/{id}',                   [AssessmentController::class, 'showApplication']);
        Route::get('/',                                    [AssessmentController::class, 'index']);
        Route::get('/{slug}',                              [AssessmentController::class, 'show']);
    });

    // Usuários (apenas admin)
    Route::middleware('is_admin')->group(function () {
        Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
        Route::put('users/{id}/reset-password', [\App\Http\Controllers\Api\UserController::class, 'resetPassword']);

        // Consumo da API OpenAI
        Route::get('openai-usage', [\App\Http\Controllers\Api\OpenAIUsageController::class, 'index']);
        Route::get('openai-usage/summary', [\App\Http\Controllers\Api\OpenAIUsageController::class, 'summary']);
        Route::get('openai-usage/filters', [\App\Http\Controllers\Api\OpenAIUsageController::class, 'filters']);

        // Logs de Auditoria
        Route::get('audit-logs', [\App\Http\Controllers\Api\TestAuditLogController::class, 'index']);
        Route::get('audit-logs/filters', [\App\Http\Controllers\Api\TestAuditLogController::class, 'filters']);

    });

    // Rotas do Módulo Financeiro (Apenas Admin e Operational)
    Route::prefix('financial')->middleware('block_roles:client,candidate,company')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\FinancialController::class, 'dashboardStats']);
        Route::get('/commissions', [\App\Http\Controllers\Api\FinancialController::class, 'listCommissions']);
        Route::get('/services', [\App\Http\Controllers\Api\FinancialController::class, 'listServices']);
        
        // Bloqueado para recrutadores comuns (operational), clientes e candidatos
        Route::middleware('block_roles:operational,client,candidate,company')->group(function () {
            Route::apiResource('/transactions', \App\Http\Controllers\Api\FinancialController::class);
            Route::post('/services', [\App\Http\Controllers\Api\FinancialController::class, 'storeService']);
            Route::put('/services/{id}', [\App\Http\Controllers\Api\FinancialController::class, 'updateService']);
            Route::delete('/services/{id}', [\App\Http\Controllers\Api\FinancialController::class, 'destroyService']);
            Route::patch('/commissions/{id}/status', [\App\Http\Controllers\Api\FinancialController::class, 'updateCommissionStatus']);
        });
    });

    // Rotas do Google Agenda
    Route::prefix('google-calendar')->group(function () {
        Route::get('/status', [GoogleCalendarController::class, 'status']);
        Route::get('/events', [GoogleCalendarController::class, 'events']);
        Route::delete('/disconnect', [GoogleCalendarController::class, 'disconnect']);
        Route::post('/watch', [GoogleCalendarController::class, 'watch']);
    });
    Route::get('/auth/google/url', [GoogleAuthController::class, 'getAuthUrl']);
});

// ============================================================
// Motor Genérico de Testes — Rotas Públicas (via token)
// ============================================================
Route::prefix('assessment/public')->group(function () {
    Route::get('{token}',         [AssessmentPublicController::class, 'start']);
    Route::post('{token}/submit', [AssessmentPublicController::class, 'submit']);
    Route::get('{token}/result',  [AssessmentPublicController::class, 'result']);
});

// Rotas públicas Culture Fit via token
Route::middleware([ValidateCultureFitToken::class])->group(function () {
    Route::get('/culture-fit/token/{token}', [CultureFitTestTokenController::class, 'validateToken']);
    Route::post('/culture-fit/token/{token}/submit', [CultureFitTestController::class, 'submitPublicTest']);
    Route::get('/culture-fit/token/{token}/result/{resultId}', [CultureFitTestController::class, 'getPublicResult']);
});
