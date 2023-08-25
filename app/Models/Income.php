<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Income extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::created(function ($user) {
            Cache::forget('income_cache');
        });

        static::deleted(function ($user) {
            Cache::forget('income_cache');
        });
    }
}
