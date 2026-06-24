<?php

namespace App\Services\Assessment;

use App\Models\AssessmentTest;
use InvalidArgumentException;

/**
 * Registry de estratégias de cálculo.
 *
 * Resolve a estratégia correta pelo campo `type` do AssessmentTest.
 * Para adicionar um novo tipo: registrar aqui, criar a classe e o seed.
 * Nenhum controller ou tela precisa ser alterado.
 */
class ScoringStrategyManager
{
    /**
     * Mapa type => classe da estratégia.
     * Adicionar aqui ao implementar novos tipos.
     */
    private array $strategies = [
        'likert'  => LikertStrategy::class,
        'climate' => LikertStrategy::class, // clima usa Likert + favorabilidade extra
        'sjt'     => SjtStrategy::class,
        'hybrid'  => LikertStrategy::class, // híbrido usa Likert como base
    ];

    /**
     * Retorna a instância da estratégia para o teste informado.
     *
     * @throws InvalidArgumentException se o tipo não tiver estratégia registrada
     */
    public function for(AssessmentTest $test): ScoringStrategyInterface
    {
        $type = $test->type;

        if (!isset($this->strategies[$type])) {
            throw new InvalidArgumentException(
                "Nenhuma estratégia de cálculo registrada para o tipo de teste \"{$type}\"."
            );
        }

        return app($this->strategies[$type]);
    }

    /**
     * Registra ou sobrescreve uma estratégia em runtime.
     * Útil para testes automatizados com estratégia mock.
     */
    public function register(string $type, string $strategyClass): self
    {
        if (!is_a($strategyClass, ScoringStrategyInterface::class, true)) {
            throw new InvalidArgumentException(
                "{$strategyClass} deve implementar ScoringStrategyInterface."
            );
        }

        $this->strategies[$type] = $strategyClass;

        return $this;
    }
}
