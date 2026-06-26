<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    protected $evolutionApiService;

    public function __construct(EvolutionApiService $evolutionApiService)
    {
        $this->evolutionApiService = $evolutionApiService;
    }
    /**
     * Listar todas as vagas ativas (feed) com filtros
     */
    public function index(Request $request)
    {
        $query = Job::with(['user:id,name,email', 'category:id,name,slug'])
            ->where('is_active', true);

        // Filtro por título
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        // Filtro por cidade
        if ($request->filled('city')) {
            $query->where('address', 'like', '%' . $request->city . '%');
        }

        // Filtro por categoria
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtro por período
        if ($request->filled('period')) {
            $now = now();
            switch ($request->period) {
                case '24h':
                    $query->where('created_at', '>=', $now->subDay());
                    break;
                case 'week':
                    $query->where('created_at', '>=', $now->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', $now->subMonth());
                    break;
                    // 'all' não precisa de filtro
            }
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 12));

        // Adicionar campos de contato baseados no usuário autenticado
        $jobs->getCollection()->transform(function ($job) {
            $job->contact_email = $job->contact_email;
            $job->contact_phone = $job->contact_phone;
            return $job;
        });

        return response()->json($jobs, 200);
    }

    /**
     * Listar cidades únicas das vagas ativas
     */
    public function cities()
    {
        $cities = Job::where('is_active', true)
            ->distinct()
            ->pluck('address')
            ->map(function ($address) {
                // Extrai a cidade do endereço (assumindo formato: "Cidade - Estado" ou "Cidade/Estado")
                $parts = preg_split('/[-\/,]/', $address);
                return trim(end($parts));
            })
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'data' => $cities,
        ], 200);
    }

    /**
     * Criar nova vaga
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'description' => 'required|string',
            'responsibilities' => 'required|string',
            'requirements' => 'required|string',
            'workload' => 'required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'benefits' => 'nullable|string',
            'type' => 'required|string|in:clt,pj,estagio,aprendiz',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_confidential' => 'boolean',
            'original_email' => 'nullable|email',
            'original_phone' => 'nullable|string|max:20',
        ]);

        $validated['user_id'] = $request->user()->id;

        // Vagas criadas por clientes ficam pendentes de aprovação
        if ($request->user()->role === 'client') {
            $validated['approval_status'] = 'pending';
            $validated['is_active'] = false; // inativa até ser aprovada

            // Empresa definida automaticamente pelo cliente vinculado
            if ($request->user()->recruitment_client_id) {
                $client = \App\Models\RecruitmentClient::find($request->user()->recruitment_client_id);
                if ($client) {
                    $validated['company'] = $client->name;
                }
            }
        }

        // Se company estiver vazio, automaticamente marca como confidencial
        if (empty($validated['company'])) {
            $validated['company'] = 'Confidencial';
            $validated['is_confidential'] = true;
        }

        $job = Job::create($validated);

        // Auto-criar faturamento se a vaga já estiver ativa/aprovada (ex: criada por Admin)
        if ($job->approval_status !== 'pending' && $job->is_active) {
            try {
                \App\Models\FinancialTransaction::autoCreateForJob($job);
            } catch (\Exception $e) {
                Log::error('Erro ao auto-criar transação financeira para vaga no store: ' . $e->getMessage());
            }
        }

        // Enviar vaga para WhatsApp via Evolution API
        try {
            $jobData = $job->load(['user:id,name,email', 'category:id,name,slug'])->toArray();
            $this->evolutionApiService->sendJobToWhatsApp($jobData);
        } catch (\Exception $e) {
            // Log do erro mas não falha a criação da vaga
            Log::error('Erro ao enviar vaga para WhatsApp', [
                'job_id' => $job->id,
                'error' => $e->getMessage()
            ]);
        }

        // Notificar candidatos com preferência nesta categoria
        try {
            // Encontrar candidatos que têm interesse nesta categoria
            $category = \App\Models\Category::find($validated['category_id']);
            if ($category) {
                $candidates = $category->candidates;
                
                // Para cada candidato, encontrar o usuário correspondente e notificar
                foreach ($candidates as $candidate) {
                    $user = \App\Models\User::where('email', $candidate->email)->first();
                    if ($user) {
                        $user->notify(new \App\Notifications\NewJobAvailable($job));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao notificar candidatos', [
                'job_id' => $job->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'message' => 'Vaga criada com sucesso',
            'job' => $job->load(['user:id,name,email', 'category:id,name,slug']),
        ], 201);
    }

    /**
     * Exibir uma vaga específica
     */
    public function show(string $id)
    {
        $job = Job::with(['user:id,name,email', 'category:id,name,slug'])->findOrFail($id);

        // Adicionar campos de contato baseados no usuário autenticado
        $job->contact_email = $job->contact_email;
        $job->contact_phone = $job->contact_phone;

        return response()->json($job, 200);
    }

    /**
     * Atualizar uma vaga existente
     */
    public function update(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'title' => 'sometimes|required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'responsibilities' => 'sometimes|required|string',
            'requirements' => 'sometimes|required|string',
            'workload' => 'sometimes|required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'benefits' => 'nullable|string',
            'type' => 'sometimes|required|string|in:clt,pj,estagio,aprendiz',
            'email' => 'sometimes|required|email',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_confidential' => 'boolean',
            'original_email' => 'nullable|email',
            'original_phone' => 'nullable|string|max:20',
        ]);

        // Se company estiver vazio, automaticamente marca como confidencial
        if (isset($validated['company']) && empty($validated['company'])) {
            $validated['company'] = 'Confidencial';
            $validated['is_confidential'] = true;
        }

        $job->update($validated);

        return response()->json([
            'message' => 'Vaga atualizada com sucesso',
            'job' => $job->load(['user:id,name,email', 'category:id,name,slug']),
        ], 200);
    }

    /**
     * Deletar uma vaga
     */
    public function destroy(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $job->delete();

        return response()->json([
            'message' => 'Vaga deletada com sucesso',
        ], 200);
    }

    /**
     * Listar todas as vagas (área administrativa ou portal do cliente)
     */
    public function myJobs(Request $request)
    {
        $query = Job::with(['user:id,name,email,role', 'category:id,name,slug'])
            ->withCount('candidateApplications as resumes_count');

        // Clientes veem todas as vagas do seu cliente (por company name ou user_id do mesmo cliente)
        if (auth()->user()->role === 'client') {
            $clientId = auth()->user()->recruitment_client_id;
            if ($clientId) {
                $client = \App\Models\RecruitmentClient::find($clientId);
                $clientUserIds = \App\Models\User::where('recruitment_client_id', $clientId)->pluck('id');
                $query->where(function ($q) use ($client, $clientUserIds) {
                    $q->whereIn('user_id', $clientUserIds);
                    if ($client) {
                        $q->orWhere('company', $client->name);
                    }
                });
            } else {
                $query->where('user_id', auth()->id());
            }
        }

        // Filtros
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->filled('company')) {
            $query->where('company', 'like', '%' . $request->company . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'pending') {
                $query->where('approval_status', 'pending');
            } elseif ($status === 'approved') {
                $query->where(function($q) {
                    $q->where('approval_status', 'approved')
                      ->orWhereNull('approval_status');
                });
            } elseif ($status === 'rejected') {
                $query->where('approval_status', 'rejected');
            }
        }

        // Filtro para vagas com currículos
        if ($request->filled('has_resumes')) {
            if ($request->has_resumes === '1') {
                // Vagas que têm pelo menos 1 candidatura
                $query->has('candidateApplications', '>=', 1);
            } else {
                // Vagas sem candidaturas
                $query->doesntHave('candidateApplications');
            }
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        // Adicionar campos de contato baseados no usuário autenticado
        $jobs->getCollection()->transform(function ($job) {
            $job->contact_email = $job->contact_email;
            $job->contact_phone = $job->contact_phone;
            return $job;
        });

        return response()->json($jobs, 200);
    }

    /**
     * Alternar status ativo/inativo da vaga
     */
    public function toggleStatus(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $job->is_active = !$job->is_active;
        $job->save();

        // Se a vaga foi ativada e não está pendente, auto-cria o faturamento se necessário
        if ($job->is_active && $job->approval_status !== 'pending') {
            try {
                \App\Models\FinancialTransaction::autoCreateForJob($job);
            } catch (\Exception $e) {
                Log::error('Erro ao auto-criar transação financeira para vaga no toggleStatus: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => $job->is_active ? 'Vaga ativada com sucesso' : 'Vaga inativada com sucesso',
            'job' => $job->load(['user:id,name,email', 'category:id,name,slug']),
        ], 200);
    }

    /**
     * Obter estatísticas do dashboard
     */
    public function dashboardStats()
    {
        $stats = [
            'total_jobs' => Job::count(),
            'active_jobs' => Job::where('is_active', true)->count(),
            'inactive_jobs' => Job::where('is_active', false)->count(),
            'jobs_by_category' => Job::with('category')
                ->selectRaw('category_id, count(*) as count')
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category_name' => $item->category->name ?? 'Sem categoria',
                        'count' => $item->count
                    ];
                }),
            'recent_jobs' => Job::with(['category:id,name', 'user:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'jobs_by_type' => Job::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => ucfirst($item->type),
                        'count' => $item->count
                    ];
                }),
            'jobs_this_month' => Job::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'jobs_last_month' => Job::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count(),
        ];

        return response()->json($stats, 200);
    }

    /**
     * Testar integração com WhatsApp
     */
    public function testWhatsAppIntegration()
    {
        try {
            $isConnected = $this->evolutionApiService->checkConnection();

            if (!$isConnected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instância do WhatsApp não está conectada',
                    'connected' => false
                ], 400);
            }

            $testResult = $this->evolutionApiService->sendTestMessage();

            if ($testResult) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mensagem de teste enviada com sucesso',
                    'connected' => true
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao enviar mensagem de teste',
                    'connected' => true
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na integração: ' . $e->getMessage(),
                'connected' => false
            ], 500);
        }
    }
    /**
     * Listar vagas aguardando aprovação (admin/operational)
     */
    public function pendingJobs(Request $request)
    {
        $query = Job::with(['user:id,name,email,role', 'category:id,name,slug'])
            ->where('approval_status', 'pending')
            ->orderBy('created_at', 'asc');

        return response()->json($query->paginate(15));
    }

    /**
     * Aprovar vaga de cliente
     */
    public function approve(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'is_confidential' => 'boolean',
        ]);

        $job->update([
            'approval_status' => 'approved',
            'is_active'       => true,
            'rejection_reason' => null,
            'is_confidential' => $validated['is_confidential'] ?? $job->is_confidential,
        ]);

        // Auto-criar faturamento ao aprovar a vaga
        try {
            \App\Models\FinancialTransaction::autoCreateForJob($job);
        } catch (\Exception $e) {
            Log::error('Erro ao auto-criar transação financeira para vaga no approve: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Vaga aprovada com sucesso.',
            'job' => $job->load(['user:id,name,email,role', 'category:id,name,slug']),
        ]);
    }

    /**
     * Rejeitar vaga de cliente
     */
    public function reject(Request $request, string $id)
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $job->update([
            'approval_status' => 'rejected',
            'is_active'       => false,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'message' => 'Vaga rejeitada.',
            'job' => $job->load(['user:id,name,email,role', 'category:id,name,slug']),
        ]);
    }

    /**
     * Atualizar o estágio do candidato no processo seletivo
     */
    public function updateApplicationStage(Request $request, string $jobId, string $applicationId)
    {
        $job = Job::findOrFail($jobId);
        
        // Encontrar a candidatura específica
        $application = $job->candidateApplications()->findOrFail($applicationId);
        
        $validated = $request->validate([
            'pipeline_stage' => 'required|in:new,contacting,interview_scheduled,interviewed,shortlisted,rejected,hired',
            'interview_date' => 'nullable|date',
            'interview_feedback' => 'nullable|string',
            'admin_notes' => 'nullable|string',
        ]);
        
        $oldStage = $application->pipeline_stage;
        $newStage = $validated['pipeline_stage'];
        
        $application->update($validated);
        
        if ($oldStage !== $newStage) {
            $stagesPortuguese = [
                'new' => 'Novo',
                'contacting' => 'Em Contato',
                'interview_scheduled' => 'Entrevista Agendada',
                'interviewed' => 'Entrevistado',
                'shortlisted' => 'Finalista',
                'rejected' => 'Desclassificado',
                'hired' => 'Contratado'
            ];
            
            $oldStageName = $stagesPortuguese[$oldStage] ?? $oldStage;
            $newStageName = $stagesPortuguese[$newStage] ?? $newStage;
            
            $userName = \Illuminate\Support\Facades\Auth::user()?->name ?? 'Sistema';
            
            \App\Models\ApplicationComment::create([
                'candidate_job_application_id' => $application->id,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'comment' => "Movimentação: {$userName} moveu o candidato de \"{$oldStageName}\" para \"{$newStageName}\".",
            ]);

            // Hook do Módulo Financeiro: Atualiza ou cria o faturamento ao contratar o candidato
            if ($newStage === 'hired') {
                try {
                    $candidate = $application->candidate;
                    $client = null;
                    if ($job->user && $job->user->recruitment_client_id) {
                        $client = \App\Models\RecruitmentClient::find($job->user->recruitment_client_id);
                    }
                    if (!$client && $job->company) {
                        $client = \App\Models\RecruitmentClient::where('name', 'like', '%' . $job->company . '%')->first();
                    }

                    $commissionPct = $client ? (float) $client->commission_percentage : 0;
                    $salary = $job->salary ? (float) $job->salary : 0;
                    $amount = $commissionPct > 0 ? ($salary * ($commissionPct / 100)) : 0;

                    // Busca se já existe um lançamento criado ao abrir a vaga
                    $transaction = \App\Models\FinancialTransaction::where('job_id', $job->id)->first();

                    if ($transaction) {
                        $transaction->update([
                            'candidate_id' => $candidate ? $candidate->id : null,
                            'candidate_contact' => $candidate ? ($candidate->phone ?? $candidate->email) : null,
                            'admission_date' => now(),
                            'warranty_ends_at' => now()->addDays(45),
                            'description' => "Faturamento da Vaga: {$job->title} - Contratado: " . ($candidate ? $candidate->name : 'N/A'),
                            'amount' => $amount > 0 ? $amount : $transaction->amount,
                            'candidate_salary' => $salary > 0 ? $salary : $transaction->candidate_salary,
                            'commission_percentage' => $commissionPct > 0 ? $commissionPct : $transaction->commission_percentage,
                        ]);
                    } else if ($client) {
                        // Caso não exista (vagas legadas), cria um novo
                        \App\Models\FinancialTransaction::create([
                            'client_id' => $client->id,
                            'type' => 'recruitment',
                            'description' => "Contratação: " . ($candidate ? $candidate->name : 'Candidato') . " - Vaga: {$job->title}",
                            'amount' => $amount,
                            'due_date' => now()->addDays(30),
                            'admission_date' => now(),
                            'warranty_ends_at' => now()->addDays(45),
                            'job_id' => $job->id,
                            'candidate_id' => $candidate ? $candidate->id : null,
                            'candidate_contact' => $candidate ? ($candidate->phone ?? $candidate->email) : null,
                            'candidate_salary' => $salary,
                            'commission_percentage' => $commissionPct,
                            'status' => 'pending'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao auto-criar/atualizar rascunho financeiro: ' . $e->getMessage());
                }
            }
        }
        
        return response()->json([
            'message' => 'Estágio atualizado com sucesso',
            'application' => $application
        ], 200);
    }
}
