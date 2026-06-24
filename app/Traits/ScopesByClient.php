<?php

namespace App\Traits;

use App\Models\CandidateJobApplication;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para aplicar scoping de visibilidade por RecruitmentClient.
 *
 * Regras (sempre baseadas em auth()->user()):
 *  - admin / operational  → veem TUDO (nenhum filtro adicional aplicado).
 *  - client com recruitment_client_id → veem itens criados por qualquer
 *    usuário pertencente ao mesmo RecruitmentClient.
 *  - client sem recruitment_client_id → fallback restritivo: só vê o que ele
 *    mesmo criou (proteção contra configuração incorreta).
 *  - Demais roles (company, candidate, etc) → só veem o que criaram.
 */
trait ScopesByClient
{
    /**
     * Aplica scoping simples por user_id (ou coluna equivalente) na query.
     * Usar para listagens de TOKENS e outros recursos cuja visibilidade é
     * determinada exclusivamente pelo criador.
     */
    protected function applyClientScope(Builder $query, string $userIdColumn = 'user_id'): Builder
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return $query;
        }

        if (in_array($authUser->role, ['admin', 'operational'], true)) {
            return $query;
        }

        if ($authUser->role === 'client') {
            if ($authUser->recruitment_client_id) {
                $clientUserIds = $this->getClientUserIds($authUser->recruitment_client_id);
                return $query->where(function ($q) use ($clientUserIds, $userIdColumn, $authUser) {
                    $q->whereIn($userIdColumn, $clientUserIds)
                      ->orWhere('recruitment_client_id', $authUser->recruitment_client_id);
                });
            }
            return $query->where($userIdColumn, $authUser->id);
        }

        return $query->where($userIdColumn, $authUser->id);
    }

    /**
     * Aplica scoping por user_id + inclusão de testes vinculados a candidatos
     * de vagas do cliente (mesmo que criados por user externo, ex: admin).
     *
     * Usar para listagens de RESULTADOS de teste.
     */
    protected function applyClientScopeWithLinkedCandidates(
        Builder $query,
        string $userIdColumn = 'user_id',
        string $emailColumn = 'testee_email'
    ): Builder {
        $authUser = auth()->user();

        if (!$authUser) {
            return $query;
        }

        if (in_array($authUser->role, ['admin', 'operational'], true)) {
            return $query;
        }

        if ($authUser->role === 'client' && $authUser->recruitment_client_id) {
            $clientUserIds = $this->getClientUserIds($authUser->recruitment_client_id);
            $linkedEmails  = $this->getClientLinkedCandidateEmails($clientUserIds);
            $modelClass    = $query->getModel();

            return $query->where(function ($q) use ($userIdColumn, $clientUserIds, $emailColumn, $linkedEmails, $authUser, $modelClass) {
                $q->whereIn($userIdColumn, $clientUserIds);
                if (!empty($linkedEmails)) {
                    $q->orWhereIn($emailColumn, $linkedEmails);
                }

                if ($modelClass instanceof \App\Models\DiscTestResult) {
                    $q->orWhereHas('discTestToken', function ($tokenQ) use ($authUser) {
                        $tokenQ->where('recruitment_client_id', $authUser->recruitment_client_id);
                    });
                } elseif ($modelClass instanceof \App\Models\CultureFitResult) {
                    $q->orWhereHas('cultureFitTestToken', function ($tokenQ) use ($authUser) {
                        $tokenQ->where('recruitment_client_id', $authUser->recruitment_client_id);
                    });
                }
            });
        }

        if ($authUser->role === 'client') {
            return $query->where($userIdColumn, $authUser->id);
        }

        return $query->where($userIdColumn, $authUser->id);
    }

    /**
     * Verifica se o usuário autenticado pode acessar um recurso identificado
     * pelo seu user_id (criador). Usar em show/update/destroy de TOKEN.
     */
    protected function userCanAccessByUserId(int $resourceUserId): bool
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return false;
        }

        if (in_array($authUser->role, ['admin', 'operational'], true)) {
            return true;
        }

        if ($authUser->role === 'client' && $authUser->recruitment_client_id) {
            $resourceOwner = User::find($resourceUserId);
            return $resourceOwner && $resourceOwner->recruitment_client_id === $authUser->recruitment_client_id;
        }

        return $resourceUserId === $authUser->id;
    }

    /**
     * Verifica se o usuário autenticado pode acessar um RESULTADO de teste,
     * considerando também a regra de "candidato vinculado a vaga do cliente"
     * ou o vínculo direto do token com o cliente.
     */
    protected function userCanAccessResult(int $resultUserId, ?string $resultTesteeEmail, $resultModel = null): bool
    {
        if ($this->userCanAccessByUserId($resultUserId)) {
            return true;
        }

        $authUser = auth()->user();

        if (
            $authUser
            && $authUser->role === 'client'
            && $authUser->recruitment_client_id
        ) {
            if ($resultModel) {
                if ($resultModel instanceof \App\Models\DiscTestResult) {
                    if ($resultModel->discTestToken && $resultModel->discTestToken->recruitment_client_id === $authUser->recruitment_client_id) {
                        return true;
                    }
                } elseif ($resultModel instanceof \App\Models\CultureFitResult) {
                    if ($resultModel->cultureFitTestToken && $resultModel->cultureFitTestToken->recruitment_client_id === $authUser->recruitment_client_id) {
                        return true;
                    }
                }
            }

            if ($resultTesteeEmail) {
                $clientUserIds = $this->getClientUserIds($authUser->recruitment_client_id);
                $clientJobIds  = Job::whereIn('user_id', $clientUserIds)->pluck('id')->all();

                return CandidateJobApplication::whereIn('job_id', $clientJobIds)
                    ->whereHas('candidate', fn ($q) => $q->where('email', $resultTesteeEmail))
                    ->exists();
            }
        }

        return false;
    }

    /**
     * Helper interno: lista user_ids pertencentes a um RecruitmentClient.
     */
    private function getClientUserIds(int $recruitmentClientId): array
    {
        return User::where('recruitment_client_id', $recruitmentClientId)
            ->pluck('id')
            ->all();
    }

    /**
     * Helper interno: lista emails de candidatos vinculados às vagas
     * dos usuários informados.
     */
    private function getClientLinkedCandidateEmails(array $clientUserIds): array
    {
        if (empty($clientUserIds)) {
            return [];
        }

        $clientJobIds = Job::whereIn('user_id', $clientUserIds)->pluck('id')->all();

        if (empty($clientJobIds)) {
            return [];
        }

        return CandidateJobApplication::whereIn('job_id', $clientJobIds)
            ->with('candidate:id,email')
            ->get()
            ->pluck('candidate.email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
