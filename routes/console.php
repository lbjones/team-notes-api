<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

Artisan::command('member:create {team} {name}', function () {
    $team = trim($this->argument('team'));
    $name = trim($this->argument('name'));
    Validator::make(compact('team', 'name'), [
        'team' => 'required|string|max:100',
        'name' => 'required|string|max:100',
    ])->validate();

    $token = bin2hex(random_bytes(32));
    $created = DB::transaction(function () use ($team, $name, $token) {
        DB::table('teams')->insertOrIgnore(['name' => $team]);
        $teamId = DB::table('teams')->where('name', $team)->value('id');

        return DB::table('users')->insertOrIgnore([
            'name' => $name,
            'team_id' => $teamId,
            'token_hash' => hash('sha256', $token),
        ]);
    });

    if (! $created) {
        $this->error('Member already exists in this team.');

        return 1;
    }

    $this->line($token);

    return 0;
})->purpose('Create a team member and print their bearer token once');
