<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpenAIUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenAIUsageController extends Controller
{
    /**
     * Lista paginada de logs com filtros.
     */
    public function index(Request $request)
    {
        $query = OpenAIUsageLog::query()
            ->with(['user:id,name,email', 'recruitmentClient:id,name']);

        $this->applyFilters($query, $request);

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(5, min(100, $perPage));

        $logs = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Totais agregados por período + breakdowns por modelo e feature.
     */
    public function summary(Request $request)
    {
        $base = OpenAIUsageLog::query();
        $this->applyFilters($base, $request);

        $totals = (clone $base)
            ->selectRaw('
                COUNT(*) as calls,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success_calls,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as error_calls,
                COALESCE(SUM(input_tokens), 0) as input_tokens,
                COALESCE(SUM(output_tokens), 0) as output_tokens,
                COALESCE(SUM(total_tokens), 0) as total_tokens,
                COALESCE(SUM(cached_input_tokens), 0) as cached_input_tokens,
                COALESCE(SUM(reasoning_tokens), 0) as reasoning_tokens,
                COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd,
                COALESCE(AVG(duration_ms), 0) as avg_duration_ms
            ', [OpenAIUsageLog::STATUS_SUCCESS, OpenAIUsageLog::STATUS_ERROR])
            ->first();

        $byModel = (clone $base)
            ->select('model', DB::raw('COUNT(*) as calls'),
                DB::raw('COALESCE(SUM(input_tokens),0) as input_tokens'),
                DB::raw('COALESCE(SUM(output_tokens),0) as output_tokens'),
                DB::raw('COALESCE(SUM(total_tokens),0) as total_tokens'),
                DB::raw('COALESCE(SUM(estimated_cost_usd),0) as estimated_cost_usd'))
            ->groupBy('model')
            ->orderByDesc('estimated_cost_usd')
            ->get();

        $byFeature = (clone $base)
            ->select('feature', DB::raw('COUNT(*) as calls'),
                DB::raw('COALESCE(SUM(input_tokens),0) as input_tokens'),
                DB::raw('COALESCE(SUM(output_tokens),0) as output_tokens'),
                DB::raw('COALESCE(SUM(total_tokens),0) as total_tokens'),
                DB::raw('COALESCE(SUM(estimated_cost_usd),0) as estimated_cost_usd'))
            ->groupBy('feature')
            ->orderByDesc('estimated_cost_usd')
            ->get();

        $daily = (clone $base)
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as calls'),
                DB::raw('COALESCE(SUM(total_tokens),0) as total_tokens'),
                DB::raw('COALESCE(SUM(estimated_cost_usd),0) as estimated_cost_usd')
            )
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->limit(90)
            ->get();

        return response()->json([
            'totals' => $totals,
            'by_model' => $byModel,
            'by_feature' => $byFeature,
            'daily' => $daily,
        ]);
    }

    /**
     * Lista valores distintos para preencher dropdowns de filtro.
     */
    public function filters()
    {
        return response()->json([
            'features' => OpenAIUsageLog::query()->select('feature')->distinct()->orderBy('feature')->pluck('feature'),
            'models'   => OpenAIUsageLog::query()->select('model')->distinct()->orderBy('model')->pluck('model'),
            'statuses' => [OpenAIUsageLog::STATUS_SUCCESS, OpenAIUsageLog::STATUS_ERROR],
        ]);
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to);
        }
        if ($feature = $request->input('feature')) {
            $query->where('feature', $feature);
        }
        if ($model = $request->input('model')) {
            $query->where('model', $model);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($clientId = $request->input('recruitment_client_id')) {
            $query->where('recruitment_client_id', $clientId);
        }
    }
}
