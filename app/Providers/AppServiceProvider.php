<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::viaRequest('member-token', function (Request $request): ?User {
            $token = $request->bearerToken();

            return $token ? User::where('token_hash', hash('sha256', $token))->first() : null;
        });
    }
}
