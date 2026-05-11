<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'listing_id',
        'listing_title',
        'sender_id',
        'receiver_id',
    ];
}
