<?php

namespace App\Http\Controllers;

use App\Models\SerieTv;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SerieTvController extends Controller
{
    use AuthorizesRequests;

    // Elenco di tutte le Serie TV
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');

        if ($user->role === 'Admin') {
            return SerieTv::withCount('episodes')->get();
        }

        return SerieTv::where('user_id', $user->id)
            ->withCount('episodes')
            ->get();
    }

    // Visualizza una singola Serie TV
    public function show($id)
    {
        $serie = SerieTv::find($id);

        if (!$serie) {
            return response()->json(['message' => 'Serie TV non trovata'], 404);
        }

        return response()->json($serie);
    }

    // Crea una nuova Serie TV (solo Admin o User, secondo la policy)
    public function store(Request $request)
    {
        $this->authorize('create', SerieTv::class);

        $user = $request->attributes->get('user');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'category' => 'required|string|max:100',
            'language' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        $serie = SerieTv::create([
            ...$validated,
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Serie TV creata con successo', 'serie' => $serie], 201);
    }

    // Aggiorna una Serie TV
    public function update(Request $request, $id)
    {
        $serie = SerieTv::find($id);

        if (!$serie) {
            return response()->json(['message' => 'Serie TV non trovata'], 404);
        }

        $this->authorize('update', $serie);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'category' => 'sometimes|string|max:100',
            'language' => 'sometimes|string|max:20',
            'description' => 'nullable|string',
        ]);

        $serie->update($validated);

        return response()->json(['message' => 'Serie TV aggiornata', 'serie' => $serie]);
    }

    // Elimina una Serie TV
    public function destroy(Request $request, $id)
    {
        $serie = SerieTv::find($id);

        if (!$serie) {
            return response()->json(['message' => 'Serie TV non trovata'], 404);
        }

        $this->authorize('delete', $serie);

        $serie->delete();

        return response()->json(['message' => 'Serie TV eliminata con successo']);
    }
}

