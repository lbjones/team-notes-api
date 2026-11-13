<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NoteController extends Controller
{
    private function notes(Request $request): Builder
    {
        return Note::where('team_id', $request->user()->team_id);
    }

    public function index(Request $request)
    {
        return $this->notes($request)->orderByDesc('id')->simplePaginate(25);
    }

    public function store(Request $request)
    {
        $note = new Note($request->validate([
            'title' => 'required|string|max:200',
            'body' => 'required|string|max:20000',
        ]));
        $note->team_id = $request->user()->team_id;
        $note->save();

        return response()->json($note, 201);
    }

    public function show(Request $request, string $note)
    {
        return $this->notes($request)->findOrFail($note);
    }

    public function update(Request $request, string $note)
    {
        $record = $this->notes($request)->findOrFail($note);
        $changes = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'body' => 'sometimes|required|string|max:20000',
        ]);
        if ($changes === []) {
            throw ValidationException::withMessages(['note' => 'Supply a title or body.']);
        }
        $record->update($changes);

        return $record;
    }

    public function destroy(Request $request, string $note)
    {
        $this->notes($request)->findOrFail($note)->delete();

        return response()->noContent();
    }
}
