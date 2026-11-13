<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Hidden(['token_hash'])]
class User extends Authenticatable
{
    public $timestamps = false;
}
