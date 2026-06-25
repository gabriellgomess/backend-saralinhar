<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('recruitmentClient')
            ->whereIn('role', ['admin', 'operational']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->boolean('all')) {
            $users = $query->orderBy('name')->get();
            return response()->json($users);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($users);
    }

    /**
     * Lista usuários de um cliente específico.
     */
    public function byClient(Request $request, string $clientId)
    {
        $users = User::where('recruitment_client_id', $clientId)
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|string|email|max:255|unique:users',
            'password'               => 'required|string|min:8|confirmed',
            'role'                   => 'required|in:admin,operational,client,candidate,company',
            'recruitment_client_id'  => 'nullable|exists:recruitment_clients,id',
        ]);

        $user = User::create([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'password'              => Hash::make($request->password),
            'role'                  => $request->role,
            'recruitment_client_id' => $request->recruitment_client_id,
        ]);

        if ($user->role === 'candidate') {
            \App\Models\Candidate::create([
                'name'   => $user->name,
                'email'  => $user->email,
                'status' => 'pending',
            ]);
        }

        return response()->json($user->load('recruitmentClient'), 201);
    }

    public function show(string $id)
    {
        return User::with('recruitmentClient')->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'                  => 'required|in:admin,operational,client,candidate,company',
            'recruitment_client_id' => 'nullable|exists:recruitment_clients,id',
        ]);

        $user->update([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'role'                  => $request->role,
            'recruitment_client_id' => $request->recruitment_client_id,
        ]);

        if ($user->role === 'candidate' && !\App\Models\Candidate::where('email', $user->email)->exists()) {
            \App\Models\Candidate::create([
                'name'   => $user->name,
                'email'  => $user->email,
                'status' => 'pending',
            ]);
        }

        return response()->json($user->load('recruitmentClient'));
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() == $user->id) {
            return response()->json(['message' => 'Não é possível excluir o próprio usuário.'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Usuário excluído com sucesso.']);
    }

    public function resetPassword(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }
}
