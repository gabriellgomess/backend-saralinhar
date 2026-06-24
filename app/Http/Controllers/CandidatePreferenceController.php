<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidatePreferenceController extends Controller
{
    /**
     * Get candidate preferences
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        
        // Tenta encontrar candidato pelo email do usuário
        $candidate = Candidate::where('email', $user->email)->first();

        if (!$candidate) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $candidate->preferences
        ]);
    }

    /**
     * Update candidate preferences
     */
    public function update(Request $request, $id = null)
    {
        $user = Auth::user();
        $candidate = null;

        if ($id && $id !== 'profile') {
            $candidate = Candidate::find($id);
        } else {
            $candidate = Candidate::where('email', $user->email)->first();
        }

        // Se não encontrar o candidato e estivermos no contexto do perfil do usuário logado,
        // cria o registro do candidato automaticamente.
        if (!$candidate && (!$id || $id === 'profile')) {
            $candidate = Candidate::create([
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'pending',
            ]);
        }

        if (!$candidate) {
            return response()->json(['message' => 'Candidato não encontrado'], 404);
        }

        // Validação
        $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        // Sincronizar
        $candidate->preferences()->sync($request->categories);

        return response()->json([
            'message' => 'Preferências atualizadas com sucesso',
            'data' => $candidate->load('preferences')->preferences
        ]);
    }
}
