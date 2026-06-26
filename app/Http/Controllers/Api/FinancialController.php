<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialService;
use App\Models\FinancialTransaction;
use App\Models\FinancialRecruiterCommission;
use App\Models\RecruitmentClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinancialController extends Controller
{
    /**
     * Retorna as estatísticas financeiras do Dashboard.
     */
    public function dashboardStats()
    {
        $isAdmin = Auth::user()->role === 'admin';
        $userId = Auth::id();

        if ($isAdmin) {
            $stats = [
                'total_invoiced' => (float) FinancialTransaction::where('status', 'paid')->sum('amount'),
                'total_pending'  => (float) FinancialTransaction::where('status', 'pending')->sum('amount'),
                'recruiter_payouts_pending' => (float) FinancialRecruiterCommission::where('status', 'pending')->sum('amount'),
                'receivables_by_month' => FinancialTransaction::selectRaw("DATE_FORMAT(due_date, '%Y-%m') as month, sum(amount) as total")
                    ->groupBy('month')->orderBy('month')->get()->map(function ($item) {
                        return [
                            'month' => $item->month,
                            'total' => (float) $item->total
                        ];
                    }),
                'top_clients' => FinancialTransaction::selectRaw('client_id, sum(amount) as total')
                    ->with('client:id,name')
                    ->where('status', 'paid')
                    ->groupBy('client_id')->orderByDesc('total')->limit(5)->get()->map(function ($item) {
                        return [
                            'client_name' => $item->client ? $item->client->name : 'N/A',
                            'total' => (float) $item->total
                        ];
                    })
            ];
        } else {
            // Recrutadores comuns veem apenas os seus próprios ganhos
            $stats = [
                'total_earned' => (float) FinancialRecruiterCommission::where('user_id', $userId)->where('status', 'paid')->sum('amount'),
                'total_pending' => (float) FinancialRecruiterCommission::where('user_id', $userId)->where('status', 'pending')->sum('amount'),
                'monthly_earnings' => FinancialRecruiterCommission::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, sum(amount) as total")
                    ->where('user_id', $userId)->groupBy('month')->orderBy('month')->get()->map(function ($item) {
                        return [
                            'month' => $item->month,
                            'total' => (float) $item->total
                        ];
                    })
            ];
        }

        return response()->json($stats, 200);
    }

    /**
     * Listar todos os lançamentos/faturamentos (apenas Admin).
     */
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $query = FinancialTransaction::with(['client:id,name', 'job:id,title', 'candidate:id,name', 'recruiterCommissions.user:id,name']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->orderByDesc('due_date')->paginate($request->input('per_page', 15)), 200);
    }

    /**
     * Criar um lançamento manualmente com os splits dos recrutadores (apenas Admin).
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:recruitment_clients,id',
            'type' => 'required|in:recruitment,service',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'admission_date' => 'nullable|date',
            'warranty_ends_at' => 'nullable|date',
            'is_warranty_replacement' => 'nullable|boolean',
            'job_id' => 'nullable|exists:job_listings,id',
            'candidate_id' => 'nullable|exists:candidates,id',
            'candidate_contact' => 'nullable|string|max:255',
            'candidate_salary' => 'nullable|numeric',
            'commission_percentage' => 'nullable|numeric',
            'financial_service_id' => 'nullable|exists:financial_services,id',
            'recruiters' => 'nullable|array',
            'recruiters.*.user_id' => 'required|exists:users,id',
            'recruiters.*.amount' => 'required|numeric|min:0',
            'recruiters.*.percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        return DB::transaction(function () use ($validated) {
            $transaction = FinancialTransaction::create($validated);

            if (!empty($validated['recruiters'])) {
                foreach ($validated['recruiters'] as $recruiter) {
                    $transaction->recruiterCommissions()->create([
                        'user_id' => $recruiter['user_id'],
                        'amount' => $recruiter['amount'],
                        'percentage' => $recruiter['percentage'] ?? null,
                    ]);
                }
            }

            return response()->json($transaction->load('recruiterCommissions.user'), 201);
        });
    }

    /**
     * Exibir detalhes de um lançamento específico.
     */
    public function show($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $transaction = FinancialTransaction::with(['client', 'job', 'candidate', 'recruiterCommissions.user'])->findOrFail($id);
        return response()->json($transaction, 200);
    }

    /**
     * Atualizar dados de um lançamento e sincronizar comissões (apenas Admin).
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $transaction = FinancialTransaction::findOrFail($id);
        $validated = $request->validate([
            'description' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'due_date' => 'sometimes|required|date',
            'admission_date' => 'nullable|date',
            'warranty_ends_at' => 'nullable|date',
            'is_warranty_replacement' => 'nullable|boolean',
            'status' => 'sometimes|required|in:pending,paid,cancelled',
            'payment_date' => 'nullable|date',
            'candidate_id' => 'nullable|exists:candidates,id',
            'candidate_contact' => 'nullable|string|max:255',
            'candidate_salary' => 'nullable|numeric',
            'commission_percentage' => 'nullable|numeric',
            'recruiters' => 'nullable|array',
            'recruiters.*.user_id' => 'required|exists:users,id',
            'recruiters.*.amount' => 'required|numeric|min:0',
            'recruiters.*.percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        return DB::transaction(function () use ($transaction, $validated) {
            $transaction->update($validated);

            if (isset($validated['recruiters'])) {
                // Remove as comissões anteriores e cria as novas atualizadas
                $transaction->recruiterCommissions()->delete();
                foreach ($validated['recruiters'] as $recruiter) {
                    $transaction->recruiterCommissions()->create([
                        'user_id' => $recruiter['user_id'],
                        'amount' => $recruiter['amount'],
                        'percentage' => $recruiter['percentage'] ?? null,
                        'status' => $transaction->status === 'paid' ? 'paid' : 'pending',
                        'payment_date' => $transaction->status === 'paid' ? ($transaction->payment_date ?: now()) : null
                    ]);
                }
            } else if ($transaction->wasChanged('status') && $transaction->status === 'paid') {
                // Se a transação foi paga e as comissões não foram enviadas novamente, marca as comissões como pagas
                $transaction->recruiterCommissions()->update([
                    'status' => 'paid',
                    'payment_date' => $transaction->payment_date ?: now()
                ]);
            }

            return response()->json($transaction->load('recruiterCommissions.user'), 200);
        });
    }

    /**
     * Excluir um lançamento (apenas Admin).
     */
    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        FinancialTransaction::findOrFail($id)->delete();
        return response()->json(['message' => 'Lançamento removido com sucesso'], 200);
    }

    /**
     * Listar comissões dos recrutadores (Recrutadores veem apenas as suas próprias, Admin vê todas).
     */
    public function listCommissions(Request $request)
    {
        $isAdmin = Auth::user()->role === 'admin';
        $userId = Auth::id();

        $query = FinancialRecruiterCommission::with(['user:id,name', 'transaction.client:id,name']);

        if (!$isAdmin) {
            $query->where('user_id', $userId);
        } else if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderByDesc('created_at')->paginate($request->input('per_page', 15)), 200);
    }

    /**
     * Atualizar o status de pagamento da comissão de um recrutador (apenas Admin).
     */
    public function updateCommissionStatus(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $commission = FinancialRecruiterCommission::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
            'payment_date' => 'nullable|date'
        ]);

        $commission->update([
            'status' => $request->status,
            'payment_date' => $request->status === 'paid' ? ($request->payment_date ?? now()) : null
        ]);

        return response()->json($commission, 200);
    }

    /**
     * Listar catálogo de serviços extras.
     */
    public function listServices()
    {
        return response()->json(FinancialService::orderBy('name')->get(), 200);
    }

    /**
     * Criar novo tipo de serviço extra (apenas Admin).
     */
    public function storeService(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_value' => 'required|numeric|min:0',
        ]);

        return response()->json(FinancialService::create($validated), 201);
    }

    /**
     * Editar tipo de serviço extra (apenas Admin).
     */
    public function updateService(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $service = FinancialService::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'default_value' => 'sometimes|required|numeric|min:0',
        ]);

        $service->update($validated);
        return response()->json($service, 200);
    }

    /**
     * Deletar tipo de serviço extra (apenas Admin).
     */
    public function destroyService($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        FinancialService::findOrFail($id)->delete();
        return response()->json(['message' => 'Serviço deletado com sucesso'], 200);
    }
}
