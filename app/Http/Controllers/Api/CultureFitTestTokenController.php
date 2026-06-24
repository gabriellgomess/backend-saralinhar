<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CultureFitTestToken;
use App\Models\TestAuditLog;
use App\Traits\ScopesByClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\CultureFitTestLinkMail;

class CultureFitTestTokenController extends Controller
{
    use ScopesByClient;

    /**
     * Lista tokens visíveis para o usuário autenticado.
     * Admin/operacional: todos. Cliente: apenas do próprio RecruitmentClient.
     */
    public function index()
    {
        try {
            $query = CultureFitTestToken::with(['user:id,name', 'recruitmentClient:id,name'])->orderBy('created_at', 'desc');
            $this->applyClientScope($query);
            $tokens = $query->get();

            return response()->json([
                'success' => true,
                'tokens' => $tokens,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar tokens Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tokens.',
            ], 500);
        }
    }

    /**
     * Cria um novo token para teste Culture Fit
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'candidate_id'         => 'nullable|exists:candidates,id',
                'recruitment_client_id'=> 'nullable|exists:recruitment_clients,id',
                'testee_name'          => 'nullable|string|max:255',
                'testee_email'         => 'nullable|email|max:255',
                'testee_phone'         => 'nullable|string|max:20',
                'testee_position'      => 'nullable|string|max:255',
                'job_title'            => 'nullable|string|max:255',
                'description'          => 'nullable|string|max:1000',
                'expires_in_days'      => 'nullable|integer|min:1|max:365',
            ]);

            $candidate = null;
            if (!empty($validated['candidate_id'])) {
                $candidate = \App\Models\Candidate::find($validated['candidate_id']);
            }

            $token = CultureFitTestToken::create([
                'user_id'               => auth()->id(),
                'candidate_id'          => $candidate?->id,
                'recruitment_client_id' => $validated['recruitment_client_id'] ?? null,
                'token'                 => CultureFitTestToken::generateToken(),
                'testee_name'           => $candidate?->name    ?? $validated['testee_name']    ?? null,
                'testee_email'          => $candidate?->email   ?? $validated['testee_email']   ?? null,
                'testee_phone'          => $candidate?->phone   ?? $validated['testee_phone']   ?? null,
                'testee_position'       => $validated['testee_position'] ?? null,
                'job_title'             => $validated['job_title']       ?? null,
                'description'           => $validated['description']     ?? null,
                'expires_at'            => isset($validated['expires_in_days'])
                    ? now()->addDays((int) $validated['expires_in_days'])
                    : now()->addDays(30),
                'status'                => 'active',
            ]);

            // Gera a URL pública do teste
            $testUrl = URL::to('/teste-culture-fit/' . $token->token);

            // Envia o e-mail se houver destinatário
            if ($token->testee_email) {
                try {
                    Mail::to($token->testee_email)->send(new CultureFitTestLinkMail($token, $testUrl));
                } catch (\Exception $e) {
                    Log::error('Erro ao enviar e-mail do teste Culture Fit: ' . $e->getMessage());
                    // Não retorna erro para o usuário, apenas loga
                }
            }

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
                TestAuditLog::ACTION_TOKEN_CREATED,
                'token',
                $token->id,
                [
                    'testee_name'   => $token->testee_name,
                    'testee_email'  => $token->testee_email,
                    'candidate_id'  => $token->candidate_id,
                    'job_title'     => $token->job_title,
                    'expires_at'    => optional($token->expires_at)->toIso8601String(),
                    'email_sent'    => (bool) $token->testee_email,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Token criado com sucesso!',
                'token' => $token,
                'test_url' => $testUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar token Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar token.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe um token específico
     */
    public function show($id)
    {
        try {
            $query = CultureFitTestToken::with(['cultureFitResult', 'recruitmentClient:id,name']);
            $this->applyClientScope($query);
            $token = $query->findOrFail($id);

            $testUrl = URL::to('/teste-culture-fit/' . $token->token);

            return response()->json([
                'success' => true,
                'token' => $token,
                'test_url' => $testUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar token Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Token não encontrado.',
            ], 404);
        }
    }

    /**
     * Atualiza um token
     */
    public function update(Request $request, $id)
    {
        try {
            $query = CultureFitTestToken::query();
            $this->applyClientScope($query);
            $token = $query->findOrFail($id);

            $validated = $request->validate([
                'candidate_id'         => 'nullable|exists:candidates,id',
                'recruitment_client_id'=> 'nullable|exists:recruitment_clients,id',
                'testee_name'          => 'nullable|string|max:255',
                'testee_email'         => 'nullable|email|max:255',
                'testee_phone'         => 'nullable|string|max:20',
                'testee_position'      => 'nullable|string|max:255',
                'job_title'            => 'nullable|string|max:255',
                'description'          => 'nullable|string|max:1000',
                'expires_in_days'      => 'nullable|integer|min:1|max:365',
                'status'               => 'nullable|in:active,cancelled',
            ]);

            $updateData = $validated;

            if (array_key_exists('candidate_id', $validated) && $validated['candidate_id']) {
                $candidate = \App\Models\Candidate::find($validated['candidate_id']);
                if ($candidate) {
                    $updateData['testee_name']  = $candidate->name;
                    $updateData['testee_email'] = $candidate->email;
                    $updateData['testee_phone'] = $candidate->phone;
                }
            }

            if (isset($validated['expires_in_days'])) {
                $updateData['expires_at'] = now()->addDays((int) $validated['expires_in_days']);
                unset($updateData['expires_in_days']);
            }

            $token->update($updateData);

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
                TestAuditLog::ACTION_TOKEN_UPDATED,
                'token',
                $token->id,
                ['changes' => array_keys($updateData)]
            );

            return response()->json([
                'success' => true,
                'message' => 'Token atualizado com sucesso!',
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar token Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar token.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancela um token
     */
    public function cancel($id)
    {
        try {
            $query = CultureFitTestToken::query();
            $this->applyClientScope($query);
            $token = $query->findOrFail($id);

            if ($token->status === 'used') {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível cancelar um token já utilizado.',
                ], 400);
            }

            $token->update(['status' => 'cancelled']);

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
                TestAuditLog::ACTION_TOKEN_CANCELLED,
                'token',
                $token->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Token cancelado com sucesso!',
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar token Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar token.',
            ], 500);
        }
    }

    /**
     * Remove um token
     */
    public function destroy($id)
    {
        try {
            $query = CultureFitTestToken::query();
            $this->applyClientScope($query);
            $token = $query->findOrFail($id);

            if ($token->status === 'used') {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir um token já utilizado.',
                ], 400);
            }

            $tokenId = $token->id;
            $token->delete();

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
                TestAuditLog::ACTION_TOKEN_DELETED,
                'token',
                $tokenId
            );

            return response()->json([
                'success' => true,
                'message' => 'Token excluído com sucesso!',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir token Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir token.',
            ], 500);
        }
    }

    /**
     * Valida um token público (para uso nas rotas públicas)
     */
    public function validateToken($tokenString)
    {
        try {
            $token = CultureFitTestToken::where('token', $tokenString)
                ->active()
                ->valid()
                ->first();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido ou expirado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao validar token Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao validar token.',
            ], 500);
        }
    }
}
