<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'cfp_app_id',
        'cfp_secret_key',
		'cf_app_id',
		'cf_secret_key',
		'cfp_auth',
		'cfp_auth_expiry',
		'free_ads_count',
		'free_withdrawal_count',
        'tnc',
		'privacy_policy',
		'about_us',
		'contact_us',
		'monthly_subs_price',
		'yearly_subs_price',
		'from_refer_amount',
		'to_refer_amount',
		'gcm_auth',
		'sms_api_key',
		'sms_senderid',
    ];
}
