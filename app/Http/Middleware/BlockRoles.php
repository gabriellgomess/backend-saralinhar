<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia o acesso a uma rota para usuários cujo role esteja na lista informada.
 *
 * Uso em rotas:
 *   Route::middleware('block_roles:client')->group(...);
 *   Route::middleware('block_roles:client,candidate')->group(...);
 *
 * Útil para rotas administrativas/internas que não devem ser acessíveis
 * por roles de portal externo (ex: clientes).
 */
class BlockRoles
{
    public function handle(Request $request, Closure $next, string ...$blockedRoles): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, $blockedRoles, true)) {
            return response()->json([
                'message' => 'Acesso não autorizado para o seu perfil.',
            ], 403);
        }

        return $next($request);
    }
}
