<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EpisodeController extends Controller
{
    use AuthorizesRequests;

    // Visualizza tutti gli episodi
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');

        if ($user->role === 'Admin') {
            return response()->json(Episode::all(), 200);
        }

        // Episodi delle serie create da questo utente
        return response()->json(
            Episode::whereHas('serie', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get(),
            200
        );
    }

    // Visualizza un singolo episodio
    public function show($id)
    {
        $episode = Episode::with('user')->find($id); // carica relazione user (se serve)

        if (!$episode) {
            return response()->json(['message' => 'Episodio non trovato'], 404);
        }

        // Se vuoi restituire dati utente, decripta!
        if ($episode->user) {
            $episode->user->name = Crypt::decryptString($episode->user->name);
            $episode->user->surname = Crypt::decryptString($episode->user->surname);
            $episode->user->email = Crypt::decryptString($episode->user->email);
        }

        return response()->json($episode, 200);
    }

    // Crea un nuovo episodio
    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\Episode::class);

        $user = $request->attributes->get('user');

        $validated = $request->validate([
            'serie_tv_id'     => 'required|exists:serie_tv,id',
            'title'           => 'required|string|max:255',
            'season'          => 'required|integer|min:1',
            'episode_number'  => 'required|integer|min:1',
            'language'        => 'required|string|max:50',
            'description'     => 'nullable|string',
            'video_url'       => 'nullable|url|max:255',
        ]);

        $episode = Episode::create([
            ...$validated,
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Episodio creato', 'episode' => $episode], 201);
    }

    // Aggiorna un episodio
    public function update(Request $request, $id)
    {
        $episode = Episode::find($id);

        if (!$episode) {
            return response()->json(['message' => 'Episodio non trovato'], 404);
        }

        $this->authorize('update', $episode);

        $validated = $request->validate([
            'serie_tv_id'     => 'sometimes|exists:serie_tv,id',
            'title'           => 'sometimes|string|max:255',
            'season'          => 'sometimes|integer|min:1',
            'episode_number'  => 'sometimes|integer|min:1',
            'language'        => 'sometimes|string|max:50',
            'description'     => 'nullable|string',
            'video_url'       => 'nullable|url|max:255',
        ]);

        $episode->update($validated);

        return response()->json(['message' => 'Episodio aggiornato', 'episode' => $episode]);
    }

    // Elimina un episodio
    public function destroy($id)
    {
        $episode = Episode::with('serie')->find($id);

        if (!$episode) {
            return response()->json(['message' => 'Episodio non trovato'], 404);
        }

        $this->authorize('delete', $episode);

        $episode->delete();

        return response()->json(['message' => 'Episodio eliminato con successo']);
    }
}







