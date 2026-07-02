<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppJobApplication;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD de candidaturas pessoais do candidato (app EntrevistaPro AI).
 * Todas as operações são restritas ao usuário autenticado.
 */
class AppApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = AppJobApplication::where('user_id', $request->user()->id)
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Candidaturas recuperadas com sucesso',
            'data' => $applications,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['user_id'] = $request->user()->id;

        $application = AppJobApplication::create($validated);

        return response()->json([
            'message' => 'Candidatura criada com sucesso',
            'data' => $application,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $application = $this->findOwned($request, $id);

        if (!$application) {
            return response()->json(['message' => 'Candidatura não encontrada'], 404);
        }

        $application->update($this->validated($request));

        return response()->json([
            'message' => 'Candidatura atualizada com sucesso',
            'data' => $application->fresh(),
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $application = $this->findOwned($request, $id);

        if (!$application) {
            return response()->json(['message' => 'Candidatura não encontrada'], 404);
        }

        $application->delete();

        return response()->json([
            'message' => 'Candidatura removida com sucesso',
        ], 200);
    }

    private function findOwned(Request $request, $id): ?AppJobApplication
    {
        return AppJobApplication::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'company' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'applied_at' => 'required|date_format:Y-m-d',
            'status' => ['required', Rule::in(AppJobApplication::STATUSES)],
        ]);
    }
}
