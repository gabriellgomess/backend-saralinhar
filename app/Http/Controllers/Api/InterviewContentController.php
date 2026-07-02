<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewArea;
use App\Models\InterviewQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Conteúdo de entrevistas do app EntrevistaPro AI.
 *
 * Rotas públicas: consumidas pelo aplicativo mobile.
 * Rotas admin: consumidas pelo painel do site-saralinhar.
 */
class InterviewContentController extends Controller
{
    // =========================================================
    // PÚBLICO (app mobile)
    // =========================================================

    /**
     * Lista áreas ativas, ordenadas
     */
    public function areas()
    {
        $areas = InterviewArea::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        return response()->json([
            'message' => 'Áreas recuperadas com sucesso',
            'data' => $areas,
        ], 200);
    }

    /**
     * Lista perguntas ativas de uma área (gerais + específicas)
     */
    public function questions($id)
    {
        $area = InterviewArea::where('is_active', true)->find($id);

        if (!$area) {
            return response()->json([
                'message' => 'Área não encontrada',
            ], 404);
        }

        $questions = InterviewQuestion::where('is_active', true)
            ->where(function ($query) use ($area) {
                $query->whereNull('interview_area_id')
                    ->orWhere('interview_area_id', $area->id);
            })
            ->orderByRaw('interview_area_id IS NOT NULL') // gerais primeiro
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'interview_area_id', 'text']);

        return response()->json([
            'message' => 'Perguntas recuperadas com sucesso',
            'data' => $questions,
        ], 200);
    }

    // =========================================================
    // ADMIN (painel do site)
    // =========================================================

    /**
     * Lista todas as áreas (incluindo inativas) com contagem de perguntas
     */
    public function areasAll()
    {
        $areas = InterviewArea::withCount('questions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $generalCount = InterviewQuestion::whereNull('interview_area_id')->count();

        return response()->json([
            'message' => 'Áreas recuperadas com sucesso',
            'data' => $areas,
            'general_questions_count' => $generalCount,
        ], 200);
    }

    public function storeArea(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:interview_areas,name',
                'icon' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            $validated['slug'] = Str::slug($validated['name']);

            $area = InterviewArea::create($validated);

            return response()->json([
                'message' => 'Área criada com sucesso',
                'data' => $area,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function updateArea(Request $request, $id)
    {
        $area = InterviewArea::find($id);

        if (!$area) {
            return response()->json(['message' => 'Área não encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:interview_areas,name,' . $id,
                'icon' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            if ($validated['name'] !== $area->name) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $area->update($validated);

            return response()->json([
                'message' => 'Área atualizada com sucesso',
                'data' => $area,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroyArea($id)
    {
        $area = InterviewArea::find($id);

        if (!$area) {
            return response()->json(['message' => 'Área não encontrada'], 404);
        }

        $questionsCount = $area->questions()->count();
        $area->delete(); // perguntas específicas caem junto (cascade)

        return response()->json([
            'message' => $questionsCount > 0
                ? "Área deletada com sucesso ({$questionsCount} pergunta(s) específica(s) removida(s) junto)"
                : 'Área deletada com sucesso',
        ], 200);
    }

    /**
     * Lista perguntas para o painel.
     * ?area_id=X  -> específicas da área X
     * ?general=1  -> apenas gerais
     * (sem filtro) -> todas, com a área carregada
     */
    public function questionsAll(Request $request)
    {
        $query = InterviewQuestion::with('area:id,name')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($request->boolean('general')) {
            $query->whereNull('interview_area_id');
        } elseif ($request->filled('area_id')) {
            $query->where('interview_area_id', $request->input('area_id'));
        }

        return response()->json([
            'message' => 'Perguntas recuperadas com sucesso',
            'data' => $query->get(),
        ], 200);
    }

    public function storeQuestion(Request $request)
    {
        try {
            $validated = $request->validate([
                'text' => 'required|string|max:1000',
                'interview_area_id' => 'nullable|exists:interview_areas,id',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            $question = InterviewQuestion::create($validated);
            $question->load('area:id,name');

            return response()->json([
                'message' => 'Pergunta criada com sucesso',
                'data' => $question,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function updateQuestion(Request $request, $id)
    {
        $question = InterviewQuestion::find($id);

        if (!$question) {
            return response()->json(['message' => 'Pergunta não encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'text' => 'required|string|max:1000',
                'interview_area_id' => 'nullable|exists:interview_areas,id',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            $question->update($validated);
            $question->load('area:id,name');

            return response()->json([
                'message' => 'Pergunta atualizada com sucesso',
                'data' => $question,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroyQuestion($id)
    {
        $question = InterviewQuestion::find($id);

        if (!$question) {
            return response()->json(['message' => 'Pergunta não encontrada'], 404);
        }

        $question->delete();

        return response()->json([
            'message' => 'Pergunta deletada com sucesso',
        ], 200);
    }
}
