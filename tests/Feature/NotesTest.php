<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $team, string $name): string
    {
        $this->assertSame(0, Artisan::call('member:create', compact('team', 'name')));
        $token = trim(Artisan::output());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertDatabaseHas('users', ['name' => $name, 'token_hash' => hash('sha256', $token)]);

        return $token;
    }

    private function login(string $token): void
    {
        // A feature test shares its guard across requests, unlike separate HTTP requests.
        Auth::forgetGuards();
        $this->withToken($token);
    }

    public function test_teammates_can_read_edit_and_delete_a_shared_note(): void
    {
        $this->login($this->member('North', 'Alice'));
        $id = $this->postJson('/api/notes', ['title' => 'Handoff', 'body' => 'Draft'])
            ->assertCreated()->json('id');

        $this->login($this->member('North', 'Bob'));
        $this->getJson('/api/notes')->assertOk()->assertJsonPath('data.0.id', $id);
        $url = "/api/notes/$id";
        $this->getJson($url)->assertOk()->assertJsonPath('body', 'Draft');
        $this->patchJson($url, ['body' => 'Reviewed'])->assertOk()->assertJsonPath('title', 'Handoff');
        $this->assertDatabaseHas('notes', ['id' => $id, 'body' => 'Reviewed']);
        $this->deleteJson($url)->assertNoContent();
        $this->getJson($url)->assertNotFound();
    }

    public function test_another_team_cannot_discover_or_change_a_note(): void
    {
        $this->login($this->member('North', 'Alice'));
        $note = $this->postJson('/api/notes', ['title' => 'Secret', 'body' => 'Draft'])->assertCreated()->json();
        $this->login($this->member('South', 'Bob'));
        $url = '/api/notes/'.$note['id'];
        $this->getJson('/api/notes?team_id='.$note['team_id'])->assertOk()->assertJsonCount(0, 'data');
        $this->getJson($url)->assertNotFound();
        $this->patchJson($url, ['title' => 'Changed'])->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
        $this->assertDatabaseHas('notes', ['id' => $note['id'], 'title' => 'Secret']);

        $new = $this->postJson('/api/notes', ['title' => 'South', 'body' => 'Draft', 'team_id' => $note['team_id']])
            ->assertCreated()->json();
        $this->assertNotSame($note['team_id'], $new['team_id']);
    }

    public function test_missing_and_invalid_bearer_tokens_are_rejected(): void
    {
        $this->get('/api/notes')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->login('invalid');
        $this->postJson('/api/notes', [])->assertUnauthorized();
    }

    public function test_duplicate_provisioning_preserves_the_existing_token(): void
    {
        $token = $this->member('North', 'Alice');
        $this->assertSame(1, Artisan::call('member:create', ['team' => 'North', 'name' => 'Alice']));
        $this->login($token);
        $this->getJson('/api/notes')->assertOk();
    }

    public function test_invalid_create_and_patch_leave_stored_data_intact(): void
    {
        $this->login($this->member('North', 'Alice'));
        $this->postJson('/api/notes', ['title' => str_repeat('a', 201)])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'body']);
        $this->assertDatabaseCount('notes', 0);
        $id = $this->postJson('/api/notes', ['title' => 'Title', 'body' => 'Text'])->assertCreated()->json('id');
        $this->patchJson("/api/notes/$id", ['body' => ' '])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->patchJson("/api/notes/$id", [])->assertUnprocessable();
        $this->assertDatabaseHas('notes', ['id' => $id, 'body' => 'Text']);
    }
}
