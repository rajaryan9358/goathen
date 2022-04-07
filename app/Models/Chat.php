<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_user_id',
        'buyer_user_id',
        'ads_id',
		'blocked_id'
    ];
}
