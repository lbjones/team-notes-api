<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->controller(NoteController::class)->group(function () {
    Route::get('notes', 'index');
    Route::post('notes', 'store');
    Route::get('notes/{note}', 'show')->whereNumber('note');
    Route::patch('notes/{note}', 'update')->whereNumber('note');
    Route::delete('notes/{note}', 'destroy')->whereNumber('note');
});
