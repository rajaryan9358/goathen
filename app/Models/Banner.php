<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'banner_image',
        'link_type',
        'banner_link'
    ];

    public function getBannerImageAttribute($value){
        $path = url('') . '/Banners/';
        return $path.$value;
    }
}
