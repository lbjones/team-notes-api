<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->unique(['team_id', 'name']);
        });
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
            $table->index(['team_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('users');
        Schema::dropIfExists('teams');
    }
};
