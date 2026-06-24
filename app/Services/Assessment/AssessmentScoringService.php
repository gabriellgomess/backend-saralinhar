<?php

namespace App\Services\Assessment;

use App\Models\AssessmentApplication;
use App\Models\AssessmentResult;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

/**
 * Orquestra o cálculo e a persistência do resultado de uma aplicação.
 *
 * Uso:
 *   app(AssessmentScoringService::class)->score($application);
 */
class AssessmentScoringService
{
    public function __construct(
        private readonly ScoringStrategyManager $manager,
        private readonly OpenAIService $openAI,
    ) {}

    /**
     * Calcula e persiste o resultado da aplicação.
     * Se já existir um resultado, ele é substituído.
     */
    public function score(AssessmentApplication $application): AssessmentResult
    {
        $application->loadMissing('test');

        $strategy = $this->manager->for($application->test);
        $data     = $strategy->calculate($application);

        // Gera narrativa via IA (best-effort: falha silenciosa, não bloqueia o score)
        $narrative = null;
        try {
            $narrative = $this->openAI->generateAssessmentNarrative([
                'test_name'       => $application->test->name,
                'respondent_name' => $application->respondent_name,
                'overall_score'   => $data['overall_score'],
                'quality_index'   => $data['quality_index'],
                'dimension_scores'=> $data['dimension_scores'],
            ]);
        } catch (\Exception $e) {
            Log::warning('Assessment IA narrative failed (non-blocking)', ['error' => $e->getMessage()]);
        }

        $result = AssessmentResult::updateOrCreate(
            ['assessment_application_id' => $application->id],
            [
                'overall_score'    => $data['overall_score'],
                'dimension_scores' => $data['dimension_scores'],
                'quality_index'    => $data['quality_index'],
                'flags'            => $data['flags'],
                'ai_narrative'     => $narrative,
                'calculated_at'    => now(),
            ]
        );

        $application->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        Log::info('Resultado de assessment calculado', [
            'application_id' => $application->id,
            'test_slug'      => $application->test->slug,
            'overall_score'  => $data['overall_score'],
            'quality_index'  => $data['quality_index'],
            'ai_narrative'   => $narrative ? 'gerada' : 'indisponível',
        ]);

        return $result;
    }
}
