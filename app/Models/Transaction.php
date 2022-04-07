<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'txn_title',
        'txn_mode',
		'txn_type',
		'txn_status',
		'txn_amount',
		'closing_balance',
        'reference_id'
    ];
}
