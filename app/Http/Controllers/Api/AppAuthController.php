<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Autenticação do app EntrevistaPro AI.
 *
 * Rota de login própria do app: sem Cloudflare Turnstile (inviável no mobile),
 * protegida por throttle nas rotas. Registro continua usando POST /register.
 */
class AppAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'E-mail ou senha incorretos',
            ], 401);
        }

        $token = $user->createToken('app_token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Gera um código de 6 dígitos e envia por e-mail.
     * Resposta sempre genérica para não revelar se o e-mail existe.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            try {
                Mail::raw(
                    "Seu código de recuperação de senha do EntrevistaPro AI é: {$code}\n\n" .
                    'O código expira em 60 minutos. Se você não solicitou, ignore este e-mail.',
                    function ($message) use ($user) {
                        $message->to($user->email)
                            ->subject('Recuperação de senha — EntrevistaPro AI');
                    }
                );
            } catch (\Throwable $e) {
                Log::error('Falha ao enviar e-mail de recuperação (app): ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um código de recuperação.',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        $valid = $record
            && Hash::check($request->code, $record->token)
            && now()->diffInMinutes($record->created_at) < 60;

        if (!$valid) {
            return response()->json([
                'message' => 'Código inválido ou expirado',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Código inválido ou expirado'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete(); // revoga sessões antigas

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.',
        ], 200);
    }
}
