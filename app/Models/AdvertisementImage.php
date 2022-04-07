<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisementImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ads_id',
        'image_link',
    ];

    public function advertisement(){
        return $this->belongsTo(Advertisement::class,'ads_id');
    }
}
