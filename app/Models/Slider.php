<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Slider extends Model
{
    protected $fillable = [
        'image',
        'title',
        'description',
        'link'
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($value) => url('/storage/sliders/' . $value),
        );
    }
}
