<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssessmentBuilderController extends Controller
{
    // =========================================================================
    // Testes
    // =========================================================================

    /**
     * GET /api/assessments/builder
     * Lista todos os testes com contagem de dimensões e questões.
     */
    public function index(): JsonResponse
    {
        $tests = AssessmentTest::withCount(['dimensions', 'questions', 'applications'])
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $tests]);
    }

    /**
     * GET /api/assessments/builder/{id}
     * Retorna o teste completo com dimensões e questões.
     */
    public function show(int $id): JsonResponse
    {
        $test = AssessmentTest::with([
            'dimensions' => fn($q) => $q->orderBy('order'),
            'dimensions.questions' => fn($q) => $q->orderBy('order'),
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $test]);
    }

    /**
     * POST /api/assessments/builder
     * Cria um novo instrumento.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'slug'        => 'nullable|string|max:100|unique:assessment_tests,slug',
            'description' => 'nullable|string',
            'type'        => 'required|in:likert,sjt,hybrid,climate',
            'version'     => 'nullable|string|max:20',
            'disclaimer'  => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        // Garante unicidade do slug
        $base  = $data['slug'];
        $count = 1;
        while (AssessmentTest::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $base . '-' . $count++;
        }

        $test = AssessmentTest::create($data);

        return response()->json(['success' => true, 'data' => $test], 201);
    }

    /**
     * PUT /api/assessments/builder/{id}
     * Atualiza as informações do instrumento.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $test = AssessmentTest::findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'slug'        => "nullable|string|max:100|unique:assessment_tests,slug,{$id}",
            'description' => 'nullable|string',
            'type'        => 'required|in:likert,sjt,hybrid,climate',
            'version'     => 'nullable|string|max:20',
            'disclaimer'  => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $test->update($data);

        return response()->json(['success' => true, 'data' => $test]);
    }

    /**
     * PATCH /api/assessments/builder/{id}/toggle
     * Ativa ou desativa o instrumento.
     */
    public function toggle(int $id): JsonResponse
    {
        $test = AssessmentTest::findOrFail($id);
        $test->update(['is_active' => !$test->is_active]);

        return response()->json(['success' => true, 'data' => $test]);
    }

    /**
     * DELETE /api/assessments/builder/{id}
     * Exclui o instrumento se não houver aplicações vinculadas.
     */
    public function destroy(int $id): JsonResponse
    {
        $test = AssessmentTest::withCount('applications')->findOrFail($id);

        if ($test->applications_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Não é possível excluir: este instrumento possui {$test->applications_count} aplicação(ões) registrada(s).",
            ], 422);
        }

        $test->delete();

        return response()->json(['success' => true, 'message' => 'Instrumento excluído.']);
    }

    // =========================================================================
    // Dimensões
    // =========================================================================

    /**
     * POST /api/assessments/builder/{id}/dimensions
     */
    public function storeDimension(Request $request, int $id): JsonResponse
    {
        $test = AssessmentTest::findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'slug'        => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'weight'      => 'nullable|numeric|min:0',
            'order'       => 'nullable|integer|min:0',
        ]);

        $data['assessment_test_id'] = $test->id;
        $data['slug']  = $data['slug'] ?? Str::slug($data['name']);
        $data['weight'] = $data['weight'] ?? 1.0;

        // Garante unicidade do slug dentro do teste
        $base  = $data['slug'];
        $count = 1;
        while (AssessmentDimension::where('assessment_test_id', $id)->where('slug', $data['slug'])->exists()) {
            $data['slug'] = $base . '-' . $count++;
        }

        // Order padrão = próximo na sequência
        if (!isset($data['order'])) {
            $data['order'] = AssessmentDimension::where('assessment_test_id', $id)->max('order') + 1;
        }

        $dim = AssessmentDimension::create($data);

        return response()->json(['success' => true, 'data' => $dim], 201);
    }

    /**
     * PUT /api/assessments/builder/{id}/dimensions/{dimId}
     */
    public function updateDimension(Request $request, int $id, int $dimId): JsonResponse
    {
        $dim = AssessmentDimension::where('assessment_test_id', $id)->findOrFail($dimId);

        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'slug'        => "nullable|string|max:100",
            'description' => 'nullable|string',
            'weight'      => 'nullable|numeric|min:0',
            'order'       => 'nullable|integer|min:0',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $dim->update($data);

        return response()->json(['success' => true, 'data' => $dim]);
    }

    /**
     * DELETE /api/assessments/builder/{id}/dimensions/{dimId}
     */
    public function destroyDimension(int $id, int $dimId): JsonResponse
    {
        $dim = AssessmentDimension::where('assessment_test_id', $id)->findOrFail($dimId);

        $qCount = AssessmentQuestion::where('assessment_dimension_id', $dimId)->count();
        if ($qCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Remova as {$qCount} questão(ões) desta dimensão antes de excluí-la.",
            ], 422);
        }

        $dim->delete();

        return response()->json(['success' => true, 'message' => 'Dimensão excluída.']);
    }

    // =========================================================================
    // Questões
    // =========================================================================

    /**
     * POST /api/assessments/builder/{id}/questions
     */
    public function storeQuestion(Request $request, int $id): JsonResponse
    {
        $test = AssessmentTest::findOrFail($id);

        $data = $request->validate([
            'assessment_dimension_id' => "required|integer|exists:assessment_dimensions,id",
            'statement'               => 'required|string',
            'code'                    => 'nullable|string|max:20',
            'question_type'           => 'nullable|in:likert,single_choice,sjt_pair',
            'scale_min'               => 'nullable|integer',
            'scale_max'               => 'nullable|integer',
            'is_reverse'              => 'boolean',
            'is_attention_check'      => 'boolean',
            'weight'                  => 'nullable|numeric|min:0',
            'order'                   => 'nullable|integer|min:0',
        ]);

        $data['assessment_test_id'] = $test->id;
        $data['question_type']  ??= 'likert';
        $data['scale_min']      ??= 1;
        $data['scale_max']      ??= 5;
        $data['weight']         ??= 1.0;

        // Auto-gera código se não informado
        if (empty($data['code'])) {
            $initials = $this->testInitials($test->name);
            $next     = AssessmentQuestion::where('assessment_test_id', $id)->count() + 1;
            $data['code'] = $initials . str_pad($next, 2, '0', STR_PAD_LEFT);
        }

        // Order padrão = próximo na sequência
        if (!isset($data['order'])) {
            $data['order'] = AssessmentQuestion::where('assessment_test_id', $id)->max('order') + 1;
        }

        $question = AssessmentQuestion::create($data);
        $question->load('dimension:id,name,slug');

        return response()->json(['success' => true, 'data' => $question], 201);
    }

    /**
     * PUT /api/assessments/builder/{id}/questions/{qId}
     */
    public function updateQuestion(Request $request, int $id, int $qId): JsonResponse
    {
        $question = AssessmentQuestion::where('assessment_test_id', $id)->findOrFail($qId);

        $data = $request->validate([
            'assessment_dimension_id' => "nullable|integer|exists:assessment_dimensions,id",
            'statement'               => 'required|string',
            'code'                    => 'nullable|string|max:20',
            'question_type'           => 'nullable|in:likert,single_choice,sjt_pair',
            'scale_min'               => 'nullable|integer',
            'scale_max'               => 'nullable|integer',
            'is_reverse'              => 'boolean',
            'is_attention_check'      => 'boolean',
            'weight'                  => 'nullable|numeric|min:0',
            'order'                   => 'nullable|integer|min:0',
        ]);

        $question->update($data);
        $question->load('dimension:id,name,slug');

        return response()->json(['success' => true, 'data' => $question]);
    }

    /**
     * DELETE /api/assessments/builder/{id}/questions/{qId}
     */
    public function destroyQuestion(int $id, int $qId): JsonResponse
    {
        $question = AssessmentQuestion::where('assessment_test_id', $id)->findOrFail($qId);
        $question->delete();

        return response()->json(['success' => true, 'message' => 'Questão excluída.']);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Gera iniciais em maiúsculas a partir do nome do instrumento. */
    private function testInitials(string $name): string
    {
        $words    = preg_split('/[\s\-_]+/', $name);
        $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), $words));
        return substr($initials, 0, 3) ?: 'Q';
    }
}
