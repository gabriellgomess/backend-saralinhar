<?php

namespace App\Services\Assessment;

use App\Models\AssessmentApplication;
use App\Models\AssessmentOption;
use App\Models\AssessmentResponse;
use Illuminate\Support\Collection;

/**
 * Estratégia de cálculo para Teste Situacional Profissional (SJT).
 *
 * Suporta três formatos de resposta (spec §9.3):
 *
 * Formato A — single_choice
 *   score_cenario = score_alternativa_escolhida
 *
 * Formato B — sjt_pair (melhor + pior)
 *   score_melhor  = score_alternativa_melhor
 *   score_pior    = 100 - score_alternativa_pior
 *   score_cenario = (score_melhor + score_pior) / 2
 *
 * Formato C — ranking
 *   F     = soma |ranking_usuario_i - ranking_especialista_i|
 *   Fmax  = floor(n² / 2)
 *   score = 100 * (1 - F / Fmax)
 *
 * Score por dimensão = média dos cenários da dimensão.
 * Score geral        = média ponderada das dimensões.
 */
class SjtStrategy implements ScoringStrategyInterface
{
    public function calculate(AssessmentApplication $application): array
    {
        $application->load([
            'test.dimensions',
            'responses.question.dimension',
            'responses.question.options',
            'responses.option',
        ]);

        $responses       = $application->responses;
        $dimensionScores = $this->calculateDimensionScores($application, $responses);
        $overallScore    = $this->calculateOverallScore($application, $dimensionScores);
        [$qualityIndex, $flags] = $this->calculateQualityIndex($responses);

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
            );

            if ($dimResponses->isEmpty()) {
                continue;
            }

            $scenarioScores = $dimResponses->map(
                fn(AssessmentResponse $r) => $this->scoreScenario($r)
            )->filter(fn($s) => $s !== null);

            if ($scenarioScores->isEmpty()) {
                continue;
            }

            $score = round($scenarioScores->avg(), 2);

            $scores[$dimension->slug] = [
                'name'           => $dimension->name,
                'score'          => $score,
                'mean'           => $score,
                'weight'         => $dimension->weight,
                'classification' => $this->classify($score),
                'item_count'     => $scenarioScores->count(),
            ];
        }

        return $scores;
    }

    // -------------------------------------------------------------------------
    // Score de um cenário (despacha para o formato correto)
    // -------------------------------------------------------------------------

    private function scoreScenario(AssessmentResponse $response): ?float
    {
        $questionType = $response->question->question_type;

        return match ($questionType) {
            'single_choice' => $this->scoreSingleChoice($response),
            'sjt_pair'      => $this->scoreSjtPair($response),
            'ranking'       => $this->scoreRanking($response),
            default         => null,
        };
    }

    /** Formato A: score direto da opção escolhida */
    private function scoreSingleChoice(AssessmentResponse $response): ?float
    {
        if (!$response->option) {
            return null;
        }

        return (float) $response->option->withScore()->score;
    }

    /** Formato B: (score_melhor + (100 - score_pior)) / 2 */
    private function scoreSjtPair(AssessmentResponse $response): ?float
    {
        $pair = $response->sjt_pair_json;

        if (empty($pair['best_option_id']) || empty($pair['worst_option_id'])) {
            return null;
        }

        $options    = $response->question->options->keyBy('id');
        $bestOption = $options->get($pair['best_option_id']);
        $worstOption = $options->get($pair['worst_option_id']);

        if (!$bestOption || !$worstOption) {
            return null;
        }

        $scoreBest  = $bestOption->withScore()->score;
        $scoreWorst = $worstOption->withScore()->score;

        return ($scoreBest + (100 - $scoreWorst)) / 2;
    }

    /**
     * Formato C: distância entre o ranking do respondente e o ranking de referência.
     * O ranking de referência é a ordem das opções por score DESC.
     */
    private function scoreRanking(AssessmentResponse $response): ?float
    {
        $rankingUser = $response->ranking_json; // [option_id, option_id, ...]

        if (empty($rankingUser)) {
            return null;
        }

        // Monta ranking de referência (ordem de score decrescente)
        $options = $response->question->options->sortByDesc(
            fn($o) => $o->withScore()->score
        )->values();

        $expertRanking = $options->pluck('id')->toArray();
        $n             = count($expertRanking);

        if ($n < 2) {
            return null;
        }

        // Índices: posição no ranking de referência por option_id
        $expertPosition = array_flip($expertRanking); // [option_id => position_0based]

        $F = 0;
        foreach ($rankingUser as $userPosition => $optionId) {
            $expertPos = $expertPosition[$optionId] ?? null;
            if ($expertPos === null) {
                continue;
            }
            $F += abs($userPosition - $expertPos);
        }

        $Fmax = (int) floor(($n ** 2) / 2);

        if ($Fmax === 0) {
            return 100.0;
        }

        return max(0.0, round(100 * (1 - $F / $Fmax), 2));
    }

    // -------------------------------------------------------------------------
    // Score geral
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
    // Índice de qualidade
    // -------------------------------------------------------------------------

    private function calculateQualityIndex(Collection $responses): array
    {
        $flags      = [];
        $penaltySum = 0;

        // Penalidade por tempo total muito baixo
        $totalTime   = $responses->sum('response_time_seconds');
        $itemCount   = $responses->count();
        $minExpected = $itemCount * 4;

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

        return [max(0, 100 - $penaltySum), $flags];
    }

    // -------------------------------------------------------------------------
    // Classificação textual
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
