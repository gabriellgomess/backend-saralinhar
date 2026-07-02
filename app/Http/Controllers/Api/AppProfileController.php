<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Perfil profissional do candidato no app EntrevistaPro AI.
 * O perfil é persistido no registro Candidate (mesmo banco de candidatos do site).
 */
class AppProfileController extends Controller
{
    public function show(Request $request)
    {
        $candidate = $this->resolveCandidate($request->user());

        return response()->json([
            'message' => 'Perfil recuperado com sucesso',
            'data' => $candidate,
        ], 200);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'professional_area' => 'nullable|string|max:255',
            'desired_role' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'work_mode' => 'nullable|in:presencial,hibrido,remoto',
            'qualifications_summary' => 'nullable|string|max:5000',
            'education' => 'nullable|string|max:2000',
            'skills' => 'nullable|string|max:2000',
            'salary_expectation' => 'nullable|string|max:100',
            'summary' => 'nullable|string|max:5000',
            'phone' => 'nullable|string|max:30',
        ]);

        $candidate = $this->resolveCandidate($request->user());
        $candidate->update($validated);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso',
            'data' => $candidate->fresh(),
        ], 200);
    }

    /**
     * Encontra (ou cria) o Candidate do usuário logado.
     * Vincula por user_id; se não houver, adota o registro existente com o
     * mesmo e-mail (evita duplicar candidatos já presentes no banco do site).
     */
    private function resolveCandidate(User $user): Candidate
    {
        $candidate = Candidate::where('user_id', $user->id)->first();

        if ($candidate) {
            return $candidate;
        }

        $candidate = Candidate::where('email', $user->email)
            ->whereNull('user_id')
            ->orderBy('id')
            ->first();

        if ($candidate) {
            $candidate->update(['user_id' => $user->id]);
            return $candidate;
        }

        return Candidate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'pending',
        ]);
    }
}
