<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestAuditLog;
use App\Models\RecruitmentClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestAuditLogController extends Controller
{
    /**
     * Lista paginada de logs de auditoria com filtros.
     */
    public function index(Request $request)
    {
        $query = TestAuditLog::query()
            ->with(['user:id,name,email', 'recruitmentClient:id,name']);

        $this->applyFilters($query, $request);

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(5, min(100, $perPage));

        $logs = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Opções de filtros distinct para os menus de seleção no frontend.
     */
    public function filters()
    {
        // Pega as ações reais que existem no banco
        $actions = TestAuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        
        // Pega os tipos de teste reais
        $testTypes = TestAuditLog::query()->select('test_type')->distinct()->orderBy('test_type')->pluck('test_type');

        // Pega todos os clientes para filtro
        $clients = RecruitmentClient::query()->select('id', 'name')->orderBy('name')->get();

        // Pega os usuários que executaram ações de auditoria
        $userIds = TestAuditLog::query()->whereNotNull('user_id')->select('user_id')->distinct()->pluck('user_id');
        $users = User::query()->whereIn('id', $userIds)->select('id', 'name', 'email')->orderBy('name')->get();

        return response()->json([
            'actions' => $actions,
            'test_types' => $testTypes,
            'clients' => $clients,
            'users' => $users,
        ]);
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($from = $request->input('from')) {
            // Garante início do dia
            if (strlen($from) === 10) {
                $from .= ' 00:00:00';
            }
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            // Garante fim do dia
            if (strlen($to) === 10) {
                $to .= ' 23:59:59';
            }
            $query->where('created_at', '<=', $to);
        }
        if ($testType = $request->input('test_type')) {
            $query->where('test_type', $testType);
        }
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($clientId = $request->input('recruitment_client_id')) {
            $query->where('recruitment_client_id', $clientId);
        }
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('metadata', 'LIKE', '%' . $search . '%')
                  ->orWhere('ip_address', 'LIKE', '%' . $search . '%')
                  ->orWhere('user_agent', 'LIKE', '%' . $search . '%');
            });
        }
    }
}
