<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'category',
        'location',
        'date',
        'image',
        'views',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'views' => 'integer',
    ];
}
