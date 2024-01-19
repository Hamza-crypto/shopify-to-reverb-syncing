<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'name', 'category', 'sku', 'quantity', 'full_data'
    ];

    protected $casts = [
        'full_data' => 'json',
    ];
}