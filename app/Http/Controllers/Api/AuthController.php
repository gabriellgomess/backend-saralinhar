<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'in:candidate,company',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'candidate',
        ]);

        if ($user->role === 'candidate') {
            \App\Models\Candidate::create([
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'pending',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login de usuário
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Opcional: Validar Cloudflare Turnstile se a chave secreta estiver configurada no .env
        $turnstileSecret = env('TURNSTILE_SECRET_KEY');
        if ($turnstileSecret) {
            $token = $request->input('turnstile_token');

            if (!$token) {
                throw ValidationException::withMessages([
                    'turnstile' => ['A validação de segurança é obrigatória.'],
                ]);
            }

            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $turnstileSecret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            if (!$response->successful() || !$response->json('success')) {
                throw ValidationException::withMessages([
                    'turnstile' => ['Falha na verificação de segurança (Captcha). Tente novamente.'],
                ]);
            }
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Carregar candidato associado se existir
        $candidate = \App\Models\Candidate::where('email', $user->email)->with('preferences')->first();
        
        // Adicionar dados do candidato ao objeto user para o frontend
        if ($candidate) {
            $user->candidate = $candidate;
            $user->preferences = $candidate->preferences;
        }

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Obter perfil do usuário autenticado
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Carregar candidato associado se existir
        $candidate = \App\Models\Candidate::where('email', $user->email)->with('preferences')->first();
        
        // Adicionar dados do candidato ao objeto user para o frontend
        if ($candidate) {
            $user->candidate = $candidate;
            $user->preferences = $candidate->preferences;
        }

        return response()->json([
            'user' => $user,
        ], 200);
    }

    /**
     * Alterar senha do próprio usuário
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['A senha atual está incorreta.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }

    /**
     * Logout de usuário
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ], 200);
    }
}
