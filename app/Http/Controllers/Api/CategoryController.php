<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Lista todas as categorias ativas
     */
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Categorias recuperadas com sucesso',
            'data' => $categories,
        ], 200);
    }

    /**
     * Lista todas as categorias (incluindo inativas) - apenas para admin
     */
    public function all()
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'message' => 'Todas as categorias recuperadas com sucesso',
            'data' => $categories,
        ], 200);
    }

    /**
     * Exibe uma categoria específica
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Categoria não encontrada',
            ], 404);
        }

        return response()->json([
            'message' => 'Categoria recuperada com sucesso',
            'data' => $category,
        ], 200);
    }

    /**
     * Cria uma nova categoria
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            // Gera o slug automaticamente
            $validated['slug'] = Str::slug($validated['name']);

            $category = Category::create($validated);

            return response()->json([
                'message' => 'Categoria criada com sucesso',
                'data' => $category,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Atualiza uma categoria existente
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Categoria não encontrada',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $id,
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            // Atualiza o slug se o nome mudou
            if ($validated['name'] !== $category->name) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $category->update($validated);

            return response()->json([
                'message' => 'Categoria atualizada com sucesso',
                'data' => $category,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove uma categoria
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Categoria não encontrada',
            ], 404);
        }

        // Verifica se há vagas associadas
        $jobsCount = $category->jobs()->count();

        if ($jobsCount > 0) {
            return response()->json([
                'message' => "Não é possível deletar esta categoria pois existem {$jobsCount} vaga(s) associada(s)",
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoria deletada com sucesso',
        ], 200);
    }
}
