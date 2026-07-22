<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thing extends Model
{
    protected $fillable = [
        'title',
        'email',
        'amount',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function getDisplayTitleAttribute(): string
    {
        return $this->title;
    }
}
