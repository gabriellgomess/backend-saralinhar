<?php

namespace App\Http\Middleware;

use App\Models\DiscTestToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDiscToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido.',
            ], 400);
        }

        $tokenModel = DiscTestToken::where('token', $token)
            ->active()
            ->valid()
            ->first();

        if (!$tokenModel) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido, expirado ou já utilizado.',
            ], 404);
        }

        // Adiciona o token ao request para uso nos controllers
        $request->merge(['validated_token' => $tokenModel]);

        return $next($request);
    }
}
