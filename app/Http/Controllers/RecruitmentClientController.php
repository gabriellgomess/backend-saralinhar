<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\RecruitmentClient;

class RecruitmentClientController extends Controller
{
    public function index()
    {
        $clients = RecruitmentClient::orderBy('name')->get();
        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'is_player'              => 'nullable|boolean',
            'type'                   => 'required|in:PF,PJ',
            'cnpj_cpf'               => 'nullable|string|max:20',
            'contact_name'           => 'nullable|string|max:255',
            'secondary_contact_name' => 'nullable|string|max:255',
            'phone'                  => 'nullable|string|max:20',
            'secondary_phone'        => 'nullable|string|max:20',
            'email'                  => 'nullable|email|max:255',
            'website'                => 'nullable|string|max:255',
            'address'                => 'nullable|string|max:255',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:2',
            'zip_code'               => 'nullable|string|max:10',
            'logo'                   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'notes'                  => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('clients/logos', 'public');
        }

        unset($validated['logo']);
        $client = RecruitmentClient::create($validated);
        return response()->json($client, 201);
    }

    public function show($id)
    {
        $client = RecruitmentClient::with('activities')->findOrFail($id);
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        $client = RecruitmentClient::findOrFail($id);

        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'is_player'              => 'nullable|boolean',
            'type'                   => 'required|in:PF,PJ',
            'cnpj_cpf'               => 'nullable|string|max:20',
            'contact_name'           => 'nullable|string|max:255',
            'secondary_contact_name' => 'nullable|string|max:255',
            'phone'                  => 'nullable|string|max:20',
            'secondary_phone'        => 'nullable|string|max:20',
            'email'                  => 'nullable|email|max:255',
            'website'                => 'nullable|string|max:255',
            'address'                => 'nullable|string|max:255',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:2',
            'zip_code'               => 'nullable|string|max:10',
            'logo'                   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'notes'                  => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            if ($client->logo_path && Storage::disk('public')->exists($client->logo_path)) {
                Storage::disk('public')->delete($client->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('clients/logos', 'public');
        }

        unset($validated['logo']);
        $client->update($validated);
        return response()->json($client);
    }

    public function destroy($id)
    {
        $client = RecruitmentClient::findOrFail($id);

        if ($client->logo_path && Storage::disk('public')->exists($client->logo_path)) {
            Storage::disk('public')->delete($client->logo_path);
        }

        $client->delete();
        return response()->json(null, 204);
    }
}
