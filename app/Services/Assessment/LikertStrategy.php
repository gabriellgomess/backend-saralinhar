<?php

namespace App\Services\Assessment;

use App\Models\AssessmentApplication;
use App\Models\AssessmentResponse;
use Illuminate\Support\Collection;

/**
 * Estratégia de cálculo para instrumentos Likert (escala 1-5).
 *
 * Fórmulas aplicadas (spec §5):
 *   resposta_ajustada = is_reverse ? (scale_max + 1 - raw) : raw
 *   media_dimensao    = soma(ajustada * peso_item) / soma(peso_item)
 *   score_dimensao    = ((media_dimensao - scale_min) / (scale_max - scale_min)) * 100
 *   score_geral       = soma(score_dim * peso_dim) / soma(peso_dim)
 */
class LikertStrategy implements ScoringStrategyInterface
{
    public function calculate(AssessmentApplication $application): array
    {
        $application->load([
            'test.dimensions',
            'responses.question.dimension',
        ]);

        $responses = $application->responses;

        $dimensionScores = $this->calculateDimensionScores($application, $responses);
        $overallScore    = $this->calculateOverallScore($application, $dimensionScores);
        [$qualityIndex, $flags] = $this->calculateQualityIndex($application, $responses);

        return [
            'overall_score'    => round($overallScore, 2),
            'dimension_scores' => $dimensionScores,
            'quality_index'    => $qualityIndex,
            'flags'            => $flags,
        ];
    }

    // -------------------------------------------------------------------------
    // Score por dimensão
    // -------------------------------------------------------------------------

    private function calculateDimensionScores(AssessmentApplication $application, Collection $responses): array
    {
        $dimensions = $application->test->dimensions;
        $scores     = [];

        foreach ($dimensions as $dimension) {
            $dimResponses = $responses->filter(
                fn(AssessmentResponse $r) => $r->question->assessment_dimension_id === $dimension->id
                    && $r->numeric_answer !== null
            );

            if ($dimResponses->isEmpty()) {
                continue;
            }

            $weightedSum  = 0.0;
            $weightTotal  = 0.0;
            $scaleMin     = null;
            $scaleMax     = null;

            foreach ($dimResponses as $response) {
                $question    = $response->question;
                $adjusted    = $question->adjustedValue($response->numeric_answer);
                $itemWeight  = $question->weight ?? 1.0;

                $weightedSum += $adjusted * $itemWeight;
                $weightTotal += $itemWeight;

                // usa a escala da primeira pergunta da dimensão como referência
                $scaleMin ??= $question->scale_min;
                $scaleMax ??= $question->scale_max;
            }

            $mean  = $weightTotal > 0 ? $weightedSum / $weightTotal : 0;
            $range = ($scaleMax - $scaleMin) ?: 4;
            $score = round((($mean - $scaleMin) / $range) * 100, 2);
            $score = max(0.0, min(100.0, $score));

            $scores[$dimension->slug] = [
                'name'           => $dimension->name,
                'score'          => $score,
                'mean'           => round($mean, 2),
                'weight'         => $dimension->weight,
                'classification' => $this->classify($score),
                'item_count'     => $dimResponses->count(),
            ];
        }

        return $scores;
    }

    // -------------------------------------------------------------------------
    // Score geral ponderado pelas dimensões
    // -------------------------------------------------------------------------

    private function calculateOverallScore(AssessmentApplication $application, array $dimensionScores): float
    {
        $dimensions  = $application->test->dimensions->keyBy('slug');
        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($dimensionScores as $slug => $data) {
            $dimWeight    = $dimensions[$slug]?->weight ?? 1.0;
            $weightedSum += $data['score'] * $dimWeight;
            $weightTotal += $dimWeight;
        }

        return $weightTotal > 0 ? $weightedSum / $weightTotal : 0.0;
    }

    // -------------------------------------------------------------------------
    // Índice de qualidade de resposta (spec §5.7 – 5.9)
    // -------------------------------------------------------------------------

    private function calculateQualityIndex(AssessmentApplication $application, Collection $responses): array
    {
        $flags      = [];
        $penaltySum = 0;

        // Penalidade por respostas repetidas em excesso
        $numericAnswers = $responses
            ->filter(fn($r) => $r->numeric_answer !== null)
            ->pluck('numeric_answer');

        if ($numericAnswers->count() > 0) {
            $maxRepeat    = $numericAnswers->countBy()->max();
            $repeatRate   = $maxRepeat / $numericAnswers->count();

            if ($repeatRate >= 0.95) {
                $penaltySum += 35;
                $flags[]     = 'repetition_very_high';
            } elseif ($repeatRate >= 0.85) {
                $penaltySum += 20;
                $flags[]     = 'repetition_high';
            }
        }

        // Penalidade por tempo total muito baixo
        $totalTime      = $responses->sum('response_time_seconds');
        $itemCount      = $responses->count();
        $minExpected    = $itemCount * 4; // 4 s por item

        if ($itemCount > 0 && $totalTime > 0) {
            $timeFraction = $totalTime / $minExpected;
            if ($timeFraction < 0.50) {
                $penaltySum += 25;
                $flags[]     = 'time_very_low';
            } elseif ($timeFraction < 0.70) {
                $penaltySum += 10;
                $flags[]     = 'time_low';
            }
        }

        $qualityIndex = max(0, 100 - $penaltySum);

        return [$qualityIndex, $flags];
    }

    // -------------------------------------------------------------------------
    // Classificação textual (spec §5.5)
    // -------------------------------------------------------------------------

    private function classify(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Forte evidência comportamental percebida',
            $score >= 60 => 'Evidência adequada',
            $score >= 40 => 'Em desenvolvimento',
            default      => 'Baixa evidência comportamental percebida',
        };
    }
}
