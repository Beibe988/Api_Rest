<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MovieController extends Controller
{
    use AuthorizesRequests;

    // Elenco di tutti i film
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'Admin') {
            return response()->json(Movie::all(), 200);
        } else {
            return response()->json(Movie::where('user_id', $user->id)->get(), 200);
        }
    }

    // Visualizzazione di un film specifico
    public function show(Movie $movie)
    {
        return response()->json($movie, 200);
    }

    // Inserimento di un nuovo film (autorizzato da policy)
    public function store(Request $request)
    {
        $this->authorize('create', Movie::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|integer|min:1800|max:' . date('Y'),
            'video_link' => 'nullable|string|max:500',
            'category' => 'required|string',
            'language' => 'required|string',
            'description' => 'required|string',
        ]);

        $validated['user_id'] = Auth::id();

        $movie = Movie::create($validated);

        return response()->json(['message' => 'Film inserito con successo', 'movie' => $movie], 201);
    }

    // Aggiornamento di un film
    public function update(Request $request, Movie $movie)
    {
        $this->authorize('update', $movie);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:1800|max:' . date('Y'),
            'video_link' => 'nullable|string|max:500',
            'category' => 'sometimes|string',
            'language' => 'sometimes|string',
            'description' => 'sometimes|string',
        ]);

        $movie->update($validated);

        return response()->json(['message' => 'Dati aggiornati con successo', 'movie' => $movie]);
    }

    // Eliminazione di un film
    public function destroy(Movie $movie)
    {
        $this->authorize('delete', $movie);

        $movie->delete();

        return response()->json(['message' => 'Film eliminato con successo'], 200);
    }
}



