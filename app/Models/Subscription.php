<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'txn_id',
        'subs_type',
        'expiry_time',
        'subs_amount',
        'is_active'
    ];
}
