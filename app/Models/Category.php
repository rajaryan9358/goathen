<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'category_image',
        'is_active'
    ];

    public function getCategoryImageAttribute($value){
        $path = url('') . '/Categories/';
        return $path.$value;
    }
}
