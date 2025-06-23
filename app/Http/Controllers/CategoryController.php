<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Tutti gli utenti autenticati possono vedere le categorie
    public function index(Request $request)
    {
        // Se serve l'utente loggato: $user = $request->attributes->get('user');
        return response()->json(Category::all(), 200);
    }

    // Solo Admin (gestito tramite middleware in api.php)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($validated);
        return response()->json($category, 201);
    }

    // Solo Admin (gestito tramite middleware in api.php)
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);
        return response()->json($category, 200);
    }

    // Solo Admin (gestito tramite middleware in api.php)
    public function destroy(Request $request, Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Categoria eliminata con successo'], 200);
    }
}




