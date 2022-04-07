<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_id',
        'to_id',
        'refer_code',
		'from_account',
		'to_amount'
    ];
}
