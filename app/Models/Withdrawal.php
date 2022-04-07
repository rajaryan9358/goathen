<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'txn_id',
        'txn_amount',
		'account_name',
		'account_number',
		'ifsc_code',
        'utr'
    ];
}
