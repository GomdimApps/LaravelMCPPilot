<?php

namespace App\Models;

use App\Concerns\HasDisplayName;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasDisplayName;

    protected $fillable = ['name', 'email'];
}
