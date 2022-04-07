<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
		'user_id',
        'category_id',
        'ads_title',
		'primary_image',
        'ads_description',
		'lat',
		'lng',
		'address',
		'price',
		'status',
		'contact_count',
		'is_deleted',
		'is_hidden',
		'is_chat_available',
		'is_call_available',
    ];

	public function advertisementImage(){
		return $this->hasmany(AdvertisementImage::class,'ads_id');
	}
}
