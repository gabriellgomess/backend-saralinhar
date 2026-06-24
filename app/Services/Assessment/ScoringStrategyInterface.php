<?php

namespace App\Services\Assessment;

use App\Models\AssessmentApplication;

interface ScoringStrategyInterface
{
    /**
     * Calcula os scores a partir das respostas de uma aplicação.
     *
     * Retorna um array com:
     *   - overall_score  float        Score geral normalizado 0-100
     *   - dimension_scores array      Score por dimensão: [slug => [score, classification, mean]]
     *   - quality_index  int          Índice de qualidade de resposta 0-100
     *   - flags          array        Alertas de qualidade detectados
     */
    public function calculate(AssessmentApplication $application): array;
}
